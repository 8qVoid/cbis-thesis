<?php

namespace Tests\Feature;

use App\Models\BloodReservation;
use App\Models\DonationSchedule;
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
}
