<?php

namespace Tests\Feature;

use App\Models\DonationSchedule;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_account_filters_distinguish_staff_donors_patients_and_combined_accounts(): void
    {
        $qao = User::factory()->create(); $qao->assignRole('Quality Assurance Officer');
        $donor = User::factory()->create(['name' => 'Only Donor']); $donor->assignRole('Donor');
        $patient = User::factory()->create(['name' => 'Only Patient']); $patient->assignRole('Patient');
        $both = User::factory()->create(['name' => 'Combined Person', 'is_active' => false]); $both->assignRole(['Donor', 'Patient']);
        $this->actingAs($qao)->get(route('staff-users.index', ['category' => 'staff']))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->contains('id', $qao->id) && $users->every(fn ($user) => $user->hasAnyRole(['Quality Assurance Officer', 'Blood Bank Staff', 'Event Facilitator'])));
        $this->get(route('staff-users.index', ['category' => 'public', 'role' => 'both', 'status' => 'inactive', 'q' => 'COMBINED']))->assertOk()
            ->assertSee('User Management')->assertSee('Patient/Donor')
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$both->id]);
        $this->get(route('staff-users.index', ['role' => 'donor']))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$donor->id]);
        $this->get(route('staff-users.index', ['q' => "' OR 1=1 --"]))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 0);
        $this->actingAs($donor)->get(route('staff-users.index'))->assertForbidden();
    }

    public function test_account_search_and_facility_filter_do_not_expand_facility_scope(): void
    {
        $facility = Facility::create(['code' => 'F1', 'name' => 'Branch 1', 'type' => 'blood_bank', 'is_active' => true]);
        $other = Facility::create(['code' => 'F2', 'name' => 'Branch 2', 'type' => 'blood_bank', 'is_active' => true]);
        $manager = User::factory()->create(['facility_id' => $facility->id]);
        $manager->assignRole('Event Facilitator'); $manager->givePermissionTo('manage users');
        $outside = User::factory()->create(['facility_id' => $other->id, 'name' => 'Outside Account']);
        $this->actingAs($manager)->get(route('staff-users.index', ['q' => $outside->email]))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 0);
        $this->get(route('staff-users.index', ['facility_id' => $other->id]))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 0);
    }

    public function test_event_cards_filter_by_approval_and_search_without_leaking_other_facilities(): void
    {
        $main = Facility::create(['code' => 'MAIN', 'name' => 'Main', 'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true]);
        $branch = Facility::create(['code' => 'BRANCH', 'name' => 'Branch', 'type' => 'blood_bank', 'is_active' => true]);
        $qao = User::factory()->create(); $qao->assignRole('Quality Assurance Officer');
        $payload = ['event_type' => 'blood_donation', 'event_date' => now()->addWeek(), 'start_at' => now()->addWeek()->setTime(9, 0), 'end_at' => now()->addWeek()->setTime(12, 0), 'start_time' => '09:00', 'end_time' => '12:00', 'venue' => 'Town Hall', 'status' => 'planned'];
        $approved = DonationSchedule::create([...$payload, 'title' => 'Approved Drive', 'facility_id' => $main->id, 'approval_status' => 'approved']);
        DonationSchedule::create([...$payload, 'title' => 'Pending Drive', 'facility_id' => $branch->id, 'approval_status' => 'pending']);
        $this->actingAs($qao)->get(route('donation-schedules.index', ['q' => 'DRIVE', 'approval_status' => 'approved']))->assertOk()
            ->assertSee('More actions')->assertSee('View event')->assertSee('cbis-event-card', false)
            ->assertViewHas('schedules', fn ($items) => $items->pluck('id')->all() === [$approved->id]);
        $facilitator = User::factory()->create(['facility_id' => $branch->id]); $facilitator->assignRole('Event Facilitator');
        $this->actingAs($facilitator)->get(route('donation-schedules.index', ['q' => 'Approved Drive', 'facility_id' => $main->id]))->assertOk()
            ->assertViewHas('schedules', fn ($items) => $items->total() === 0);
    }
}
