<?php

namespace Tests\Feature;

use App\Models\DonationRecord;
use App\Models\Donor;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorDonationHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_sees_only_their_own_donations_and_patient_history_link(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $facility = Facility::create(['code' => 'HISTORY', 'name' => 'Bacolod Chapter', 'type' => 'blood_bank', 'is_active' => true]);
        $donorUser = User::factory()->create();
        $donorUser->assignRole(['Donor', 'Patient']);
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Donor');
        $donor = Donor::create(['user_id' => $donorUser->id, 'first_name' => 'Ari', 'last_name' => 'Reyes', 'birth_date' => '1990-01-01', 'sex' => 'female', 'blood_type' => 'O+', 'is_eligible' => true]);
        $other = Donor::create(['user_id' => $otherUser->id, 'first_name' => 'Bea', 'last_name' => 'Cruz', 'birth_date' => '1991-01-01', 'sex' => 'female', 'blood_type' => 'A+', 'is_eligible' => true]);
        foreach ([[$donor, 'OWN-DONATION'], [$other, 'OTHER-DONATION']] as [$profile, $number]) {
            DonationRecord::create(['facility_id' => $facility->id, 'donor_id' => $profile->id, 'recorded_by' => $otherUser->id, 'donation_no' => $number, 'donated_at' => now(), 'blood_type' => $profile->blood_type, 'volume_ml' => 450, 'expiration_date' => today()->addDays(35), 'expiration_time' => '12:00', 'status' => 'verified']);
        }

        $this->actingAs($donorUser)->get(route('donor.history'))->assertOk()
            ->assertSee('OWN-DONATION')->assertDontSee('OTHER-DONATION')
            ->assertSee('Bacolod Chapter');
        $this->get(route('account.dashboard', ['view' => 'both']))->assertOk()
            ->assertSee('View donation history')->assertSee('View blood request history');

        $patient = User::factory()->create();
        $patient->assignRole('Patient');
        $this->actingAs($patient)->get(route('donor.history'))->assertForbidden();
    }
}
