<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\BloodReservation;
use App\Models\DonationSchedule;
use App\Models\Facility;
use App\Models\User;
use App\Support\ReportData;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MainChapterDecisionsTest extends TestCase
{
    use RefreshDatabase;

    private Facility $main;

    private Facility $branch;

    private User $qao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->main = Facility::create(['code' => 'MAIN', 'name' => 'Bacolod Main', 'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true]);
        $this->branch = Facility::create(['code' => 'BRANCH', 'name' => 'Activity Branch', 'type' => 'blood_bank', 'is_active' => true]);
        $this->qao = User::factory()->create(['facility_id' => null]);
        $this->qao->assignRole('Quality Assurance Officer');
    }

    public function test_qao_creation_is_public_but_facilitator_creation_and_edits_need_approval(): void
    {
        Storage::fake('public');
        Notification::fake();
        $payload = ['facility_id' => $this->branch->id, 'title' => 'QAO Public Drive', 'event_type' => 'blood_donation', 'event_date' => now()->addWeek()->toDateString(), 'start_time' => '09:00', 'end_time' => '12:00', 'venue' => 'Town Hall', 'latitude' => 10.67, 'longitude' => 122.95, 'status' => 'planned', 'photo' => UploadedFile::fake()->image('poster.jpg')];
        $this->actingAs($this->qao)->post(route('donation-schedules.store'), $payload)->assertRedirect(route('donation-schedules.index'));
        $event = DonationSchedule::firstOrFail();
        $this->assertSame('approved', $event->approval_status);
        $this->assertSame($this->qao->id, $event->reviewed_by);
        $this->assertTrue($event->is_public);
        $this->get(route('public.map'))->assertSee('QAO Public Drive');
        $facilitator = User::factory()->create(['facility_id' => $this->branch->id]);
        $facilitator->assignRole('Event Facilitator');
        $this->actingAs($facilitator)->post(route('donation-schedules.store'), [...$payload, 'title' => 'Pending Branch Drive'])->assertRedirect();
        $pending = DonationSchedule::where('title', 'Pending Branch Drive')->firstOrFail();
        $this->assertFalse($pending->is_public);
        $this->get(route('public.map'))->assertDontSee('Pending Branch Drive');
        $this->actingAs($this->qao)->patch(route('donation-schedules.review', $pending), ['approval_status' => 'approved'])->assertRedirect();
        $this->actingAs($facilitator)->put(route('donation-schedules.update', $pending), [...$payload, 'title' => 'Changed Branch Drive'])->assertRedirect();
        $this->assertSame('pending', $pending->fresh()->approval_status);
        $this->assertNull($pending->fresh()->reviewed_by);
        $this->get(route('public.map'))->assertDontSee('Changed Branch Drive');
    }

    public function test_main_chapter_scope_blocks_branch_stock_and_branch_bbs(): void
    {
        $mainStock = BloodInventory::create(['facility_id' => $this->main->id, 'blood_type' => 'O+', 'component' => 'whole_blood', 'units_available' => 20, 'expiration_date' => now()->addMonth(), 'status' => 'active']);
        $branchStock = BloodInventory::create(['facility_id' => $this->branch->id, 'blood_type' => 'AB-', 'component' => 'whole_blood', 'units_available' => 999, 'expiration_date' => now()->addMonth(), 'status' => 'active']);
        $this->actingAs($this->qao)->get(route('blood-inventory.index'))->assertOk()->assertSee('O+')->assertDontSee('AB-');
        $this->get(route('blood-inventory.show', $branchStock))->assertForbidden();
        $this->get(route('blood-inventory.show', $mainStock))->assertOk();
        $bbs = User::factory()->create(['facility_id' => $this->branch->id]);
        $bbs->assignRole('Blood Bank Staff');
        $this->actingAs($bbs)->get(route('blood-inventory.index'))->assertForbidden();
        $this->get(route('reservations.index'))->assertForbidden();
        $this->actingAs($this->qao)->post(route('staff-users.store'), ['name' => 'Wrong Branch BBS', 'email' => 'wrong@example.test', 'facility_id' => $this->branch->id, 'role' => 'Blood Bank Staff', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertSessionHasErrors('facility_id');
        $patient = User::factory()->create();
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get(route('reservations.create'))->assertSee('Bacolod Main')->assertDontSee('Activity Branch');
        $this->post(route('reservations.store'), ['facility_id' => $this->branch->id, 'blood_type' => 'O+', 'component' => 'whole_blood', 'units_requested' => 1, 'needed_on' => now()->addDay()->toDateString(), 'identification' => UploadedFile::fake()->image('id.jpg'), 'blood_request' => UploadedFile::fake()->image('request.jpg')])->assertSessionHasErrors('facility_id');
        $this->assertDatabaseCount('blood_reservations', 0);
    }

    public function test_selectable_reports_filter_records_and_protect_private_fields(): void
    {
        $patient = User::factory()->create();
        BloodReservation::create(['reference' => 'MAIN-EXPORT', 'patient_user_id' => $patient->id, 'facility_id' => $this->main->id, 'blood_type' => 'O+', 'component' => 'whole_blood', 'units_requested' => 2, 'needed_on' => now()->addDay(), 'status' => 'submitted', 'clinical_purpose' => 'PRIVATE-CLINICAL-NOTE']);
        BloodReservation::create(['reference' => 'BRANCH-HIDDEN', 'patient_user_id' => $patient->id, 'facility_id' => $this->branch->id, 'blood_type' => 'O+', 'component' => 'whole_blood', 'units_requested' => 50, 'needed_on' => now()->addDay(), 'status' => 'submitted']);
        $sections = ReportData::sections(['reservations'], 'both', today()->toDateString(), today()->toDateString(), $this->qao);
        $this->assertCount(1, $sections);
        $this->assertSame(2, $sections[0]['summary']['Total units']);
        $this->assertStringNotContainsString('PRIVATE-CLINICAL-NOTE', json_encode($sections));
        $this->assertStringNotContainsString('BRANCH-HIDDEN', json_encode($sections));
        $this->assertSame([], ReportData::sections(['reservations'], 'summary', null, null, $this->qao)[0]['rows']);
        $this->assertSame([], ReportData::sections(['reservations'], 'details', '2000-01-01', '2000-01-02', $this->qao)[0]['rows']);
        foreach (['inventory', 'donations', 'releases', 'reservations'] as $type) {
            $this->actingAs($this->qao)->get(route('reports.excel', ['records' => [$type]]))->assertOk()->assertDownload();
            $this->get(route('reports.pdf', ['records' => [$type]]))->assertOk()->assertDownload();
        }
        $this->get(route('reports.excel', ['records' => array_keys(ReportData::TYPES)]))->assertOk()->assertDownload();
        $this->get(route('reports.excel', ['export_selection' => 1]))->assertSessionHasErrors('records');
        $this->get(route('reports.excel',['records' => ['bad']]))->assertSessionHasErrors('records.0');
    }
}
