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
        $disabled = User::where('email', 'bbs@cbis.local')->firstOrFail();
        $disabled->update(['is_active' => false]);
        $deleted = User::where('email', 'facilitator@cbis.local')->firstOrFail();
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
        $qao = User::where('email', 'qao@cbis.local')->firstOrFail();
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
        $qao = User::where('email', 'qao@cbis.local')->firstOrFail();
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
