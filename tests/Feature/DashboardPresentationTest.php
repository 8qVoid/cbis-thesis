<?php

namespace Tests\Feature;

use App\Models\BloodReservation;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_member_map_only_receives_public_approved_upcoming_events(): void
    {
        $facility = Facility::create(['code' => 'UI-MAIN', 'name' => 'Test Main', 'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true]);
        $donor = User::factory()->create();
        $donor->assignRole('Donor');
        foreach (['approved', 'pending', 'rejected'] as $status) {
            DonationSchedule::create([
                'facility_id' => $facility->id, 'title' => "UI {$status} event", 'event_type' => 'blood_donation',
                'event_date' => today()->addDays(2), 'start_time' => '08:00', 'end_time' => '12:00',
                'start_at' => today()->addDays(2)->setTime(8, 0), 'end_at' => today()->addDays(2)->setTime(12, 0),
                'venue' => 'Test venue', 'latitude' => 10.67, 'longitude' => 122.95,
                'is_public' => true, 'approval_status' => $status, 'status' => 'planned',
            ]);
        }
        $this->actingAs($donor)->get(route('account.dashboard'))->assertOk()
            ->assertSee('UI approved event')->assertDontSee('UI pending event')->assertDontSee('UI rejected event')
            ->assertSee('dashboard-event-map');
        $patient = User::factory()->create();
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get(route('account.dashboard'))->assertOk()
            ->assertSee('My Blood Requests')->assertDontSee('dashboard-event-map')->assertDontSee('My Donation Status');
    }

    public function test_request_totals_are_not_limited_by_preview_and_qao_has_no_private_queue(): void
    {
        $facility = Facility::create(['code' => 'UI-MAIN', 'name' => 'Test Main', 'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true]);
        $staff = User::factory()->create(['facility_id' => $facility->id]);
        $staff->assignRole('Blood Bank Staff');
        $patient = User::factory()->create();
        for ($i = 1; $i <= 8; $i++) {
            BloodReservation::create([
                'reference' => "UI-{$i}", 'patient_user_id' => $patient->id, 'facility_id' => $facility->id,
                'blood_type' => 'O+', 'component' => 'whole_blood', 'units_requested' => 1,
                'needed_on' => today()->addDay(), 'status' => 'submitted',
            ]);
        }
        $this->actingAs($staff)->get(route('dashboard'))->assertOk()->assertViewHas('pendingRequestCount', 8)
            ->assertViewHas('reservationQueue', fn ($queue) => $queue->count() === 6);
        $qao = User::factory()->create();
        $qao->assignRole('Quality Assurance Officer');
        $this->actingAs($qao)->get(route('dashboard'))->assertOk()->assertViewHas('submittedRequestCount', 8)
            ->assertViewHas('reservationQueue', fn ($queue) => $queue->isEmpty());
    }

    public function test_profile_edit_updates_shared_identity_without_changing_services(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Donor');
        $donor = Donor::create([
            'user_id' => $user->id, 'first_name' => 'Demo', 'last_name' => 'User',
            'birth_date' => '1995-01-01', 'sex' => 'female', 'blood_type' => 'A+',
            'contact_number' => '+639171234567', 'address' => 'Bacolod', 'is_eligible' => false,
        ]);
        $this->actingAs($user)->put(route('account.details.update'), [
            'first_name' => 'Updated', 'middle_name' => null, 'last_name' => 'User', 'address' => 'New address',
            'services' => ['patient'], 'is_eligible' => true,
        ])->assertRedirect(route('account.details.edit'));
        $this->assertSame('Updated User', $user->fresh()->name);
        $this->assertSame('Updated', $donor->fresh()->first_name);
        $this->assertFalse($donor->fresh()->is_eligible);
        $this->assertTrue($user->fresh()->hasRole('Donor'));
        $this->assertFalse($user->fresh()->hasRole('Patient'));
    }

    public function test_enabling_patient_service_keeps_donor_identity_and_continues_to_request(): void
    {
        $user = User::factory()->create(['first_name' => 'Demo', 'last_name' => 'Donor', 'birth_date' => '1995-01-01', 'sex' => 'female', 'phone' => '+639171234567', 'address' => 'Bacolod']);
        $accountCount = User::count();
        $user->assignRole('Donor');
        $this->actingAs($user)->get(route('account.profile.edit', ['service' => 'patient']))->assertOk()->assertSee('Enable Patient Services');
        $this->assertFalse($user->fresh()->hasRole('Patient'));
        $this->actingAs($user)->put(route('account.profile.update'), [
            'services' => ['donor', 'patient'], 'blood_type' => 'A+', 'continue_to' => 'patient',
        ])->assertRedirect(route('reservations.create'));
        $this->assertTrue($user->fresh()->hasAllRoles(['Donor', 'Patient']));
        $this->assertDatabaseCount('users', $accountCount);
        $this->assertDatabaseCount('donors', 1);
        $this->actingAs($user)->put(route('account.profile.update'), [
            'services' => ['donor', 'patient'], 'blood_type' => 'A+', 'continue_to' => 'patient',
        ])->assertRedirect(route('reservations.create'));
        $this->assertDatabaseCount('donors', 1);
        $this->assertDatabaseCount('patient_profiles', 1);
    }
}
