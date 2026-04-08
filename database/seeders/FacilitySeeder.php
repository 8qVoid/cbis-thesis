<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::firstOrCreate(
            ['code' => 'FAC-001'],
            [
                'name' => 'City Blood Center',
                'type' => 'blood_bank',
                'contact_person' => 'Facility Admin',
                'contact_number' => '09170000000',
                'email' => 'facility@cbis.local',
                'address' => 'Sample City',
                'is_active' => true,
            ]
        );

        $facilityAdmin = User::firstOrCreate(
            ['email' => 'facility.admin@cbis.local'],
            [
                'name' => 'Facility Admin',
                'password' => Hash::make('password'),
                'facility_id' => $facility->id,
                'is_active' => true,
            ]
        );
        $facilityAdmin->syncRoles(['Facility Admin / Blood Bank Personnel']);

        $medTech = User::firstOrCreate(
            ['email' => 'medtech@cbis.local'],
            [
                'name' => 'Medical Technologist',
                'password' => Hash::make('password'),
                'facility_id' => $facility->id,
                'is_active' => true,
            ]
        );
        $medTech->syncRoles(['Medical Technologist']);
    }
}
