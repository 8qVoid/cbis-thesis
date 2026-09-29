<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_bbs_can_update_own_profile_without_rewriting_earlier_audit_identity(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $facility = Facility::create(['code' => 'MAIN', 'name' => 'Bacolod Main', 'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true]);
        $staff = User::factory()->create(['name' => 'Original Staff', 'email' => 'original@example.test', 'phone' => '+639171234567', 'facility_id' => $facility->id]);
        $staff->assignRole('Blood Bank Staff');

        $this->actingAs($staff)->post(route('blood-inventory.store'), [
            'blood_type' => 'O+', 'component' => 'whole_blood', 'units_available' => 2,
            'expiration_date' => now()->addMonth()->toDateString(), 'status' => 'active',
        ])->assertRedirect(route('blood-inventory.index'));
        $inventoryLog = AuditLog::where('action', 'blood_inventory.created')->firstOrFail();
        $this->assertSame('Original Staff', $inventoryLog->details['actor_name']);

        $this->get(route('staff-profile.edit'))->assertOk()->assertSee('Original Staff');
        $this->put(route('staff-profile.update'), [
            'name' => 'Corrected Staff', 'email' => 'corrected@example.test',
            'phone' => '912345678', 'facility_id' => 999, 'role' => 'Quality Assurance Officer',
        ])->assertRedirect(route('staff-profile.edit'));

        $staff->refresh();
        $this->assertSame('Corrected Staff', $staff->name);
        $this->assertSame('corrected@example.test', $staff->email);
        $this->assertSame('+639912345678', $staff->phone);
        $this->assertSame($facility->id, $staff->facility_id);
        $this->assertTrue($staff->hasRole('Blood Bank Staff'));
        $this->assertSame('Original Staff', $inventoryLog->fresh()->details['actor_name']);

        $profileLog = AuditLog::where('action', 'staff_profile.updated')->firstOrFail();
        $this->assertSame($staff->id, $profileLog->user_id);
        $this->assertSame('Original Staff', $profileLog->details['changes']['name']['before']);
        $this->assertSame('Corrected Staff', $profileLog->details['changes']['name']['after']);
    }

    public function test_other_roles_cannot_use_the_bbs_profile_form(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $qao = User::factory()->create(['facility_id' => null]);
        $qao->assignRole('Quality Assurance Officer');

        $this->actingAs($qao)->get(route('staff-profile.edit'))->assertForbidden();
        $this->put(route('staff-profile.update'), [
            'name' => 'Changed', 'email' => 'changed@example.test',
        ])->assertForbidden();
    }
}
