<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\Facility;
use App\Models\PatientProfile;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSurfaceAuditTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->facility = Facility::create([
            'code' => 'ROLE-AUDIT', 'name' => 'Red Cross Role Audit',
            'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true,
        ]);
    }

    public function test_qao_visible_pages_render_and_operational_pages_are_blocked(): void
    {
        $qao = $this->staff('Quality Assurance Officer', null);
        $this->assertPagesOk($qao, [
            'dashboard', 'donors.index', 'blood-inventory.index', 'blood-releases.index',
            'reservations.index', 'donation-schedules.index', 'blood-bank-locations.index',
            'blood-bank-locations.create', 'donation-schedules.create', 'reports.index', 'reports.pdf', 'reports.excel',
            'notifications.index', 'facilities.index', 'staff-users.index', 'staff-users.create',
        ]);
        $this->assertPagesForbidden($qao, [
            'donation-records.index', 'bloodletting-records.index', 'blood-inventory.create',
            'blood-releases.create', 'reservations.create',
        ]);
    }

    public function test_qao_can_create_bbs_and_event_facilitator_accounts(): void
    {
        $qao = $this->staff('Quality Assurance Officer', null);

        foreach ([
            ['name' => 'Branch BBS', 'email' => 'bbs.audit@example.test', 'role' => 'Blood Bank Staff'],
            ['name' => 'Branch Facilitator', 'email' => 'facilitator.audit@example.test', 'role' => 'Event Facilitator'],
        ] as $account) {
            $this->actingAs($qao)->post(route('staff-users.store'), [
                ...$account, 'facility_id' => $this->facility->id,
                'password' => 'password123', 'password_confirmation' => 'password123',
            ])->assertRedirect(route('staff-users.index'));

            $created = User::where('email', $account['email'])->firstOrFail();
            $this->assertTrue($created->hasRole($account['role']));
            $this->assertSame($this->facility->id, $created->facility_id);
        }
    }

    public function test_bbs_pages_render_and_qao_or_facilitator_pages_are_blocked(): void
    {
        $bbs = $this->staff('Blood Bank Staff');
        $this->assertPagesOk($bbs, [
            'dashboard', 'donors.index', 'donors.create', 'donation-records.index',
            'donation-records.create', 'bloodletting-records.index', 'bloodletting-records.create',
            'blood-inventory.index', 'blood-inventory.create', 'blood-releases.index',
            'blood-releases.create', 'reservations.index', 'reports.index', 'notifications.index',
        ]);
        $this->assertPagesForbidden($bbs, [
            'reports.pdf', 'reports.excel', 'donation-schedules.index',
            'blood-bank-locations.index', 'facilities.index', 'staff-users.index',
        ]);
    }

    public function test_event_facilitator_pages_render_and_blood_operations_are_blocked(): void
    {
        $facilitator = $this->staff('Event Facilitator');
        $this->actingAs($facilitator)->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.facilitator')
            ->assertViewMissing('inventoryByType')
            ->assertSee('Activity Dashboard')
            ->assertDontSee('Current Stock by Blood Type');
        $this->assertPagesOk($facilitator, [
            'dashboard', 'donation-schedules.index', 'donation-schedules.create', 'notifications.index',
        ]);
        $this->assertPagesForbidden($facilitator, [
            'donors.index', 'donation-records.index', 'bloodletting-records.index',
            'blood-inventory.index', 'blood-releases.index', 'reservations.index',
            'reports.index', 'blood-bank-locations.index', 'facilities.index', 'staff-users.index',
        ]);
    }

    public function test_donor_and_patient_pages_are_separated_but_work_in_one_account(): void
    {
        $donor = $this->publicUser(['Donor']);
        Donor::create([
            'user_id' => $donor->id, 'first_name' => 'Dina', 'last_name' => 'Donor',
            'birth_date' => '1995-01-01', 'sex' => 'female', 'blood_type' => 'O+',
            'contact_number' => '+639171234567', 'address' => 'Bacolod',
            'is_online_registered' => true, 'is_eligible' => false,
        ]);
        $this->assertPagesOk($donor, ['account.dashboard', 'account.profile.edit', 'donor.events.index']);
        $this->actingAs($donor)->get(route('reservations.create'))->assertForbidden();
        $this->actingAs($donor)->get(route('dashboard'))->assertForbidden();

        $patient = $this->publicUser(['Patient']);
        PatientProfile::create(['user_id' => $patient->id]);
        $this->assertPagesOk($patient, ['account.dashboard', 'account.profile.edit', 'reservations.index', 'reservations.create']);
        $this->actingAs($patient)->get(route('donor.events.index'))->assertForbidden();
        $this->actingAs($patient)->get(route('dashboard'))->assertForbidden();
    }

    private function assertPagesOk(User $user, array $routes): void
    {
        foreach ($routes as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk("{$user->getRoleNames()->join(', ')} cannot open {$routeName}");
        }
    }

    private function assertPagesForbidden(User $user, array $routes): void
    {
        foreach ($routes as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertForbidden("{$user->getRoleNames()->join(', ')} unexpectedly opened {$routeName}");
        }
    }

    private function staff(string $role, ?int $facilityId = -1): User
    {
        $user = User::factory()->create([
            'facility_id' => $facilityId === -1 ? $this->facility->id : $facilityId,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function publicUser(array $roles): User
    {
        $user = User::factory()->create(['facility_id' => null, 'is_active' => true]);
        $user->assignRole($roles);

        return $user;
    }
}
