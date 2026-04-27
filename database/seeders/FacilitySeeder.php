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
                'contact_person' => 'Facility Facilitator',
                'contact_number' => '09170000000',
                'email' => 'facility@cbis.local',
                'address' => 'Sample City',
                'is_active' => true,
            ]
        );

        $facilitator = User::firstOrCreate(
            ['email' => 'facility.admin@cbis.local'],
            [
                'name' => 'Facility Facilitator',
                'password' => Hash::make('password'),
                'facility_id' => $facility->id,
                'is_active' => true,
            ]
        );
        $facilitator->forceFill([
            'name' => 'Facility Facilitator',
            'facility_id' => $facility->id,
            'is_active' => true,
        ])->save();
        $facilitator->syncRoles(['Facilitator']);

        $medicalStaff = User::firstOrCreate(
            ['email' => 'medical.staff@cbis.local'],
            [
                'name' => 'Medical Staff Nurse',
                'password' => Hash::make('password'),
                'facility_id' => $facility->id,
                'is_active' => true,
            ]
        );
        $medicalStaff->forceFill([
            'facility_id' => $facility->id,
            'is_active' => true,
        ])->save();
        $medicalStaff->syncRoles(['Medical Staff / Nurse']);

        $legacyMedTech = User::withTrashed()->firstWhere('email', 'medtech@cbis.local');

        if ($legacyMedTech !== null) {
            $legacyMedTech->forceFill([
                'name' => 'Medical Staff Nurse',
                'facility_id' => $facility->id,
                'is_active' => true,
            ]);

            if ($legacyMedTech->trashed()) {
                $legacyMedTech->restore();
            }

            $legacyMedTech->save();
            $legacyMedTech->syncRoles(['Medical Staff / Nurse']);
        }
    }
}
