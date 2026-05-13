<?php

namespace Database\Seeders;

use App\Models\BloodBankLocation;
use App\Models\DonationSchedule;
use App\Models\Facility;
use App\Models\FacilityApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@cbis.local')->first();

        foreach ($this->facilities() as $demo) {
            $locationPhotoPath = $this->copyDemoAsset($demo['asset'], 'demo/locations/'.$demo['asset']);
            $eventPhotoPath = $this->copyDemoAsset($demo['asset'], 'demo/events/'.$demo['asset']);
            $legitimacyProofPath = $this->copyDemoAsset($demo['asset'], 'demo/proofs/'.pathinfo($demo['asset'], PATHINFO_FILENAME).'-legitimacy.'.pathinfo($demo['asset'], PATHINFO_EXTENSION));
            $dohProofPath = $this->copyDemoAsset($demo['asset'], 'demo/proofs/'.pathinfo($demo['asset'], PATHINFO_FILENAME).'-doh.'.pathinfo($demo['asset'], PATHINFO_EXTENSION));

            $facility = $this->upsertFacility($demo);

            FacilityApplication::query()->updateOrCreate(
                ['email' => $demo['email']],
                [
                    'organization_name' => $demo['name'],
                    'facility_type' => $demo['type'],
                    'contact_person' => $demo['contact_person'],
                    'contact_number' => $demo['contact_number'],
                    'address' => $demo['address'],
                    'doh_accreditation_number' => $demo['doh_accreditation_number'],
                    'legitimacy_proof_path' => $legitimacyProofPath,
                    'doh_accreditation_proof_path' => $dohProofPath,
                    'status' => 'approved',
                    'review_notes' => 'Demo application approved for presentation data.',
                    'reviewed_by' => $admin?->id,
                    'reviewed_at' => now(),
                    'facility_id' => $facility->id,
                ]
            );

            BloodBankLocation::query()->updateOrCreate(
                ['facility_id' => $facility->id],
                [
                    'address' => $demo['address'],
                    'contact_number' => $demo['contact_number'],
                    'latitude' => $demo['latitude'],
                    'longitude' => $demo['longitude'],
                    'photo_path' => $locationPhotoPath,
                ]
            );

            $facilitator = $this->upsertUser([
                'name' => $demo['account_name'],
                'email' => $demo['email'],
                'phone' => $demo['contact_number'],
                'facility_id' => $facility->id,
                'role' => 'Facilitator',
            ]);
            $facilitator->syncRoles(['Facilitator']);

            DonationSchedule::query()->updateOrCreate(
                [
                    'facility_id' => $facility->id,
                    'title' => $demo['event']['title'],
                ],
                [
                    'event_type' => $demo['event']['type'],
                    'event_date' => $demo['event']['date'],
                    'start_time' => $demo['event']['start_time'],
                    'end_time' => $demo['event']['end_time'],
                    'start_at' => $demo['event']['date'].' '.$demo['event']['start_time'],
                    'end_at' => $demo['event']['date'].' '.$demo['event']['end_time'],
                    'venue' => $demo['event']['venue'],
                    'latitude' => $demo['event']['latitude'],
                    'longitude' => $demo['event']['longitude'],
                    'description' => $demo['event']['description'],
                    'photo_path' => $eventPhotoPath,
                    'contact_person' => $demo['contact_person'] ?: $demo['account_name'],
                    'contact_number' => $demo['contact_number'],
                    'is_public' => true,
                    'status' => 'planned',
                ]
            );
        }
    }

    private function upsertFacility(array $demo): Facility
    {
        $facility = Facility::withTrashed()
            ->where('email', $demo['email'])
            ->orWhere('code', $demo['code'])
            ->first() ?? new Facility();

        if ($facility->trashed()) {
            $facility->restore();
        }

        $facility->forceFill([
            'code' => $demo['code'],
            'name' => $demo['name'],
            'type' => $demo['type'],
            'contact_person' => $demo['contact_person'],
            'contact_number' => $demo['contact_number'],
            'email' => $demo['email'],
            'address' => $demo['address'],
            'is_active' => true,
        ])->save();

        return $facility;
    }

    private function upsertUser(array $data): User
    {
        $user = User::withTrashed()->where('email', $data['email'])->first() ?? new User();

        if ($user->trashed()) {
            $user->restore();
        }

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'facility_id' => $data['facility_id'],
            'password' => Hash::make('password'),
            'is_active' => true,
        ])->save();

        return $user;
    }

    private function copyDemoAsset(string $filename, string $target): string
    {
        $source = database_path('seeders/demo-assets/locations/'.$filename);

        if (file_exists($source)) {
            Storage::disk('public')->put($target, file_get_contents($source));
        }

        return $target;
    }

    private function facilities(): array
    {
        return [
            [
                'code' => 'FAC-001',
                'name' => 'PHILIPPINE RED CROSS Bacolod City Chapter',
                'type' => 'blood_bank',
                'contact_person' => 'Facility Facilitator',
                'account_name' => 'Red Cross Facility Facilitator',
                'contact_number' => '09170000000',
                'email' => 'facility@cbis.local',
                'address' => 'PHILIPPINE RED CROSS Bacolod City Chapter, Bacolod City',
                'doh_accreditation_number' => 'DEMO-PRC-001',
                'latitude' => '10.677697924777405',
                'longitude' => '122.955752974684320',
                'asset' => 'red-cross.webp',
                'event' => [
                    'title' => 'Bacolod Red Cross Blood Donation Drive',
                    'type' => 'blood_donation',
                    'date' => '2026-05-20',
                    'start_time' => '09:00:00',
                    'end_time' => '15:00:00',
                    'venue' => 'PHILIPPINE RED CROSS Bacolod City Chapter, Bacolod City',
                    'latitude' => '10.6776979',
                    'longitude' => '122.9557530',
                    'description' => 'A public blood donation activity for eligible donors in Bacolod. Please bring a valid ID and arrive within the scheduled time.',
                ],
            ],
            [
                'code' => 'FAC-002',
                'name' => 'Negros First Provincial Blood Center',
                'type' => 'blood_bank',
                'contact_person' => null,
                'account_name' => 'Negros First Provincial Blood Center',
                'contact_number' => '(034) 433 0313',
                'email' => 'moosec06+bloodbank2@gmail.com',
                'address' => 'Abad Santos Street, Bacolod, 6100 Negros Occidental',
                'doh_accreditation_number' => 'DEMO-NFPBC-002',
                'latitude' => '10.656565341312362',
                'longitude' => '122.942859542329340',
                'asset' => 'negros-first.jpg',
                'event' => [
                    'title' => 'Manapla Community Blood Donation Drive',
                    'type' => 'blood_donation',
                    'date' => '2026-05-21',
                    'start_time' => '08:30:00',
                    'end_time' => '14:30:00',
                    'venue' => 'Manapla, Negros Occidental',
                    'latitude' => '10.9591466',
                    'longitude' => '123.1234936',
                    'description' => 'A community blood donation event for eligible donors in Manapla.',
                ],
            ],
            [
                'code' => 'FAC-003',
                'name' => 'Corazon Locsin Montelibano Memorial Regional Hospital',
                'type' => 'hospital',
                'contact_person' => null,
                'account_name' => 'Corazon Locsin Memorial Hospital Facilitator',
                'contact_number' => '(034) 703 1350',
                'email' => 'moosec06+bloodbank3@gmail.com',
                'address' => 'MXC2+V9Q, Lacson St, Bacolod, 6100 Negros Occidental',
                'doh_accreditation_number' => 'DEMO-CLMMRH-003',
                'latitude' => '10.672442800000000',
                'longitude' => '122.950776800000000',
                'asset' => 'corazon-locsin.webp',
                'event' => [
                    'title' => 'Victorias Bloodletting Activity',
                    'type' => 'bloodletting',
                    'date' => '2026-05-22',
                    'start_time' => '09:00:00',
                    'end_time' => '16:00:00',
                    'venue' => 'Victorias, Negros Occidental',
                    'latitude' => '10.9013412',
                    'longitude' => '123.0714943',
                    'description' => 'A scheduled bloodletting activity for eligible donors in Victorias.',
                ],
            ],
        ];
    }
}
