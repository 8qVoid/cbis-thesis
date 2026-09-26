<?php

namespace Tests\Feature;

use App\Events\BloodReleased;
use App\Livewire\DonationRecords\CreateDonationRecord;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\BloodReservation;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\EventRegistration;
use App\Models\Facility;
use App\Models\FacilityApplication;
use App\Models\User;
use App\Notifications\BloodReservationStatusChanged;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FacilitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $bbs;

    private Facility $main;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->main = Facility::create(['code' => 'AUDIT', 'name' => 'Audit Main', 'type' => 'blood_bank', 'is_main_chapter' => true, 'is_active' => true]);
        $this->bbs = User::factory()->create(['facility_id' => $this->main->id, 'is_active' => true]);
        $this->bbs->assignRole('Blood Bank Staff');
    }

    public function test_main_staff_can_find_online_donors_without_a_facility(): void
    {
        $donor = Donor::create(['first_name' => 'Online', 'last_name' => 'Audit', 'birth_date' => '2000-01-01', 'sex' => 'male', 'blood_type' => 'A+', 'is_online_registered' => true]);
        $this->actingAs($this->bbs)->get(route('donors.index'))->assertOk()->assertSee('Audit, Online');
        $this->get(route('donors.edit', $donor))->assertOk();
    }

    public function test_inventory_component_links_filter_the_results(): void
    {
        $this->stock('whole_blood');
        $platelets = $this->stock('platelet_concentrate');
        $this->actingAs($this->bbs)->get(route('blood-inventory.index', ['component' => 'platelet_concentrate']))
            ->assertOk()->assertViewHas('inventory', fn ($items) => $items->count() === 1 && $items->first()->id === $platelets->id);
    }

    public function test_manual_stock_rejects_duplicate_and_active_past_expiry(): void
    {
        $stock = $this->stock('whole_blood');
        $data = $stock->only(['blood_type', 'component', 'units_available', 'status']);
        $data['expiration_date'] = $stock->expiration_date->toDateString();
        $this->actingAs($this->bbs)->post(route('blood-inventory.store'), $data)
            ->assertSessionHasErrors('expiration_date');
        $this->assertDatabaseCount('blood_inventory', 1);
        $data['expiration_date'] = today()->subYear()->toDateString();
        $this->put(route('blood-inventory.update', $stock), $data)->assertSessionHasErrors('expiration_date');
        $this->assertTrue($stock->fresh()->expiration_date->isFuture());
        $data['status'] = 'expired';
        $this->put(route('blood-inventory.update', $stock), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('blood-inventory.index'))->assertOk()
            ->assertViewHas('totalAvailableUnits', 0);
    }

    public function test_manual_balance_changes_are_visible_as_stock_in_and_out(): void
    {
        $data = ['blood_type' => 'B+', 'component' => 'whole_blood', 'units_available' => 8,
            'status' => 'active', 'expiration_date' => today()->addDays(10)->toDateString()];
        $this->actingAs($this->bbs)->post(route('blood-inventory.store'), $data)->assertSessionHasNoErrors();
        $stock = BloodInventory::firstOrFail();
        $data['units_available'] = 5;
        $this->put(route('blood-inventory.update', $stock), $data)->assertSessionHasNoErrors();
        $this->get(route('blood-inventory.index'))->assertOk()->assertViewHas('stockMovements', function ($movements) {
            return $movements->contains(fn ($m) => $m['type'] === 'in' && $m['units'] === 8)
                && $movements->contains(fn ($m) => $m['type'] === 'out' && $m['units'] === 3);
        });
    }

    public function test_donation_stock_in_keeps_original_quantity_after_release(): void
    {
        $donor = Donor::create(['first_name' => 'Stock', 'last_name' => 'Test', 'birth_date' => '2000-01-01', 'sex' => 'male', 'blood_type' => 'A+']);
        $donation = \App\Models\DonationRecord::create(['facility_id' => $this->main->id, 'donor_id' => $donor->id,
            'recorded_by' => $this->bbs->id, 'donation_no' => 'MOVEMENT-TEST', 'donated_at' => now(),
            'blood_type' => 'A+', 'volume_ml' => 450, 'expiration_date' => today()->addDays(5), 'status' => 'verified']);
        $stock = $this->stock('whole_blood');
        $stock->update(['donation_record_id' => $donation->id, 'units_available' => 0]);
        BloodRelease::create(['facility_id' => $this->main->id, 'blood_inventory_id' => $stock->id,
            'released_by' => $this->bbs->id, 'units_released' => 1, 'released_at' => now(), 'patient_name' => 'Test Patient']);
        $this->actingAs($this->bbs)->get(route('blood-inventory.index'))->assertOk()
            ->assertViewHas('stockMovements', fn ($items) => $items->contains(fn ($m) => $m['type'] === 'in' && $m['units'] === 1)
                && $items->contains(fn ($m) => $m['type'] === 'out' && $m['units'] === 1));
        $this->post(route('blood-inventory.store'), ['donation_record_id' => $donation->id, 'blood_type' => 'A+',
            'component' => 'whole_blood', 'units_available' => 1, 'status' => 'active', 'expiration_date' => today()->addDays(5)->toDateString()])
            ->assertSessionHasErrors('donation_record_id');
        $this->assertDatabaseCount('blood_inventory', 1);
        $this->get(route('blood-inventory.edit', $stock))->assertOk()->assertSee('Correct the unit balance');
        $this->get(route('blood-inventory.create'))->assertOk()->assertSee('Available stock must expire today or later');
    }

    public function test_stock_filters_match_the_selected_blood_type_and_exclude_unusable_low_stock(): void
    {
        $matching = $this->stock('whole_blood');
        $other = $this->stock('whole_blood'); $other->update(['blood_type' => 'O+']);
        $empty = $this->stock('whole_blood'); $empty->update(['units_available' => 0]);
        $expired = $this->stock('whole_blood'); $expired->update(['expiration_date' => today()->subDay()]);
        $this->actingAs($this->bbs)->get(route('blood-inventory.index', ['blood_type' => 'A+', 'component' => 'whole_blood', 'status' => 'low_stock']))
            ->assertOk()->assertViewHas('inventory', fn ($items) => $items->count() === 1 && $items->first()->id === $matching->id);
    }

    public function test_registered_donor_is_sent_to_existing_event_registration(): void
    {
        $user = User::factory()->create(['facility_id' => null, 'is_active' => true]);
        $user->assignRole('Donor');
        $donor = Donor::create([
            'user_id' => $user->id,
            'first_name' => 'Repeat',
            'last_name' => 'Registrant',
            'birth_date' => '2000-01-01',
            'sex' => 'male',
            'blood_type' => 'A+',
        ]);
        $event = DonationSchedule::create([
            'facility_id' => $this->main->id,
            'title' => 'Repeat Registration Drive',
            'event_type' => 'blood_donation',
            'event_date' => today()->addDays(7),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'start_at' => today()->addDays(7)->setTime(8, 0),
            'end_at' => today()->addDays(7)->setTime(12, 0),
            'venue' => 'Audit Hall',
            'is_public' => true,
            'approval_status' => 'approved',
            'status' => 'planned',
        ]);
        EventRegistration::create([
            'donation_schedule_id' => $event->id,
            'donor_id' => $donor->id,
            'facility_id' => $this->main->id,
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('donor.events.join', $event))
            ->assertRedirect(route('donor.events.index'))
            ->assertSessionHas('success', 'You are already registered for this event. Your registration is shown below.');

        $this->get(route('public.map'))
            ->assertOk()
            ->assertSee('Already Registered')
            ->assertSee('Show registration');
    }

    public function test_inactive_accounts_cannot_keep_using_an_existing_session(): void
    {
        $patient = User::factory()->create(['facility_id' => null, 'is_active' => true]);
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get(route('account.dashboard'))->assertOk();
        $patient->update(['is_active' => false]);
        $this->get(route('account.dashboard'))->assertForbidden();
        $this->get(route('reservations.index'))->assertForbidden();
        $this->post(route('logout'))->assertRedirect();
    }

    public function test_release_rechecks_stock_and_rolls_back_failed_transactions(): void
    {
        $stock = $this->stock('whole_blood');
        $stock->update(['units_available' => 1]);
        try {
            DB::transaction(function () use ($stock) {
                $release = BloodRelease::create(['facility_id' => $this->main->id, 'blood_inventory_id' => $stock->id, 'released_by' => $this->bbs->id, 'units_released' => 2, 'released_at' => now()]);
                event(new BloodReleased($release));
            });
            $this->fail('A stale release must not exceed current stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('units_released', $exception->errors());
        }
        $this->assertDatabaseCount('blood_releases', 0);
        $this->assertSame(1, $stock->fresh()->units_available);
    }

    private function stock(string $component): BloodInventory
    {
        return BloodInventory::create(['facility_id' => $this->main->id, 'blood_type' => 'A+', 'component' => $component, 'units_available' => 2, 'expiration_date' => today()->addDays(4), 'status' => 'active']);
    }

    public function test_redeploy_seeding_does_not_reactivate_disabled_or_deleted_demo_accounts(): void
    {
        $this->seed(FacilitySeeder::class);
        $disabled = User::where('email', 'bbs@gmail.com')->firstOrFail();
        $disabled->update(['is_active' => false]);
        $deleted = User::where('email', 'facilitator@gmail.com')->firstOrFail();
        $deleted->delete();
        $this->seed(DatabaseSeeder::class);
        $this->assertFalse($disabled->fresh()->is_active);
        $this->assertTrue(User::withTrashed()->findOrFail($deleted->id)->trashed());
    }

    public function test_branch_approval_cannot_replace_a_patient_account(): void
    {
        $patient = User::factory()->create(['facility_id' => null]);
        $patient->assignRole('Patient');
        $application = FacilityApplication::create([
            'organization_name' => 'Test Branch', 'facility_type' => 'blood_bank', 'contact_person' => 'Test Representative',
            'contact_number' => '09171234567', 'email' => $patient->email, 'address' => 'Test address',
            'legitimacy_proof_path' => 'test.pdf', 'doh_accreditation_proof_path' => 'test.pdf',
        ]);
        $qao = User::where('email', 'qao@gmail.com')->firstOrFail();
        $this->actingAs($qao)->put(route('facility-applications.review', $application), ['status' => 'approved'])->assertSessionHasErrors('status');
        $this->assertTrue($patient->fresh()->hasRole('Patient'));
        $this->assertNull($patient->fresh()->facility_id);
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_screening_is_staff_only_and_updates_the_donor_dashboard_and_notifications(): void
    {
        $user = User::factory()->create(['facility_id' => null, 'is_active' => true]);
        $user->assignRole('Donor');
        $donor = Donor::create(['user_id' => $user->id, 'first_name' => 'Screen', 'last_name' => 'Audit', 'birth_date' => '2000-01-01', 'sex' => 'male', 'blood_type' => 'A+']);
        $payload = ['status' => 'eligible', 'screening_confirmed' => '1'];
        $this->actingAs($user)->patch(route('donors.screening', $donor), $payload)->assertForbidden();
        $qao = User::where('email', 'qao@gmail.com')->firstOrFail();
        $this->actingAs($qao)->patch(route('donors.screening', $donor), $payload)->assertForbidden();
        $this->actingAs($this->bbs)->patch(route('donors.screening', $donor), $payload)->assertSessionHasNoErrors();
        $this->assertTrue($donor->fresh()->is_eligible);
        $this->actingAs($user)->get(route('account.dashboard'))->assertOk()->assertSee('Eligible at last screening');
        $this->get(route('notifications.index', ['type' => 'screening']))->assertOk()->assertSee('Donation screening updated');
        $this->actingAs($this->bbs)->patch(route('donors.screening', $donor), ['status' => 'deferred', 'screening_confirmed' => '1'])->assertSessionHasErrors('donor_message');
        $this->patch(route('donors.screening', $donor), ['status' => 'deferred', 'screening_confirmed' => '1', 'donor_message' => 'Please return for reassessment.', 'review_on' => today()->addWeek()->toDateString()])->assertSessionHasNoErrors();
        $this->assertFalse($donor->fresh()->is_eligible);
        $this->assertSame(2, $donor->screenings()->count());
        $this->actingAs($user)->get(route('account.dashboard'))->assertSee('Deferred')->assertSee('Please return for reassessment.');
    }

    public function test_bbs_cannot_mark_an_underage_donor_eligible(): void
    {
        $donor = Donor::create([
            'first_name' => 'Underage', 'last_name' => 'Donor',
            'birth_date' => today()->subYears(17)->toDateString(),
            'sex' => 'male', 'blood_type' => 'A+', 'is_eligible' => false,
        ]);

        $this->actingAs($this->bbs)->patch(route('donors.screening', $donor), [
            'status' => 'eligible',
            'screening_confirmed' => '1',
        ])->assertSessionHasErrors('status');

        $this->assertFalse($donor->fresh()->is_eligible);
        $this->assertDatabaseCount('donor_screenings', 0);
    }

    public function test_bbs_cannot_record_a_donation_for_an_underage_donor(): void
    {
        $donor = Donor::create([
            'facility_id' => $this->main->id, 'first_name' => 'Underage', 'last_name' => 'Donor',
            'birth_date' => today()->subYears(17)->toDateString(),
            'sex' => 'male', 'blood_type' => 'A+', 'is_eligible' => false,
        ]);

        $this->actingAs($this->bbs)->post(route('donation-records.store'), [
            'facility_id' => $this->main->id,
            'donor_id' => $donor->id,
            'donation_no' => 'DN-UNDERAGE',
            'donated_at' => now()->format('Y-m-d H:i:s'),
            'blood_type' => 'A+',
            'volume_ml' => 450,
            'expiration_date' => today()->addDays(30)->toDateString(),
            'status' => 'verified',
        ])->assertSessionHasErrors('donor_id');

        $this->assertDatabaseCount('donation_records', 0);
        $this->assertDatabaseCount('blood_inventory', 0);
    }

    public function test_reservation_fulfillment_requires_matching_recorded_releases(): void
    {
        Notification::fake();
        $patient = User::factory()->create(['facility_id' => null]);
        $patient->assignRole('Patient');
        $reservation = BloodReservation::create(['reference' => 'BR-FLOW', 'patient_user_id' => $patient->id, 'facility_id' => $this->main->id, 'blood_type' => 'A+', 'component' => 'whole_blood', 'units_requested' => 2, 'needed_on' => today(), 'status' => 'approved']);
        $stock = $this->stock('whole_blood');
        $payload = ['blood_inventory_id' => $stock->id, 'units_released' => 1, 'released_at' => now()->toDateTimeString()];
        $this->actingAs($this->bbs)->patch(route('reservations.review', $reservation), ['status' => 'fulfilled'])->assertSessionHasErrors('status');
        $this->post(route('blood-releases.store'), $payload)->assertSessionHasErrors('units_released');
        $this->post(route('blood-releases.store'), [...$payload, 'blood_reservation_id' => $reservation->id])->assertSessionHasNoErrors();
        $this->assertSame(1, $stock->fresh()->units_available);
        $this->assertSame('approved', $reservation->fresh()->status);
        $this->post(route('blood-releases.store'), [...$payload, 'blood_reservation_id' => $reservation->id])->assertSessionHasNoErrors();
        $this->assertSame(0, $stock->fresh()->units_available);
        $this->assertSame('fulfilled', $reservation->fresh()->status);
        $this->assertSame(2, (int) $reservation->releases()->sum('units_released'));
        Notification::assertSentTo($patient, BloodReservationStatusChanged::class);
        $this->post(route('blood-releases.store'), [...$payload, 'blood_reservation_id' => $reservation->id])->assertSessionHasErrors();
        $this->assertDatabaseCount('blood_releases', 2);
    }

    public function test_main_staff_can_record_a_branch_event_donation_without_moving_stock_to_the_branch(): void
    {
        $branch = Facility::create(['code' => 'BR-AUDIT', 'name' => 'Audit Branch', 'type' => 'blood_bank', 'is_active' => true]);
        $event = DonationSchedule::create(['facility_id' => $branch->id, 'title' => 'Branch Drive', 'event_type' => 'blood_donation', 'event_date' => today(), 'start_time' => '08:00', 'end_time' => '23:59', 'start_at' => today()->setTime(8, 0), 'end_at' => today()->endOfDay(), 'venue' => 'Test Hall', 'is_public' => true, 'approval_status' => 'approved', 'status' => 'ongoing']);
        $donor = Donor::create(['first_name' => 'Branch', 'last_name' => 'Donor', 'birth_date' => '2000-01-01', 'sex' => 'male', 'blood_type' => 'A+']);
        $registration = EventRegistration::create(['facility_id' => $branch->id, 'donation_schedule_id' => $event->id, 'donor_id' => $donor->id, 'status' => 'registered', 'registered_at' => now()]);
        Livewire::actingAs($this->bbs)->test(CreateDonationRecord::class)
            ->set('selected_event_id', $event->id)->set('donor_id', $donor->id)
            ->set('donated_at', now()->toDateTimeString())->set('volume_ml', 450)
            ->set('expiration_date', today()->addDays(30)->toDateString())->set('status', 'verified')
            ->call('save')->assertHasNoErrors()->assertRedirect(route('donation-records.index'));
        $this->assertSame('attended', $registration->fresh()->status);
        $this->assertSame($this->main->id, BloodInventory::firstOrFail()->facility_id);
    }
}
