<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BloodInventory;
use App\Models\Facility;
use App\Models\ReportRequest;
use App\Models\User;
use App\Notifications\ReportRequestReviewed;
use App\Notifications\ReportRequestSubmitted;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Facility $main;
    private Facility $branch;
    private User $qao;
    private User $bbs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->main = Facility::create(['code' => 'MAIN-REPORT', 'name' => 'Bacolod Main', 'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true]);
        $this->branch = Facility::create(['code' => 'BRANCH-REPORT', 'name' => 'Branch', 'type' => 'blood_bank', 'is_active' => true]);
        $this->qao = User::factory()->create(['facility_id' => null, 'name' => 'QAO Reviewer']);
        $this->qao->assignRole('Quality Assurance Officer');
        $this->bbs = User::factory()->create(['facility_id' => $this->main->id, 'name' => 'BBS Requester']);
        $this->bbs->assignRole('Blood Bank Staff');
    }

    public function test_bbs_requests_stock_summary_and_downloads_only_after_qao_approval(): void
    {
        $stock = BloodInventory::create([
            'facility_id' => $this->main->id, 'blood_type' => 'O+', 'component' => 'whole_blood',
            'units_available' => 5, 'expiration_date' => today()->addMonth(), 'status' => 'active',
        ]);
        BloodInventory::create([
            'facility_id' => $this->branch->id, 'blood_type' => 'AB-', 'component' => 'whole_blood',
            'units_available' => 99, 'expiration_date' => today()->addMonth(), 'status' => 'active',
        ]);

        $this->actingAs($this->bbs)->get(route('reports.index'))->assertOk()->assertSee('Request a report');
        $this->post(route('report-requests.store'), ['report_type' => 'stock_summary'])->assertRedirect();
        $reportRequest = ReportRequest::sole();
        $this->assertSame('pending', $reportRequest->status);
        $this->assertSame($this->bbs->id, $reportRequest->requested_by);
        $this->assertSame('BBS Requester', $reportRequest->requester_name);
        $this->assertTrue(AuditLog::where('action', 'report_request.submitted')->where('auditable_id', $reportRequest->id)->exists());
        $this->assertNotNull($this->qao->notifications()->where('type', ReportRequestSubmitted::class)->first());
        $this->get(route('report-requests.download', [$reportRequest, 'pdf']))->assertNotFound();
        $this->post(route('report-requests.store'), ['report_type' => 'stock_summary'])->assertSessionHasErrors('report_type');

        $this->actingAs($this->qao)->get(route('reports.index'))->assertOk()->assertSee('BBS Requester');
        $this->get(route('report-requests.show', $reportRequest))->assertOk()->assertSee('Approve and prepare report');
        $this->post(route('report-requests.review', $reportRequest), ['decision' => 'approved'])->assertRedirect();
        $reportRequest->refresh();
        $this->assertSame('approved', $reportRequest->status);
        $this->assertNull($reportRequest->pending_key);
        $this->assertSame('5', $reportRequest->snapshot['sections'][0]['rows'][0][2]);
        $this->assertCount(1, $reportRequest->snapshot['sections'][0]['rows']);
        $this->assertSame('QAO Reviewer', $reportRequest->reviewer_name);
        $this->assertSame('Approved by', $reportRequest->snapshot['attributionLabel']);
        $this->assertTrue(AuditLog::where('action', 'report_request.approved')->where('auditable_id', $reportRequest->id)->exists());
        $notification = $this->bbs->notifications()->where('type', ReportRequestReviewed::class)->firstOrFail();

        $stock->update(['units_available' => 9]);
        $this->assertSame('5', $reportRequest->fresh()->snapshot['sections'][0]['rows'][0][2]);
        $this->actingAs($this->bbs)->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('report-requests.show', $reportRequest));
        $this->get(route('report-requests.show', $reportRequest))->assertOk()->assertSee('Download protected Excel');
        $this->get(route('report-requests.download', [$reportRequest, 'pdf']))->assertOk()->assertDownload();
        $this->get(route('report-requests.download', [$reportRequest, 'excel']))->assertOk()->assertDownload();

        $otherBbs = User::factory()->create(['facility_id' => $this->main->id]);
        $otherBbs->assignRole('Blood Bank Staff');
        $this->actingAs($otherBbs)->get(route('report-requests.show', $reportRequest))->assertForbidden();
        $this->get(route('report-requests.download', [$reportRequest, 'excel']))->assertForbidden();
        $this->actingAs($this->qao)->post(route('report-requests.review', $reportRequest), ['decision' => 'rejected', 'review_notes' => 'Duplicate'])->assertStatus(409);
    }

    public function test_rejection_needs_a_reason_and_branch_bbs_cannot_request(): void
    {
        $branchBbs = User::factory()->create(['facility_id' => $this->branch->id]);
        $branchBbs->assignRole('Blood Bank Staff');
        $this->actingAs($branchBbs)->post(route('report-requests.store'), ['report_type' => 'inventory_records'])->assertForbidden();
        $this->actingAs($this->qao)->post(route('report-requests.store'), ['report_type' => 'inventory_records'])->assertForbidden();

        $this->actingAs($this->bbs)->post(route('report-requests.store'), ['report_type' => 'inventory_records'])->assertRedirect();
        $reportRequest = ReportRequest::sole();
        $this->actingAs($this->qao)->post(route('report-requests.review', $reportRequest), ['decision' => 'rejected'])->assertSessionHasErrors('review_notes');
        $this->post(route('report-requests.review', $reportRequest), ['decision' => 'rejected', 'review_notes' => 'Please specify the needed record.'])->assertRedirect();
        $this->assertSame('rejected', $reportRequest->fresh()->status);
        $this->assertNull($reportRequest->fresh()->snapshot);
        $this->actingAs($this->bbs)->get(route('report-requests.download', [$reportRequest, 'pdf']))->assertNotFound();
        $this->post(route('report-requests.store'), ['report_type' => 'inventory_records'])->assertRedirect();
        $this->assertSame(2, ReportRequest::count());
    }

    public function test_report_period_stops_at_the_current_date(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-30 12:00:00'));

        $this->actingAs($this->bbs)
            ->get(route('reports.index', ['period' => 'month', 'month' => '2026-09']))
            ->assertOk()
            ->assertSee('max="2026-09"', false)
            ->assertSee('Future months are unavailable');

        foreach ([
            ['period' => 'month', 'month' => '2029-10'],
            ['period' => 'day', 'day' => '2026-10-01'],
            ['period' => 'range', 'from' => '2026-10-01'],
            ['period' => 'range', 'to' => '2026-10-01'],
        ] as $query) {
            $this->get(route('reports.index', $query))->assertRedirect(route('reports.index'));
        }

        $this->get(route('reports.index', ['period' => 'range', 'from' => '', 'to' => '']))
            ->assertOk()->assertSee('Choose both dates to apply a custom range.');

        $this->actingAs($this->qao)
            ->get(route('reports.pdf', ['period' => 'month', 'month' => '2029-10']))
            ->assertStatus(422);
    }

    public function test_bbs_selects_report_sections_detail_and_period_for_qao_approval(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-30 12:00:00'));
        BloodInventory::create([
            'facility_id' => $this->main->id, 'blood_type' => 'O+', 'component' => 'whole_blood',
            'units_available' => 5, 'expiration_date' => today()->addMonth(), 'status' => 'active',
        ]);

        $payload = [
            'report_type' => 'selected_report',
            'records' => ['inventory', 'donations'],
            'detail' => 'summary',
            'period' => 'month',
            'month' => '2026-09',
        ];
        $this->actingAs($this->bbs)->get(route('reports.index'))
            ->assertOk()->assertSee('Choose sections for a selected report')
            ->assertSee('Choose what to request');
        $this->post(route('report-requests.store'), [])->assertSessionHasErrors('report_type');
        $this->post(route('report-requests.store'), [
            ...$payload, 'records' => [],
        ])->assertSessionHasErrors('records');
        $this->post(route('report-requests.store'), [
            ...$payload, 'period' => 'range', 'from' => '', 'to' => '',
        ])->assertSessionHasErrors('period');
        $this->post(route('report-requests.store'), $payload)->assertRedirect();
        $reportRequest = ReportRequest::sole();
        $this->assertSame(['inventory', 'donations'], $reportRequest->selection['records']);
        $this->assertSame('summary', $reportRequest->selection['detail']);
        $this->assertSame('September 2026', $reportRequest->selection['period_label']);
        $this->get(route('report-requests.show', $reportRequest))
            ->assertOk()->assertSee('Inventory, Donations')->assertSee('Totals only');

        $this->actingAs($this->qao)->get(route('report-requests.show', $reportRequest))
            ->assertOk()->assertSee('Requested contents');
        $this->post(route('report-requests.review', $reportRequest), ['decision' => 'approved'])->assertRedirect();
        $snapshot = $reportRequest->fresh()->snapshot;
        $this->assertSame('summary', $snapshot['detail']);
        $this->assertSame('September 2026', $snapshot['periodLabel']);
        $this->assertSame(['Inventory', 'Donations'], array_column($snapshot['sections'], 'title'));
        $this->assertSame(5, $snapshot['sections'][0]['summary']['Total units']);
        $this->assertSame([], $snapshot['sections'][0]['rows']);

        $this->actingAs($this->bbs)->get(route('report-requests.download', [$reportRequest, 'pdf']))
            ->assertOk()->assertDownload();

        $this->post(route('report-requests.store'), [
            ...$payload, 'month' => '2029-10',
        ])->assertSessionHasErrors('period');
        $this->post(route('report-requests.store'), [
            ...$payload, 'records' => ['inventory', 'not_a_report'],
        ])->assertSessionHasErrors('records.1');
    }

    public function test_request_list_is_paginated_filterable_and_scoped_to_the_requester(): void
    {
        foreach (range(1, 12) as $number) {
            ReportRequest::create([
                'facility_id' => $this->main->id,
                'requested_by' => $this->bbs->id,
                'requester_name' => 'Own request '.$number,
                'report_type' => 'stock_summary',
                'status' => $number === 12 ? 'pending' : 'approved',
            ]);
        }
        $otherBbs = User::factory()->create(['facility_id' => $this->main->id]);
        $otherBbs->assignRole('Blood Bank Staff');
        ReportRequest::create([
            'facility_id' => $this->main->id,
            'requested_by' => $otherBbs->id,
            'requester_name' => 'Another requester',
            'report_type' => 'stock_summary',
            'status' => 'pending',
        ]);

        $this->actingAs($this->bbs)->get(route('reports.index'))
            ->assertOk()->assertSee('Showing 1–10 of 12 requests')
            ->assertSee('request_page=2')
            ->assertDontSee('Another requester');
        $this->get(route('reports.index', ['request_page' => 2]))
            ->assertOk()->assertSee('Showing 11–12 of 12 requests')
            ->assertDontSee('Another requester');
        $this->get(route('reports.index', ['request_status' => 'pending']))
            ->assertOk()->assertSee('Showing 1–1 of 1 request')
            ->assertSee('Own request 12')
            ->assertDontSee('Own request 11')
            ->assertDontSee('Another requester');

        $this->actingAs($this->qao)->get(route('reports.index', ['request_status' => 'pending']))
            ->assertOk()->assertSee('Showing 1–2 of 2 requests')
            ->assertSee('Another requester');
    }

    public function test_request_times_display_in_philippine_time_while_stored_in_utc(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-30 03:35:00', 'UTC'));

        $this->actingAs($this->bbs)->post(route('report-requests.store'), [
            'report_type' => 'stock_summary',
        ])->assertRedirect();

        $reportRequest = ReportRequest::sole();
        $this->assertSame('2026-09-30 03:35:00', $reportRequest->created_at->utc()->format('Y-m-d H:i:s'));
        $this->get(route('reports.index'))->assertOk()->assertSee('Sep 30, 2026 11:35 AM PHT');
        $this->get(route('report-requests.show', $reportRequest))
            ->assertOk()->assertSee('Sep 30, 2026 11:35 AM PHT');

        $this->actingAs($this->qao)
            ->post(route('report-requests.review', $reportRequest), ['decision' => 'approved'])
            ->assertRedirect();
        $this->assertSame('Sep 30, 2026 11:35 AM PHT', $reportRequest->fresh()->snapshot['printedAt']);
    }
}
