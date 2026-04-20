<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\Facility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBloodAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_blood_availability_page(): void
    {
        $response = $this->get(route('public.availability'));

        $response->assertOk();
        $response->assertSee('Available Bloods');
    }

    public function test_guest_cannot_access_staff_inventory_page(): void
    {
        $response = $this->get('/blood-inventory');

        $response->assertRedirect('/login');
    }

    public function test_public_availability_only_counts_non_expired_positive_inventory_for_active_blood_banks(): void
    {
        $bankOne = Facility::create([
            'code' => 'BB-001',
            'name' => 'Bacolod Blood Bank',
            'type' => 'blood_bank',
            'is_active' => true,
        ]);

        $bankTwo = Facility::create([
            'code' => 'BB-002',
            'name' => 'Silay Blood Bank',
            'type' => 'blood_bank',
            'is_active' => true,
        ]);

        $inactiveBank = Facility::create([
            'code' => 'BB-003',
            'name' => 'Inactive Blood Bank',
            'type' => 'blood_bank',
            'is_active' => false,
        ]);

        $hospital = Facility::create([
            'code' => 'H-001',
            'name' => 'City Hospital',
            'type' => 'hospital',
            'is_active' => true,
        ]);

        BloodInventory::create([
            'facility_id' => $bankOne->id,
            'blood_type' => 'A+',
            'units_available' => 3,
            'expiration_date' => now()->addDays(2)->toDateString(),
            'status' => 'active',
        ]);

        BloodInventory::create([
            'facility_id' => $bankOne->id,
            'blood_type' => 'O-',
            'units_available' => 0,
            'expiration_date' => now()->addDays(2)->toDateString(),
            'status' => 'active',
        ]);

        BloodInventory::create([
            'facility_id' => $bankOne->id,
            'blood_type' => 'B+',
            'units_available' => 2,
            'expiration_date' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        BloodInventory::create([
            'facility_id' => $bankTwo->id,
            'blood_type' => 'A+',
            'units_available' => 1,
            'expiration_date' => now()->addDay()->toDateString(),
            'status' => 'low_stock',
        ]);

        BloodInventory::create([
            'facility_id' => $bankTwo->id,
            'blood_type' => 'AB+',
            'units_available' => 5,
            'expiration_date' => now()->addDay()->toDateString(),
            'status' => 'expired',
        ]);

        BloodInventory::create([
            'facility_id' => $inactiveBank->id,
            'blood_type' => 'A+',
            'units_available' => 5,
            'expiration_date' => now()->addDay()->toDateString(),
            'status' => 'active',
        ]);

        BloodInventory::create([
            'facility_id' => $hospital->id,
            'blood_type' => 'A+',
            'units_available' => 5,
            'expiration_date' => now()->addDay()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->get(route('public.availability'));

        $response->assertOk();
        $response->assertSee('Bacolod Blood Bank');
        $response->assertSee('Silay Blood Bank');
        $response->assertDontSee('Inactive Blood Bank');
        $response->assertDontSee('City Hospital');
        $response->assertSee('A+');
        $response->assertSee('Available');
        $response->assertDontSee('Unavailable');
        $response->assertDontSee('O-');
        $response->assertDontSee('B+');
        $response->assertDontSee('AB+');
    }

    public function test_public_availability_filters_by_facility_and_blood_type(): void
    {
        $bankOne = Facility::create([
            'code' => 'BB-010',
            'name' => 'La Carlota Blood Bank',
            'type' => 'blood_bank',
            'is_active' => true,
        ]);

        $bankTwo = Facility::create([
            'code' => 'BB-011',
            'name' => 'Talisay Blood Bank',
            'type' => 'blood_bank',
            'is_active' => true,
        ]);

        BloodInventory::create([
            'facility_id' => $bankOne->id,
            'blood_type' => 'O+',
            'units_available' => 2,
            'expiration_date' => now()->addDay()->toDateString(),
            'status' => 'active',
        ]);

        BloodInventory::create([
            'facility_id' => $bankTwo->id,
            'blood_type' => 'O+',
            'units_available' => 2,
            'expiration_date' => now()->addDay()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->get(route('public.availability', [
            'facility_id' => $bankOne->id,
            'blood_type' => 'O+',
        ]));

        $response->assertOk();
        $response->assertSee('La Carlota Blood Bank');
        $response->assertDontSee('Talisay Blood Bank');
        $response->assertSee('O+');
        $response->assertDontSee('A+');
    }
}
