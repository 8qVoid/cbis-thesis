<?php

namespace Database\Seeders;

use App\Models\BloodBankLocation;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\BloodReservation;
use App\Models\BloodReservationDocument;
use App\Models\BloodlettingRecord;
use App\Models\DonationRecord;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Models\EventRegistration;
use App\Models\Facility;
use App\Models\PatientProfile;
use App\Models\User;
use App\Notifications\ActivitySubmittedForReview;
use App\Notifications\BloodReservationStatusChanged;
use App\Notifications\BloodReservationSubmitted;
use App\Notifications\DonorScreeningUpdated;
use App\Notifications\EventPostedNotification;
use App\Notifications\LowStockAlert;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Showcase records may only be seeded locally.');
        }

        $this->call([RolePermissionSeeder::class, FacilitySeeder::class]);

        DB::transaction(function (): void {
            $main = Facility::where('is_main_chapter', true)->firstOrFail();
            $qao = User::role('Quality Assurance Officer')->whereNull('facility_id')->firstOrFail();
            $bbs = User::role('Blood Bank Staff')->where('facility_id', $main->id)->firstOrFail();

            $facilities = $this->facilities($main);
            [$donors, $patients] = $this->people($main, $bbs);
            $events = $this->events($facilities, $qao);
            $this->registrations($donors, $events);
            $this->retireExtraBranches();
            $this->donations($donors, $main, $bbs);
            $stock = $this->stock($main);
            $requests = $this->requests($patients, $stock, $main, $bbs);
            $this->requestDocuments($requests);
            $this->notifications($donors, $requests, $stock, $events, $qao, $bbs);
        });

        $this->command?->table(['Record', 'Count'], [
            ['Synthetic users', User::where('is_showcase', true)->where('email', 'like', '%@gmail.com')->count()],
            ['Donations', DonationRecord::where('donation_no', 'like', 'SC-DON-%')->count()],
            ['Manual stock batches', BloodInventory::whereNotNull('showcase_key')->count()],
            ['Requests', BloodReservation::where('reference', 'like', 'SC-REQ-%')->count()],
            ['Request documents', BloodReservationDocument::whereHas('reservation', fn ($query) => $query->where('reference', 'like', 'SC-REQ-%'))->count()],
            ['Events', DonationSchedule::where('title', 'like', 'Community %')->count()],
        ]);
    }

    private function facilities(Facility $main): array
    {
        $silay = Facility::where('code', 'FAC-002')->firstOrFail();
        $places = [
            [$main, 'Bacolod Public Plaza, Bacolod City', 10.6712, 122.9448],
            [$silay, 'Silay City Public Plaza, Silay City', 10.7989, 122.9755],
        ];
        foreach ([
            ['Manapla', 10.9490, 123.1230],
            ['Victorias', 10.9010, 123.0775],
            ['Cadiz', 10.9553, 123.3105],
        ] as [$city, $lat, $lng]) {
            $placeName = $city === 'Manapla' ? $city : $city.' City';
            $places[] = [$silay, $city.' Public Plaza, '.$placeName, $lat, $lng];
        }

        foreach (array_slice($places, 0, 2) as [$facility, $address, $lat, $lng]) {
            BloodBankLocation::firstOrCreate(['facility_id' => $facility->id, 'address' => $address], [
                'latitude' => $lat, 'longitude' => $lng, 'contact_number' => '09170009999',
            ]);
        }

        return $places;
    }

    private function people(Facility $main, User $bbs): array
    {
        $firstNames = ['Maria', 'Jose', 'Angela', 'Carlo', 'Jasmine', 'Paolo', 'Nicole', 'Miguel', 'Bea', 'Daniel', 'Sofia', 'Antonio'];
        $lastNames = ['Santos', 'Villanueva', 'Dela Cruz', 'Ramirez', 'Flores', 'Gonzales', 'Bautista', 'Navarro', 'Castillo', 'Fernandez'];
        $cities = ['Bacolod City', 'Silay City', 'Manapla', 'Victorias City', 'Cadiz City'];
        $types = BloodInventory::BLOOD_TYPES;
        $donors = $patients = [];

        for ($number = 1; $number <= 100; $number++) {
            $first = $firstNames[($number - 1) % count($firstNames)];
            $last = $lastNames[intdiv($number - 1, count($firstNames))];
            $suffix = sprintf('%03d', $number);
            $emailStem = Str::slug($first.'.'.$last, '.').'.'.$suffix;
            $email = $emailStem.'@gmail.com';
            $previousEmail = $emailStem.'@cbis.test';
            $isDonor = $number <= 64 || $number % 13 === 0;
            $isPatient = $number > 64 || $number % 13 === 0;
            $sex = $number % 2 ? 'female' : 'male';
            $birth = today()->subYears(20 + $number % 35)->subDays($number)->toDateString();
            $phone = '0917000'.sprintf('%04d', $number);
            $address = 'Barangay 1, '.$cities[($number - 1) % count($cities)].', Negros Occidental';
            $user = User::withTrashed()->where('email', $email)->first();
            if ($user && ! $user->is_showcase) {
                throw new RuntimeException('An existing account uses a showcase email address: '.$email);
            }
            if (! $user) {
                $user = User::withTrashed()->where('email', $previousEmail)->where('is_showcase', true)->first();
                if ($user) {
                    $user->forceFill(['email' => $email])->save();
                }
            }
            if (! $user) {
                $user = new User;
                $user->forceFill([
                    'email' => $email, 'name' => $first.' '.$last,
                    'first_name' => $first, 'last_name' => $last,
                    'birth_date' => $birth, 'sex' => $sex, 'phone' => $phone, 'address' => $address,
                    'password' => Hash::make(in_array($number, [1, 66], true) ? 'CBIS-Local-2026!' : Str::random(48)),
                    'email_verified_at' => now(), 'is_active' => true, 'is_showcase' => true,
                ])->save();
                $user->syncRoles(array_values(array_filter([
                    $isDonor ? 'Donor' : null, $isPatient ? 'Patient' : null,
                ])));
            }
            if ($user->trashed()) {
                continue;
            }

            if ($isDonor && $user->hasRole('Donor')) {
                $donor = Donor::withTrashed()->firstOrCreate(['user_id' => $user->id], [
                    'facility_id' => $main->id, 'first_name' => $first, 'last_name' => $last,
                    'birth_date' => $birth, 'sex' => $sex, 'blood_type' => $types[($number - 1) % count($types)],
                    'contact_number' => $phone, 'address' => $address,
                    'is_eligible' => $number % 9 !== 0 && $number % 11 !== 0,
                    'is_online_registered' => true,
                ]);
                if (! $donor->trashed()) {
                    if ($number % 11 !== 0) {
                        $eligible = $number % 9 !== 0;
                        DonorScreening::firstOrCreate(['donor_id' => $donor->id, 'donor_message' => 'Routine eligibility screening completed.'], [
                            'reviewed_by' => $bbs->id, 'status' => $eligible ? 'eligible' : 'deferred',
                            'review_on' => $eligible ? null : today()->addDays(30),
                        ]);
                    }
                    $donors[] = $donor;
                }
            }
            if ($isPatient && $user->hasRole('Patient')) {
                PatientProfile::firstOrCreate(['user_id' => $user->id]);
                $patients[] = $user;
            }
        }

        return [$donors, $patients];
    }

    private function events(array $places, User $qao): array
    {
        $events = [];
        foreach (range(0, 11) as $index) {
            [$facility, $venue, $lat, $lng] = $places[$index % count($places)];
            $past = $index < 5;
            $pending = $index >= 10;
            $date = $past ? today()->subDays(70 - $index * 11) : today()->addDays(5 + ($index - 5) * 7);
            $title = 'Community Blood Drive - '.explode(' Public Plaza', $venue)[0].' - '.($index + 1);
            $event = DonationSchedule::withTrashed()->firstOrCreate(['title' => $title], [
                'facility_id' => $facility->id, 'event_type' => $index % 3 === 0 ? 'bloodletting' : 'blood_donation',
                'event_date' => $date, 'start_time' => '08:00', 'end_time' => '16:00',
                'start_at' => $date->copy()->setTime(8, 0), 'end_at' => $date->copy()->setTime(16, 0),
                'venue' => $venue, 'latitude' => $lat, 'longitude' => $lng,
                'photo_path' => 'event-photos/community-blood-drive.jpg',
                'description' => 'Community blood donation and health screening at the public plaza.',
                'contact_person' => 'Branch Coordinator', 'contact_number' => '09170009999',
                'is_public' => ! $pending, 'approval_status' => $pending ? 'pending' : 'approved',
                'reviewed_by' => $pending ? null : $qao->id,
                'reviewed_at' => $pending ? null : $date->copy()->subDays(14),
                'status' => $past ? 'completed' : 'planned',
            ]);
            if (! $event->trashed() && $event->facility_id !== $facility->id) {
                $event->update(['facility_id' => $facility->id]);
            }
            $events[] = $event;
        }

        return $events;
    }

    private function registrations(array $donors, array $events): void
    {
        foreach ($donors as $index => $donor) {
            foreach ([$index % 10, ($index + 3) % 10] as $eventIndex) {
                $event = $events[$eventIndex];
                if ($event->trashed()) {
                    continue;
                }
                $registration = EventRegistration::firstOrCreate([
                    'donation_schedule_id' => $event->id, 'donor_id' => $donor->id,
                ], [
                    'facility_id' => $event->facility_id,
                    'status' => $eventIndex < 5 ? ($index % 7 === 0 ? 'no_show' : 'attended') : 'registered',
                    'registered_at' => $event->event_date->copy()->subDays(7),
                ]);
                if ($registration->facility_id !== $event->facility_id) {
                    $registration->update(['facility_id' => $event->facility_id]);
                }
            }
        }
    }

    private function retireExtraBranches(): void
    {
        Facility::whereIn('code', ['SC-MANAPLA', 'SC-VICTORIAS', 'SC-CADIZ'])->get()->each(function (Facility $facility): void {
            $facility->locations()->delete();
            $facility->delete();
        });
    }

    private function donations(array $donors, Facility $main, User $bbs): void
    {
        foreach (array_slice($donors, 0, 55) as $index => $donor) {
            if (! $donor->is_eligible) {
                continue;
            }
            foreach ([0, 1] as $round) {
                $number = ($index + 1) * 2 - 1 + $round;
                $donatedAt = today()->subDays(22 + $index % 12 + $round * 100)->setTime(8 + $index % 7, 20);
                $expiration = $round ? today()->subDays(4 + $index % 14) : today()->addDays(6 + $index % 42);
                $status = $index % 17 === 0 && ! $round ? 'pending' : 'verified';
                $donation = DonationRecord::withTrashed()->firstOrCreate(['donation_no' => sprintf('SC-DON-%04d', $number)], [
                    'facility_id' => $main->id, 'donor_id' => $donor->id, 'recorded_by' => $bbs->id,
                    'donated_at' => $donatedAt, 'blood_type' => $donor->blood_type,
                    'volume_ml' => 450, 'expiration_date' => $expiration,
                    'status' => $status, 'remarks' => 'Collected during a scheduled blood service visit.',
                ]);
                if ($donation->trashed()) {
                    continue;
                }
                BloodlettingRecord::withTrashed()->firstOrCreate(['donation_record_id' => $donation->id], [
                    'facility_id' => $main->id, 'medical_technologist_id' => $bbs->id,
                    'bloodletting_at' => $donatedAt,
                    'verification_status' => $status,
                    'findings' => $status === 'verified' ? 'Collection completed without incident.' : 'Pending staff verification.',
                ]);
                if ($status === 'verified') {
                    BloodInventory::withTrashed()->firstOrCreate(['donation_record_id' => $donation->id], [
                        'facility_id' => $main->id, 'blood_type' => $donor->blood_type,
                        'component' => 'whole_blood', 'units_available' => $round ? 0 : 1,
                        'expiration_date' => $expiration,
                        'status' => $round ? 'expired' : 'low_stock',
                        'last_low_stock_alert_at' => now(),
                    ]);
                }
            }
        }
    }

    private function stock(Facility $main): array
    {
        $batches = [];
        $components = array_keys(BloodInventory::COMPONENTS);
        foreach (BloodInventory::BLOOD_TYPES as $typeIndex => $bloodType) {
            foreach ($components as $componentIndex => $component) {
                $number = $typeIndex * count($components) + $componentIndex;
                $units = $number % 5 === 0 ? 2 + $number % 3 : 8 + $number % 21;
                $threshold = max(1, (int) env('LOW_STOCK_THRESHOLD', 5));
                $low = $units <= $threshold;
                $batch = BloodInventory::withTrashed()->firstOrCreate(['showcase_key' => 'SC-STOCK-'.sprintf('%02d', $number)], [
                    'facility_id' => $main->id, 'donation_record_id' => null,
                    'blood_type' => $bloodType, 'component' => $component,
                    'units_available' => $units,
                    'expiration_date' => today()->addDays(12 + $number * 2),
                    'status' => $low ? 'low_stock' : 'active',
                    'last_low_stock_alert_at' => $low ? now() : null,
                ]);
                $batches[$bloodType.'|'.$component] = $batch;
            }
        }
        foreach (BloodInventory::BLOOD_TYPES as $index => $bloodType) {
            BloodInventory::withTrashed()->firstOrCreate(['showcase_key' => 'SC-EXPIRED-'.sprintf('%02d', $index)], [
                'facility_id' => $main->id, 'donation_record_id' => null,
                'blood_type' => $bloodType, 'component' => $components[$index % count($components)],
                'units_available' => 0, 'expiration_date' => today()->subDays(2 + $index), 'status' => 'expired',
            ]);
        }

        return $batches;
    }

    private function requests(array $patients, array $stock, Facility $main, User $bbs): array
    {
        $requests = [];
        $components = array_keys(BloodInventory::COMPONENTS);
        $statuses = ['submitted', 'under_review', 'approved', 'fulfilled', 'rejected', 'cancelled'];
        foreach (range(0, 31) as $index) {
            $bloodType = BloodInventory::BLOOD_TYPES[$index % 8];
            $component = $components[intdiv($index, 8)];
            $status = $statuses[$index % count($statuses)];
            $units = 1 + $index % 3;
            $batch = $stock[$bloodType.'|'.$component];
            if ($status === 'fulfilled') {
                $units = min($units, max(1, $batch->units_available));
            }
            $patient = $patients[$index % count($patients)];
            $request = BloodReservation::firstOrCreate(['reference' => sprintf('SC-REQ-%03d', $index + 1)], [
                'patient_user_id' => $patient->id, 'facility_id' => $main->id,
                'blood_type' => $bloodType, 'component' => $component, 'units_requested' => $units,
                'needed_on' => in_array($status, ['fulfilled', 'rejected', 'cancelled'], true)
                    ? today()->subDays(2 + $index % 9) : today()->addDays(2 + $index % 12),
                'clinical_purpose' => 'Hospital transfusion request following physician assessment.',
                'status' => $status, 'reviewed_by' => $status === 'submitted' ? null : $bbs->id,
                'reviewed_at' => $status === 'submitted' ? null : now()->subDays($index % 6),
                'review_notes' => match ($status) {
                    'approved' => 'Matching stock reserved for the scheduled procedure.',
                    'fulfilled' => 'Requested units were released to the receiving department.',
                    'rejected' => 'Referral details need clarification before a new request is submitted.',
                    default => null,
                },
            ]);
            $requests[] = $request;

            if ($status !== 'fulfilled') {
                continue;
            }
            if ($batch->trashed() || $batch->units_available < $request->units_requested) {
                continue;
            }
            $release = BloodRelease::withTrashed()->firstOrCreate(['blood_reservation_id' => $request->id], [
                'facility_id' => $main->id, 'blood_inventory_id' => $batch->id,
                'released_by' => $bbs->id, 'patient_name' => $patient->name,
                'requesting_unit' => 'Receiving Hospital Blood Bank',
                'released_at' => now()->subDays(1 + $index % 5),
                'units_released' => $request->units_requested, 'purpose' => 'Requested transfusion.',
            ]);
            if ($release->wasRecentlyCreated) {
                $batch->decrement('units_available', $request->units_requested);
                $batch->refresh();
                if ($batch->units_available <= max(1, (int) env('LOW_STOCK_THRESHOLD', 5))) {
                    $batch->forceFill(['status' => 'low_stock', 'last_low_stock_alert_at' => now()])->save();
                }
            }
        }

        return $requests;
    }

    private function requestDocuments(array $requests): void
    {
        foreach ($requests as $request) {
            $request->loadMissing(['patient', 'facility']);
            foreach (['blood_request', 'identification'] as $type) {
                $name = $type === 'blood_request' ? 'Blood-Request-'.$request->reference.'.pdf' : 'Patient-Profile-'.$request->reference.'.pdf';
                $path = 'reservations/'.$request->id.'/'.$name;
                if (! Storage::disk('local')->exists($path)) {
                    $pdf = Pdf::loadView('pdf.showcase-request-document', [
                        'reservation' => $request,
                        'type' => $type,
                    ])->setPaper('a4');
                    Storage::disk('local')->put($path, $pdf->output());
                    unset($pdf);
                    gc_collect_cycles();
                }
                $request->documents()->firstOrCreate(['type' => $type, 'path' => $path], [
                    'original_name' => $name,
                    'mime_type' => 'application/pdf',
                    'size' => Storage::disk('local')->size($path),
                ]);
            }
        }
    }

    private function notifications(array $donors, array $requests, array $stock, array $events, User $qao, User $bbs): void
    {
        $publicEvent = $events[5];
        foreach ($donors as $donor) {
            $user = $donor->user;
            $this->notify($user, 'event-'.$publicEvent->id, EventPostedNotification::class, [
                'title' => 'New donation activity', 'event_id' => $publicEvent->id,
                'event_title' => $publicEvent->title, 'event_type' => $publicEvent->event_type,
                'event_date' => $publicEvent->event_date->toDateString(),
                'facility_id' => $publicEvent->facility_id, 'facility_name' => $publicEvent->facility->name,
            ]);
            $screening = $donor->screenings()->first();
            if ($screening) {
                $this->notify($user, 'screening-'.$donor->id, DonorScreeningUpdated::class, [
                    'title' => 'Donation screening updated', 'status' => $screening->status,
                    'donor_message' => $screening->donor_message,
                    'review_on' => $screening->review_on?->toDateString(),
                ], true);
            }
        }

        foreach ($requests as $request) {
            $this->notify($bbs, 'request-'.$request->id, BloodReservationSubmitted::class, [
                'title' => 'New blood reservation', 'reservation_id' => $request->id,
                'reference' => $request->reference, 'facility_id' => $request->facility_id,
                'blood_type' => $request->blood_type, 'component' => $request->component,
            ], $request->status !== 'submitted');
            $this->notify($qao, 'request-'.$request->id, BloodReservationSubmitted::class, [
                'title' => 'New blood reservation', 'reservation_id' => $request->id,
                'reference' => $request->reference, 'facility_id' => $request->facility_id,
                'blood_type' => $request->blood_type, 'component' => $request->component,
            ], true);
            $this->notify($request->patient, 'request-status-'.$request->id, BloodReservationStatusChanged::class, [
                'title' => 'Reservation status updated', 'reservation_id' => $request->id,
                'reference' => $request->reference, 'status' => $request->status,
                'review_notes' => $request->review_notes,
            ], in_array($request->status, ['fulfilled', 'rejected', 'cancelled'], true));
        }

        foreach ($stock as $batch) {
            if ($batch->trashed() || $batch->status !== 'low_stock') {
                continue;
            }
            $data = [
                'title' => 'Low blood stock detected', 'facility_id' => $batch->facility_id,
                'facility_name' => $batch->facility->name, 'blood_type' => $batch->blood_type,
                'component' => $batch->component_label, 'units_available' => $batch->units_available,
                'expiration_date' => $batch->expiration_date->toDateString(),
            ];
            foreach ([$qao, $bbs] as $recipient) {
                $this->notify($recipient, 'low-stock-'.$batch->id, LowStockAlert::class, $data);
            }
        }
        foreach ($events as $event) {
            if ($event->trashed()) {
                continue;
            }
            if ($event->approval_status === 'pending') {
                $this->notify($qao, 'event-review-'.$event->id, ActivitySubmittedForReview::class, [
                    'title' => 'Activity pending QAO review', 'activity_id' => $event->id,
                    'activity_title' => $event->title, 'activity_type' => $event->event_type,
                    'event_date' => $event->event_date->toDateString(),
                    'facility_id' => $event->facility_id, 'facility_name' => $event->facility->name,
                    'venue' => $event->venue, 'submitted_by' => 'Branch Coordinator',
                    'approval_status' => 'pending',
                ]);
            } else {
                $this->notify($qao, 'event-published-'.$event->id, EventPostedNotification::class, [
                    'title' => 'Donation event published', 'event_id' => $event->id,
                    'event_title' => $event->title, 'event_type' => $event->event_type,
                    'event_date' => $event->event_date->toDateString(),
                    'facility_id' => $event->facility_id, 'facility_name' => $event->facility->name,
                ], true);
            }
        }
    }

    private function notify(User $recipient, string $key, string $type, array $data, bool $read = false): void
    {
        $hex = substr(hash('sha256', 'cbis-local-showcase-'.$key.'-'.$recipient->id), 0, 32);
        $id = substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20);
        $notification = $recipient->notifications()->firstOrCreate(['id' => $id], [
            'type' => $type, 'data' => $data, 'read_at' => $read ? now()->subDays(1) : null,
        ]);
        if (! $notification->wasRecentlyCreated && isset($data['facility_id']) && $notification->data !== $data) {
            $notification->update(['data' => $data]);
        }
    }
}
