<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InventoryExpirationTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiration_time_controls_stock_visibility_and_flagging(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 12:00:00', 'Asia/Manila'));
        $this->seed(RolePermissionSeeder::class);
        $facility = Facility::create(['code' => 'TIME', 'name' => 'Bacolod Main', 'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true]);
        $staff = User::factory()->create(['facility_id' => $facility->id, 'is_active' => true]);
        $staff->assignRole('Blood Bank Staff');

        $data = ['blood_type' => 'A+', 'component' => 'whole_blood', 'units_available' => 2,
            'expiration_date' => '2026-11-17', 'expiration_time' => '13:00', 'status' => 'active'];
        $this->actingAs($staff)->post(route('blood-inventory.store'), $data)->assertSessionHasNoErrors();
        $stock = BloodInventory::firstOrFail();
        $this->assertStringStartsWith('13:00', $stock->expiration_time);
        $this->get(route('blood-inventory.index'))->assertViewHas('totalAvailableUnits', 2);

        $originalTimezone = date_default_timezone_get();
        try {
            date_default_timezone_set('Pacific/Honolulu');
            $this->assertFalse($stock->fresh()->isExpired());
            $this->get(route('blood-inventory.index'))->assertViewHas('totalAvailableUnits', 2);
        } finally {
            date_default_timezone_set($originalTimezone);
        }

        $this->post(route('blood-inventory.store'), [...$data, 'expiration_time' => '11:00'])
            ->assertSessionHasErrors('expiration_date');
        $withoutTime = $data;
        unset($withoutTime['expiration_time']);
        $this->post(route('blood-inventory.store'), $withoutTime)->assertSessionHasErrors('expiration_time');

        $this->travelTo(Carbon::parse('2026-11-17 13:01:00', 'Asia/Manila'));
        try {
            date_default_timezone_set('Pacific/Honolulu');
            $this->assertTrue($stock->fresh()->isExpired());
            $this->get(route('blood-inventory.index'))->assertViewHas('totalAvailableUnits', 0);
            $this->get(route('blood-releases.create'))->assertDontSee('value="'.$stock->id.'"', false);
            $this->post(route('blood-releases.store'), [
                'blood_inventory_id' => $stock->id, 'units_released' => 1,
                'released_at' => now()->toDateTimeString(), 'patient_name' => 'Expiry check',
            ])->assertSessionHasErrors('blood_inventory_id');
        } finally {
            date_default_timezone_set($originalTimezone);
        }
        $this->artisan('inventory:flag-expired')->assertExitCode(0);
        $this->assertSame('expired', $stock->fresh()->status);
    }
}
