<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\DonationRecord;
use App\Models\Donor;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class InventoryStorageBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private Facility $main;

    private Facility $branch;

    private User $bbs;

    private User $qao;

    public function createApplication()
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:'
            || $app['config']->get('database.connections.sqlite.url')) {
            throw new RuntimeException('Storage breakdown tests require isolated SQLite :memory:.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-01 10:00:00'));
        $this->seed(RolePermissionSeeder::class);
        $this->main = Facility::create([
            'code' => 'STORAGE-MAIN', 'name' => 'Bacolod Main', 'type' => 'blood_bank',
            'is_active' => true, 'is_main_chapter' => true,
        ]);
        $this->branch = Facility::create([
            'code' => 'STORAGE-BRANCH', 'name' => 'Activity Branch', 'type' => 'blood_bank', 'is_active' => true,
        ]);
        $this->bbs = User::factory()->create(['facility_id' => $this->main->id]);
        $this->bbs->assignRole('Blood Bank Staff');
        $this->qao = User::factory()->create(['facility_id' => null]);
        $this->qao->assignRole('Quality Assurance Officer');
    }

    public function test_one_manual_record_remains_one_batch_with_its_full_quantity_and_existing_record_link(): void
    {
        $stock = $this->stock(['units_available' => 12]);

        $response = $this->actingAs($this->bbs)->get($this->storageUrl())
            ->assertOk()->assertViewIs('blood-inventory.storage')
            ->assertViewHas('totalUnits', 12)->assertViewHas('batchCount', 1)
            ->assertViewHas('filteredUnits', 12)->assertViewHas('selectedExpiry', null);

        $this->assertBatchIds($response, [$stock->id]);
        $this->assertSame([
            $stock->expiration_date->toDateString() => ['units' => 12, 'batch_count' => 1],
        ], $this->expiryGroups($response));
        $cards = $this->cards($response);
        $this->assertCount(1, $cards);
        $this->assertSame((string) $stock->id, $cards[0]->getAttribute('data-stock-record-id'));
        $this->assertStringContainsString('12 units', $this->normalizedText($cards[0]->textContent));
        $response->assertSee('Manual stock entry')->assertSee(route('blood-inventory.show', $stock), false);
        $this->get(route('blood-inventory.show', $stock))->assertOk()->assertSee('12');
        $this->assertDatabaseCount('blood_inventory', 1);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame(12, (int) $stock->fresh()->units_available);
        $this->assertSame('active', $stock->fresh()->status);
    }

    public function test_same_date_donation_batches_stay_independent_and_expiry_groups_sum_correctly(): void
    {
        $firstDonation = $this->donation('STORAGE-DONATION-ONE');
        $secondDonation = $this->donation('STORAGE-DONATION-TWO');
        $first = $this->stock(['donation_record_id' => $firstDonation->id, 'units_available' => 3, 'expiration_date' => '2026-10-04']);
        $second = $this->stock(['donation_record_id' => $secondDonation->id, 'units_available' => 4, 'expiration_date' => '2026-10-04']);
        $later = $this->stock(['units_available' => 5, 'expiration_date' => '2026-10-12']);

        $response = $this->actingAs($this->bbs)->get($this->storageUrl())
            ->assertOk()->assertViewHas('totalUnits', 12)->assertViewHas('batchCount', 3)
            ->assertViewHas('filteredUnits', 12)
            ->assertSee('STORAGE-DONATION-ONE')->assertSee('STORAGE-DONATION-TWO');

        $this->assertSame([
            '2026-10-04' => ['units' => 7, 'batch_count' => 2],
            '2026-10-12' => ['units' => 5, 'batch_count' => 1],
        ], $this->expiryGroups($response));
        $this->assertBatchIds($response, [$first->id, $second->id, $later->id]);
        $this->assertSame(
            [$first->id, $second->id, $later->id],
            array_map(fn ($card) => (int) $card->getAttribute('data-stock-record-id'), $this->cards($response))
        );
    }

    public function test_date_filter_changes_batches_and_filtered_quantity_without_changing_storage_totals(): void
    {
        $first = $this->stock(['donation_record_id' => $this->donation('FILTER-DONATION-ONE')->id, 'units_available' => 2, 'expiration_date' => '2026-10-04']);
        $second = $this->stock(['donation_record_id' => $this->donation('FILTER-DONATION-TWO')->id, 'units_available' => 6, 'expiration_date' => '2026-10-04']);
        $later = $this->stock(['units_available' => 10, 'expiration_date' => '2026-10-12']);

        $response = $this->actingAs($this->bbs)->get($this->storageUrl(['expiration_date' => '2026-10-04']))
            ->assertOk()->assertViewHas('totalUnits', 18)->assertViewHas('batchCount', 3)
            ->assertViewHas('filteredUnits', 8)->assertViewHas('selectedExpiry', '2026-10-04');
        $this->assertBatchIds($response, [$first->id, $second->id]);
        $this->assertCount(2, $this->cards($response));
        $response->assertDontSee('data-stock-record-id="'.$later->id.'"', false);
        $this->assertSame([
            '2026-10-04' => ['units' => 8, 'batch_count' => 2],
            '2026-10-12' => ['units' => 10, 'batch_count' => 1],
        ], $this->expiryGroups($response));

        $empty = $this->get($this->storageUrl(['expiration_date' => '2026-10-07']))
            ->assertOk()->assertViewHas('totalUnits', 18)->assertViewHas('batchCount', 3)
            ->assertViewHas('filteredUnits', 0)->assertViewHas('selectedExpiry', '2026-10-07');
        $this->assertBatchIds($empty, []);
        $this->assertCount(0, $this->cards($empty));
    }

    public function test_breakdown_matches_usable_summary_and_excludes_other_groups_and_unusable_stock(): void
    {
        $today = $this->stock(['units_available' => 12, 'expiration_date' => '2026-10-01']);
        $low = $this->stock(['units_available' => 3, 'status' => 'low_stock', 'expiration_date' => '2026-10-06']);
        $this->stock(['component' => 'whole_blood', 'units_available' => 99]);
        $this->stock(['blood_type' => 'O-', 'units_available' => 100]);
        $this->stock(['facility_id' => $this->branch->id, 'units_available' => 999]);
        $this->stock(['status' => 'expired', 'expiration_date' => '2026-10-20', 'units_available' => 20]);
        $this->stock(['expiration_date' => '2026-09-30', 'units_available' => 30]);
        $this->stock(['units_available' => 0]);
        $this->stock(['units_available' => 50])->delete();
        $inactiveMain = Facility::create([
            'code' => 'INACTIVE-STORAGE', 'name' => 'Inactive Main', 'type' => 'blood_bank',
            'is_main_chapter' => true, 'is_active' => false,
        ]);
        $this->stock(['facility_id' => $inactiveMain->id, 'units_available' => 88]);

        foreach ([$this->bbs, $this->qao] as $reader) {
            $response = $this->actingAs($reader)->get($this->storageUrl())
                ->assertOk()->assertViewHas('totalUnits', 15)->assertViewHas('batchCount', 2)
                ->assertViewHas('filteredUnits', 15);
            $this->assertBatchIds($response, [$today->id, $low->id]);
            $this->assertCount(2, $this->cards($response));
            $summary = $this->get(route('blood-inventory.index'))->assertOk();
            $group = $summary->viewData('storageRows')->get('B+|fresh_frozen_plasma');
            $this->assertSame(15, (int) $group->units);
            $this->assertSame('2026-10-01', Carbon::parse($group->next_expiration)->toDateString());
        }
    }

    public function test_facility_reader_cannot_include_stock_from_another_active_main_facility(): void
    {
        $own = $this->stock(['units_available' => 12]);
        $otherMain = Facility::create([
            'code' => 'OTHER-STORAGE-MAIN', 'name' => 'Other Main', 'type' => 'blood_bank',
            'is_active' => true, 'is_main_chapter' => true,
        ]);
        $other = $this->stock(['facility_id' => $otherMain->id, 'units_available' => 80]);

        $response = $this->actingAs($this->bbs)->get($this->storageUrl())
            ->assertOk()->assertViewHas('totalUnits', 12)->assertViewHas('batchCount', 1);
        $this->assertBatchIds($response, [$own->id]);
        $response->assertDontSee('data-stock-record-id="'.$other->id.'"', false);
        $this->get(route('blood-inventory.show', $other))->assertForbidden();

        $forgedScope = $this->get($this->storageUrl(['facility_id' => $otherMain->id]))
            ->assertOk()->assertViewHas('totalUnits', 12)->assertViewHas('batchCount', 1);
        $this->assertBatchIds($forgedScope, [$own->id]);
        $forgedScope->assertDontSee('data-stock-record-id="'.$other->id.'"', false);
        $this->assertSame(12, (int) $own->fresh()->units_available);
        $this->assertSame(80, (int) $other->fresh()->units_available);
        $this->assertDatabaseCount('blood_inventory', 2);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_stored_donation_source_text_is_escaped_on_batch_cards(): void
    {
        $payload = '<img src=x onerror=alert(1)>';
        $donation = $this->donation($payload);
        $stock = $this->stock(['donation_record_id' => $donation->id]);

        $response = $this->actingAs($this->bbs)->get($this->storageUrl())
            ->assertOk()->assertSee(e($payload), false)->assertDontSee($payload, false);
        $this->assertBatchIds($response, [$stock->id]);
        $this->assertCount(1, $this->cards($response));
    }

    public function test_guest_and_public_or_wrong_facility_accounts_cannot_open_storage_breakdown(): void
    {
        $this->get($this->storageUrl())->assertRedirect(route('login'));
        foreach (['Patient', 'Donor', 'Event Facilitator'] as $role) {
            $user = User::factory()->create(['facility_id' => $this->main->id]);
            $user->assignRole($role);
            $this->actingAs($user)->get($this->storageUrl())->assertForbidden();
        }
        $branchStaff = User::factory()->create(['facility_id' => $this->branch->id]);
        $branchStaff->assignRole('Blood Bank Staff');
        $this->actingAs($branchStaff)->get($this->storageUrl())->assertForbidden();

        $inactiveStaff = User::factory()->create(['facility_id' => $this->main->id, 'is_active' => false]);
        $inactiveStaff->assignRole('Blood Bank Staff');
        $this->actingAs($inactiveStaff)->get($this->storageUrl())->assertForbidden();
    }

    public function test_required_group_filters_and_date_format_are_validated_by_server(): void
    {
        $this->actingAs($this->bbs);
        foreach ([
            [[], ['blood_type', 'component']],
            [['blood_type' => 'B+'], ['component']],
            [['component' => 'fresh_frozen_plasma'], ['blood_type']],
            [['blood_type' => 'UNKNOWN', 'component' => 'other'], ['blood_type', 'component']],
            [['blood_type' => 'B+', 'component' => 'fresh_frozen_plasma', 'expiration_date' => '10/04/2026'], ['expiration_date']],
            [['blood_type' => 'B+', 'component' => 'fresh_frozen_plasma', 'expiration_date' => '2026-02-30'], ['expiration_date']],
        ] as [$query, $invalid]) {
            $this->getJson(route('blood-inventory.storage', $query))
                ->assertUnprocessable()->assertJsonValidationErrors($invalid);
        }

        $this->get($this->storageUrl(['expiration_date' => '']))
            ->assertOk()->assertViewHas('selectedExpiry', null)->assertViewHas('totalUnits', 0);
    }

    public function test_pagination_preserves_group_filters_and_totals_include_all_records(): void
    {
        $stocks = [];
        for ($number = 1; $number <= 23; $number++) {
            $stocks[] = $this->stock([
                'units_available' => $number, 'expiration_date' => today()->addDays($number)->toDateString(),
            ]);
        }

        $firstPage = $this->actingAs($this->bbs)->get($this->storageUrl())
            ->assertOk()->assertViewHas('totalUnits', 276)->assertViewHas('batchCount', 23)
            ->assertViewHas('filteredUnits', 276);
        $this->assertBatchIds($firstPage, array_map(fn ($stock) => $stock->id, array_slice($stocks, 0, 20)));
        $this->assertCount(20, $this->cards($firstPage));
        $batches = $firstPage->viewData('batches');
        $this->assertSame(23, $batches->total());
        $this->assertSame(20, $batches->perPage());
        parse_str(parse_url($batches->url(2), PHP_URL_QUERY), $nextQuery);
        $this->assertSame('B+', $nextQuery['blood_type']);
        $this->assertSame('fresh_frozen_plasma', $nextQuery['component']);

        $lastPage = $this->get($this->storageUrl(['page' => 2]))
            ->assertOk()->assertViewHas('totalUnits', 276)->assertViewHas('batchCount', 23)
            ->assertViewHas('filteredUnits', 276);
        $this->assertBatchIds($lastPage, array_map(fn ($stock) => $stock->id, array_slice($stocks, 20)));
        $this->assertCount(3, $this->cards($lastPage));
        $this->assertCount(23, $this->expiryGroups($lastPage));

        foreach (array_slice($stocks, 20) as $stock) {
            $stock->delete();
        }
        $stalePage = $this->get($this->storageUrl(['page' => 2]))
            ->assertOk()->assertViewHas('totalUnits', 210)->assertViewHas('batchCount', 20)
            ->assertSee('No batches on this page')->assertSee('Return to first page')
            ->assertDontSee('No current stock batches');
        $this->assertCount(0, $this->cards($stalePage));
        $stalePage->assertSee($this->storageUrl());

        $this->get($this->storageUrl(['page' => 2, 'expiration_date' => '2026-10-02']))
            ->assertOk()->assertViewHas('filteredUnits', 1)
            ->assertSee('No batches on this page')
            ->assertDontSee('No stored batches for this expiry date')
            ->assertSee($this->storageUrl(['expiration_date' => '2026-10-02']));
    }

    private function storageUrl(array $query = []): string
    {
        return route('blood-inventory.storage', [
            'blood_type' => 'B+', 'component' => 'fresh_frozen_plasma', ...$query,
        ]);
    }

    private function stock(array $attributes = []): BloodInventory
    {
        return BloodInventory::create([
            'facility_id' => $this->main->id, 'blood_type' => 'B+', 'component' => 'fresh_frozen_plasma',
            'units_available' => 12, 'expiration_date' => '2026-10-11', 'status' => 'active', ...$attributes,
        ]);
    }

    private function donation(string $reference): DonationRecord
    {
        $donor = Donor::create([
            'facility_id' => $this->main->id, 'first_name' => 'Storage', 'last_name' => 'Test Donor',
            'birth_date' => '1995-01-01', 'sex' => 'male', 'blood_type' => 'B+',
        ]);

        return DonationRecord::create([
            'facility_id' => $this->main->id, 'donor_id' => $donor->id, 'recorded_by' => $this->bbs->id,
            'donation_no' => $reference, 'donated_at' => now()->subDay(), 'blood_type' => 'B+',
            'volume_ml' => 450, 'expiration_date' => '2026-10-11', 'status' => 'verified',
        ]);
    }

    private function assertBatchIds(TestResponse $response, array $ids): void
    {
        $this->assertSame($ids, $response->viewData('batches')->getCollection()->pluck('id')->all());
    }

    private function expiryGroups(TestResponse $response): array
    {
        return $response->viewData('expiryGroups')->mapWithKeys(fn ($group) => [
            $group->expiration_date->toDateString() => [
                'units' => (int) $group->units, 'batch_count' => (int) $group->batch_count,
            ],
        ])->all();
    }

    private function cards(TestResponse $response): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($response->getContent());

            return iterator_to_array((new DOMXPath($document))->query('//article[@data-stock-record-id]'));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function normalizedText(string $text): string
    {
        return preg_replace('/\s+/u', ' ', trim($text));
    }
}
