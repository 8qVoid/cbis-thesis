<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminFacilityRecordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_created_inventory_is_visible_to_facility_medical_staff(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $facility = Facility::create([
            'code' => 'BB-900',
            'name' => 'Visible Blood Bank',
            'type' => 'blood_bank',
            'is_active' => true,
        ]);

        $superAdmin = User::where('email', 'admin@cbis.local')->firstOrFail();
        $superAdmin->givePermissionTo('manage inventory');

        $this->actingAs($superAdmin)->post(route('blood-inventory.store'), [
            'facility_id' => $facility->id,
            'blood_type' => 'O+',
            'units_available' => 7,
            'expiration_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ])->assertRedirect(route('blood-inventory.index'));

        $this->assertDatabaseHas('blood_inventory', [
            'facility_id' => $facility->id,
            'blood_type' => 'O+',
            'units_available' => 7,
        ]);

        $medicalStaff = User::factory()->create(['facility_id' => $facility->id]);
        $medicalStaff->assignRole('Medical Staff / Nurse');

        $this->actingAs($medicalStaff)
            ->get(route('blood-inventory.index'))
            ->assertOk()
            ->assertSee('O+')
            ->assertSee('7');
    }
}
