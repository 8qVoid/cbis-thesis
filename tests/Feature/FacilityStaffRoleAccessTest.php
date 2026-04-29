<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityStaffRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_facilitator_can_create_facility_staff_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $facility = $this->facility();
        $facilitator = User::factory()->create(['facility_id' => $facility->id]);
        $facilitator->assignRole('Facilitator');

        $response = $this->actingAs($facilitator)->get(route('staff-users.create'));

        $response->assertOk();
        $response->assertSee('Facilitator');
        $response->assertSee('Medical Staff / Nurse');
    }

    public function test_facilitator_can_use_front_desk_modules_but_not_inventory(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $facility = $this->facility();
        $facilitator = User::factory()->create(['facility_id' => $facility->id]);
        $facilitator->assignRole('Facilitator');

        $this->actingAs($facilitator)->get(route('donors.index'))->assertOk();
        $this->actingAs($facilitator)->get(route('donation-records.index'))->assertOk();
        $this->actingAs($facilitator)->get(route('donation-schedules.index'))->assertOk();
        $this->actingAs($facilitator)->get(route('blood-bank-locations.create'))->assertOk();
        $this->actingAs($facilitator)->get(route('notifications.index'))->assertOk();
        $this->actingAs($facilitator)->get(route('reports.index'))->assertOk();
        $this->actingAs($facilitator)->get(route('blood-inventory.index'))->assertForbidden();
    }

    public function test_medical_staff_nurse_can_use_inventory_notifications_and_reports_only(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $facility = $this->facility();
        $medicalStaff = User::factory()->create(['facility_id' => $facility->id]);
        $medicalStaff->assignRole('Medical Staff / Nurse');

        $this->actingAs($medicalStaff)->get(route('blood-inventory.index'))->assertOk();
        $this->actingAs($medicalStaff)->get(route('notifications.index'))->assertOk();
        $this->actingAs($medicalStaff)->get(route('reports.index'))->assertOk();
        $this->actingAs($medicalStaff)->get(route('donors.index'))->assertForbidden();
        $this->actingAs($medicalStaff)->get(route('donation-records.index'))->assertForbidden();
        $this->actingAs($medicalStaff)->get(route('blood-releases.index'))->assertForbidden();
        $this->actingAs($medicalStaff)->get(route('blood-bank-locations.create'))->assertForbidden();
    }

    public function test_super_administrator_can_monitor_and_create_staff_but_not_perform_other_facility_operations(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = User::where('email', 'admin@cbis.local')->firstOrFail();

        $this->actingAs($superAdmin)->get(route('donation-records.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('donation-records.create'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('bloodletting-records.create'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('donors.create'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('donation-schedules.create'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('staff-users.create'))->assertOk();
    }

    public function test_super_administrator_can_create_facility_staff_account(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $facility = $this->facility();
        $superAdmin = User::where('email', 'admin@cbis.local')->firstOrFail();

        $response = $this->actingAs($superAdmin)->post(route('staff-users.store'), [
            'name' => 'Approved Facility Contact',
            'email' => 'approved.facility@example.test',
            'facility_id' => $facility->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Facilitator',
        ]);

        $response->assertRedirect(route('staff-users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'approved.facility@example.test',
            'facility_id' => $facility->id,
            'is_active' => true,
        ]);
    }

    private function facility(): Facility
    {
        return Facility::create([
            'code' => fake()->unique()->bothify('FAC-###'),
            'name' => fake()->company(),
            'type' => 'blood_bank',
            'is_active' => true,
        ]);
    }
}
