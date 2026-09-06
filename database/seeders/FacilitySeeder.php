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
                'name' => 'PHILIPPINE RED CROSS Bacolod City Chapter',
                'type' => 'blood_bank',
                'contact_person' => 'Facility Facilitator',
                'contact_number' => '09170000000',
                'email' => 'facility@cbis.local',
                'address' => 'Sample City',
                'is_active' => true,
            ]
        );

        $facility->update(['is_main_chapter' => true]);

        // Rename legacy demo addresses on existing local databases rather
        // than creating duplicate staff accounts when the seeder is rerun.
        $facilitator = User::query()->where('email', 'facilitator@cbis.local')->first()
            ?? User::query()->where('email', 'facility.admin@cbis.local')->first()
            ?? new User(['password' => Hash::make('password')]);
        $facilitator->forceFill([
            'email' => 'facilitator@cbis.local',
            'name' => 'Facility Facilitator',
            'facility_id' => $facility->id,
            'is_active' => true,
        ])->save();
        $facilitator->syncRoles(['Event Facilitator']);

        $medicalStaff = User::query()->where('email', 'bbs@cbis.local')->first()
            ?? User::query()->where('email', 'medical.staff@cbis.local')->first()
            ?? new User(['password' => Hash::make('password')]);
        $medicalStaff->forceFill([
            'email' => 'bbs@cbis.local',
            'name' => 'Blood Bank Staff',
            'facility_id' => $facility->id,
            'is_active' => true,
        ])->save();
        $medicalStaff->syncRoles(['Blood Bank Staff']);

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
            $legacyMedTech->syncRoles(['Blood Bank Staff']);
        }
    }
}
