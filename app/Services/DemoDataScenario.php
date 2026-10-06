<?php

namespace App\Services;

use App\Events\BloodReleased;
use App\Events\DonationRecorded;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\BloodReservation;
use App\Models\DonationRecord;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Models\EventRegistration;
use App\Models\Facility;
use App\Models\PatientProfile;
use App\Models\User;
use App\Notifications\BloodReservationStatusChanged;
use App\Notifications\BloodReservationSubmitted;
use App\Notifications\DonorScreeningUpdated;
use App\Notifications\EventPostedNotification;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataScenario
{
    /** @return array<string, int> */
    public function populate(): array
    {
        return DB::transaction(function (): array {
            $facility = Facility::query()->where('is_main_chapter', true)->firstOrFail();
            $qao = User::role('Quality Assurance Officer')->whereNull('facility_id')->firstOrFail();
            $staff = User::role('Blood Bank Staff')->where('facility_id', $facility->id)->firstOrFail();

            $accounts = $this->registerAccounts();
            $events = $this->createEvents($facility, $qao);
            $this->createScreenings($accounts, $staff);
            $this->createRegistrations($accounts, $events, $facility);
            $this->createDonationsAndInventory($accounts, $events, $facility, $staff);
            $reservations = $this->createReservations($accounts, $facility, $staff);
            $this->createRelease($reservations, $facility, $staff);
            $this->createNotifications($accounts, $events, $reservations, $qao, $staff);

            return [
                'Showcase accounts' => count($accounts),
                'Donor profiles' => Donor::query()->whereHas('user', fn ($query) => $query->where('email', 'like', '%.@example.test'))->count(),
                'Activities' => DonationSchedule::query()->where('title', 'like', '[DEMO]%')->count(),
                'Event registrations' => EventRegistration::query()->whereHas('event', fn ($query) => $query->where('title', 'like', '[DEMO]%'))->count(),
                'Donation records' => DonationRecord::query()->where('donation_no', 'like', 'DON-2026-%')->count(),
                'Donation inventory batches' => BloodInventory::query()->whereHas('donationRecord', fn ($query) => $query->where('donation_no', 'like', 'DON-2026-%'))->count(),
                'Blood requests' => BloodReservation::query()->where('reference', 'like', 'REQ-2026-%')->count(),
                'Blood releases' => BloodRelease::query()->where('purpose', 'like', '[DEMO]%')->count(),
            ];
        });
    }

    /** @return array<string, array{user: User, donor: ?Donor}> */
    private function registerAccounts(): array
    {
        $people = [
            ['key' => 'maria-santos', 'first' => 'Maria', 'middle' => 'Lopez', 'last' => 'Santos', 'sex' => 'female', 'birth' => '1997-04-18', 'blood' => 'O+', 'phone' => '09170001001', 'address' => 'Barangay Mansilingan, Bacolod City', 'roles' => ['Donor']],
            ['key' => 'jose-villanueva', 'first' => 'Jose', 'middle' => 'Garcia', 'last' => 'Villanueva', 'sex' => 'male', 'birth' => '1994-11-03', 'blood' => 'A+', 'phone' => '09170001002', 'address' => 'Barangay Mandalagan, Bacolod City', 'roles' => ['Donor']],
            ['key' => 'angela-dela-cruz', 'first' => 'Angela', 'middle' => 'Reyes', 'last' => 'Dela Cruz', 'sex' => 'female', 'birth' => '2000-02-26', 'blood' => 'B+', 'phone' => '09170001003', 'address' => 'Barangay Bata, Bacolod City', 'roles' => ['Donor', 'Patient']],
            ['key' => 'carlo-ramirez', 'first' => 'Carlo', 'middle' => 'Torres', 'last' => 'Ramirez', 'sex' => 'male', 'birth' => '1992-07-12', 'blood' => 'AB+', 'phone' => '09170001004', 'address' => 'Talisay City, Negros Occidental', 'roles' => ['Donor']],
            ['key' => 'jasmine-flores', 'first' => 'Jasmine', 'middle' => 'Diaz', 'last' => 'Flores', 'sex' => 'female', 'birth' => '1998-09-07', 'blood' => 'O-', 'phone' => '09170001005', 'address' => 'Silay City, Negros Occidental', 'roles' => ['Donor']],
            ['key' => 'paolo-gonzales', 'first' => 'Paolo', 'middle' => 'Mendoza', 'last' => 'Gonzales', 'sex' => 'male', 'birth' => '1995-01-29', 'blood' => 'A-', 'phone' => '09170001006', 'address' => 'Bago City, Negros Occidental', 'roles' => ['Donor']],
            ['key' => 'nicole-bautista', 'first' => 'Nicole', 'middle' => 'Lim', 'last' => 'Bautista', 'sex' => 'female', 'birth' => '2001-06-15', 'blood' => 'B-', 'phone' => '09170001007', 'address' => 'Victorias City, Negros Occidental', 'roles' => ['Donor']],
            ['key' => 'miguel-navarro', 'first' => 'Miguel', 'middle' => 'Cruz', 'last' => 'Navarro', 'sex' => 'male', 'birth' => '1991-12-21', 'blood' => 'AB-', 'phone' => '09170001008', 'address' => 'Cadiz City, Negros Occidental', 'roles' => ['Donor']],
            ['key' => 'bea-castillo', 'first' => 'Bea', 'middle' => 'Aquino', 'last' => 'Castillo', 'sex' => 'female', 'birth' => '1999-08-09', 'blood' => 'O+', 'phone' => '09170001009', 'address' => 'La Carlota City, Negros Occidental', 'roles' => ['Donor']],
            ['key' => 'daniel-fernandez', 'first' => 'Daniel', 'middle' => 'Ramos', 'last' => 'Fernandez', 'sex' => 'male', 'birth' => '1996-05-30', 'blood' => 'A+', 'phone' => '09170001010', 'address' => 'Kabankalan City, Negros Occidental', 'roles' => ['Donor']],
            ['key' => 'sofia-martinez', 'first' => 'Sofia', 'middle' => 'Perez', 'last' => 'Martinez', 'sex' => 'female', 'birth' => '1988-03-14', 'blood' => 'B+', 'phone' => '09170001011', 'address' => 'Barangay Taculing, Bacolod City', 'roles' => ['Patient']],
            ['key' => 'antonio-sy', 'first' => 'Antonio', 'middle' => 'Tan', 'last' => 'Sy', 'sex' => 'male', 'birth' => '1985-10-22', 'blood' => 'O+', 'phone' => '09170001012', 'address' => 'Barangay Villamonte, Bacolod City', 'roles' => ['Patient']],
            ['key' => 'lourdes-alcantara', 'first' => 'Lourdes', 'middle' => 'Salazar', 'last' => 'Alcantara', 'sex' => 'female', 'birth' => '1979-01-05', 'blood' => 'AB+', 'phone' => '09170001013', 'address' => 'Barangay Alijis, Bacolod City', 'roles' => ['Patient']],
            ['key' => 'renato-mercado', 'first' => 'Renato', 'middle' => 'Ocampo', 'last' => 'Mercado', 'sex' => 'male', 'birth' => '1982-06-19', 'blood' => 'O-', 'phone' => '09170001014', 'address' => 'Himamaylan City, Negros Occidental', 'roles' => ['Patient']],
        ];

        $accounts = [];
        foreach ($people as $person) {
            $email = $person['key'].'@example.test';
            $user = User::withTrashed()->firstOrNew(['email' => $email]);
            $attributes = [
                'name' => implode(' ', [$person['first'], $person['middle'], $person['last']]),
                'first_name' => $person['first'], 'middle_name' => $person['middle'], 'last_name' => $person['last'],
                'birth_date' => $person['birth'], 'sex' => $person['sex'], 'phone' => $person['phone'],
                'address' => $person['address'],
                'is_active' => true, 'email_verified_at' => now(),
            ];
            if (! $user->exists) {
                $attributes['password'] = Hash::make(Str::random(64));
                $user->forceFill($attributes)->save();
                $user->syncRoles($person['roles']);
            }

            $donor = Donor::withTrashed()->where('user_id', $user->id)->first();
            if (! $user->trashed() && $user->hasRole('Donor') && ! $donor) {
                $donor = Donor::create([
                    'user_id' => $user->id,
                    'facility_id' => null, 'first_name' => $person['first'], 'middle_name' => $person['middle'],
                    'last_name' => $person['last'], 'birth_date' => $person['birth'], 'sex' => $person['sex'],
                    'blood_type' => $person['blood'], 'contact_number' => $person['phone'], 'email' => null,
                    'address' => $person['address'], 'is_eligible' => false, 'is_online_registered' => true,
                ]);
            }
            if (! $user->trashed() && $user->hasRole('Patient')) {
                PatientProfile::firstOrCreate(['user_id' => $user->id]);
            }
            $accounts[$person['key']] = ['user' => $user, 'donor' => $donor];
        }

        return $accounts;
    }

    /** @return array<string, DonationSchedule> */
    private function createEvents(Facility $facility, User $qao): array
    {
        $today = Carbon::today();
        $definitions = [
            'bacolod-plaza' => ['Bacolod Public Plaza Blood Drive', $today->copy()->addDays(5), 'Bacolod Public Plaza, Bacolod City', 10.6712, 122.9448, 'planned'],
            'victorias-plaza' => ['Victorias City Public Plaza Blood Drive', $today->copy()->addDays(12), 'Victorias City Public Plaza, Victorias City', 10.9010, 123.0775, 'planned'],
            'manapla-plaza' => ['Manapla Public Plaza Blood Drive', $today->copy()->addDays(19), 'Manapla Public Plaza, Manapla', 10.9490, 123.1230, 'planned'],
            'sagay-plaza' => ['Sagay City Public Plaza Blood Drive', $today->copy()->addDays(26), 'Sagay City Public Plaza, Sagay City', 10.9447, 123.4240, 'planned'],
        ];

        $events = [];
        foreach ($definitions as $key => [$title, $date, $venue, $latitude, $longitude, $status]) {
            $pending = false;
            $events[$key] = DonationSchedule::withTrashed()->firstOrCreate(['title' => $title], [
                'facility_id' => $facility->id, 'event_type' => false ? 'bloodletting' : 'blood_donation',
                'event_date' => $date, 'start_time' => '08:00', 'end_time' => '16:00',
                'start_at' => $date->copy()->setTime(8, 0), 'end_at' => $date->copy()->setTime(16, 0),
                'venue' => $venue, 'latitude' => $latitude, 'longitude' => $longitude,
                'description' => 'Demonstration activity using a real Negros Occidental venue. This is fictional test data and not a public event announcement.',
                'contact_person' => 'PRC Negros Occidental Demo Desk', 'contact_number' => '09170000999',
                'is_public' => ! $pending, 'approval_status' => $pending ? 'pending' : 'approved',
                'reviewed_by' => $pending ? null : $qao->id, 'reviewed_at' => $pending ? null : now(),
                'review_notes' => $pending ? null : 'Approved community blood activity.', 'status' => $status,
            ]);
        }

        return $events;
    }

    private function createScreenings(array $accounts, User $staff): void
    {
        $eligible = array_slice(array_keys(array_filter($accounts, fn ($account) => $account['donor'] !== null)), 0, 8);
        foreach ($accounts as $key => $account) {
            if (! $account['donor'] || $account['user']->trashed() || $account['donor']->trashed()) {
                continue;
            }
            $isEligible = in_array($key, $eligible, true);
            $screening = DonorScreening::firstOrCreate(
                ['donor_id' => $account['donor']->id, 'donor_message' => 'Initial screening'],
                ['reviewed_by' => $staff->id, 'status' => $isEligible ? 'eligible' : 'deferred',
                    'review_on' => $isEligible ? null : today()->addDays(30)]
            );
            if ($account['donor']->wasRecentlyCreated && $screening->wasRecentlyCreated) {
                $account['donor']->update(['is_eligible' => $isEligible]);
            }
        }
    }

    private function createRegistrations(array $accounts, array $events, Facility $facility): void
    {
        $donorAccounts = array_values(array_filter($accounts, fn ($account) => $account['donor'] !== null));
        foreach ($donorAccounts as $index => $account) {
            $donor = $account['donor'];
            $event = $index < 5 ? $events['bacolod-plaza'] : ($index < 8 ? $events['victorias-plaza'] : $events['bacolod-plaza']);
            if ($account['user']->trashed() || $donor->trashed() || $event->trashed()) {
                continue;
            }
            EventRegistration::firstOrCreate(
                ['donation_schedule_id' => $event->id, 'donor_id' => $donor->id],
                ['facility_id' => $facility->id, 'status' => $index < 8 ? ($index === 7 ? 'no_show' : 'attended') : 'registered',
                    'registered_at' => $event->event_date->copy()->subDays(5)]
            );
        }
    }

    private function createDonationsAndInventory(array $accounts, array $events, Facility $facility, User $staff): void
    {
        $components = array_keys(BloodInventory::COMPONENTS);
        $donorAccounts = array_values(array_filter($accounts, fn ($account) => $account['donor'] !== null));
        foreach (array_slice($donorAccounts, 0, 8) as $index => $account) {
            $donor = $account['donor'];
            if ($account['user']->trashed() || $donor->trashed()) {
                continue;
            }
            $recent = $index >= 5;
            $donatedAt = $recent
                ? today()->subDays(11)->setTime(10 + $index - 5, 15)
                : today()->subDays(24)->setTime(9 + $index, 10);
            $expiration = $index === 0 ? today()->subDay() : today()->addDays(7 + ($index * 4));
            $status = $index === 7 ? 'pending' : 'verified';
            $record = DonationRecord::withTrashed()->firstOrCreate(['donation_no' => sprintf('DON-2026-%03d', $index + 1)], [
                'facility_id' => $facility->id, 'donor_id' => $donor->id, 'recorded_by' => $staff->id,
                'donated_at' => $donatedAt, 'blood_type' => $donor->blood_type, 'volume_ml' => 450,
                'expiration_date' => $expiration, 'status' => $status,
                'remarks' => 'Donation recorded from an attended community blood activity.',
            ]);
            if (! $record->wasRecentlyCreated) {
                continue;
            }
            if ($status === 'verified') {
                event(new DonationRecorded($record));
                $record->inventory()->withTrashed()->first()?->update([
                    'component' => $components[$index % count($components)],
                    'units_available' => $index === 1 ? 0 : 1,
                    'expiration_date' => $expiration,
                    'status' => $expiration->isPast() ? 'expired' : ($index < 5 ? 'low_stock' : 'active'),
                    'last_low_stock_alert_at' => $index < 5 && ! $expiration->isPast() ? now() : null,
                ]);
            }
        }

        $manualBatches = [
            ['O+', 'whole_blood', 24, 28], ['A+', 'packed_red_blood_cells', 22, 31],
            ['B+', 'fresh_frozen_plasma', 12, 120], ['AB+', 'platelet_concentrate', 3, 4],
            ['O-', 'packed_red_blood_cells', 2, 18], ['A-', 'fresh_frozen_plasma', 8, 95],
        ];
        foreach ($manualBatches as [$bloodType, $component, $units, $days]) {
            // Expiry is initial scenario data, not a batch identity. Reusing the
            // existing manual batch preserves user edits and cross-day reruns.
            BloodInventory::withTrashed()->firstOrCreate([
                'facility_id' => $facility->id, 'donation_record_id' => null,
                'blood_type' => $bloodType, 'component' => $component,
            ], [
                'expiration_date' => today()->addDays($days),
                'units_available' => $units,
                'status' => $units <= match ($component) {
                    'whole_blood', 'packed_red_blood_cells' => 20,
                    'platelet_concentrate' => 5,
                    'fresh_frozen_plasma' => 10,
                } ? 'low_stock' : 'active',
                'last_low_stock_alert_at' => $units <= 10 ? now() : null,
            ]);
        }
    }

    /** @return array<int, BloodReservation> */
    private function createReservations(array $accounts, Facility $facility, User $staff): array
    {
        $definitions = [
            ['sofia-martinez', 'O+', 'whole_blood', 2, 'submitted', null],
            ['antonio-sy', 'A+', 'packed_red_blood_cells', 2, 'under_review', 'Documents are being reviewed.'],
            ['lourdes-alcantara', 'B+', 'fresh_frozen_plasma', 2, 'approved', 'Stock reserved for scheduled procedure.'],
            ['renato-mercado', 'O-', 'packed_red_blood_cells', 1, 'rejected', 'Requested component is not indicated on the referral.'],
            ['angela-dela-cruz', 'B+', 'fresh_frozen_plasma', 1, 'fulfilled', 'Released to the requesting unit.'],
        ];

        $reservations = [];
        foreach ($definitions as $index => [$accountKey, $bloodType, $component, $units, $status, $notes]) {
            if ($accounts[$accountKey]['user']->trashed()) {
                continue;
            }
            $reservations[] = BloodReservation::firstOrCreate(['reference' => sprintf('REQ-2026-%03d', $index + 1)], [
                'patient_user_id' => $accounts[$accountKey]['user']->id, 'facility_id' => $facility->id,
                'blood_type' => $bloodType, 'component' => $component, 'units_requested' => $units,
                'needed_on' => today()->addDays($index + 1),
                'clinical_purpose' => 'Example hospital request for thesis workflow presentation.',
                'status' => $status, 'reviewed_by' => $status === 'submitted' ? null : $staff->id,
                'reviewed_at' => $status === 'submitted' ? null : now()->subHours(8 - $index), 'review_notes' => $notes,
            ]);
        }

        return $reservations;
    }

    private function createRelease(array $reservations, Facility $facility, User $staff): void
    {
        $fulfilled = collect($reservations)->firstWhere('reference', 'REQ-2026-005');
        if (! $fulfilled || ! $fulfilled->wasRecentlyCreated || $fulfilled->status !== 'fulfilled'
            || BloodRelease::withTrashed()->where('blood_reservation_id', $fulfilled->id)->exists()) {
            return;
        }
        $inventory = BloodInventory::query()
            ->where('facility_id', $facility->id)->where('blood_type', $fulfilled->blood_type)
            ->where('component', $fulfilled->component)->where('units_available', '>', 0)
            ->whereDate('expiration_date', '>=', today())->orderBy('expiration_date')->firstOrFail();
        $release = BloodRelease::withTrashed()->firstOrCreate([
            'blood_reservation_id' => $fulfilled->id,
        ], [
            'facility_id' => $facility->id, 'blood_inventory_id' => $inventory->id, 'released_by' => $staff->id,
            'patient_name' => $fulfilled->patient->name, 'requesting_unit' => 'Bacolod City Medical Center',
            'released_at' => now()->subHours(2), 'units_released' => 1,
            'purpose' => 'Fulfilled blood reservation.',
        ]);
        if ($release->wasRecentlyCreated) {
            event(new BloodReleased($release));
        }
    }

    private function createNotifications(array $accounts, array $events, array $reservations, User $qao, User $staff): void
    {
        foreach ($accounts as $key => $account) {
            if ($account['user']->trashed()) {
                continue;
            }
            if ($account['donor'] && ! $account['donor']->trashed()) {
                if (! $events['bacolod-plaza']->trashed()) {
                    $this->notification($account['user'], 'event-'.$key, EventPostedNotification::class, [
                        'title' => 'New donation activity', 'event_id' => $events['bacolod-plaza']->id,
                        'event_title' => $events['bacolod-plaza']->title,
                        'event_type' => $events['bacolod-plaza']->event_type,
                        'event_date' => $events['bacolod-plaza']->event_date->toDateString(),
                        'facility_id' => $events['bacolod-plaza']->facility_id,
                        'facility_name' => $events['bacolod-plaza']->facility->name,
                    ]);
                }
                $latestScreening = $account['donor']->screenings()->latest()->first();
                if ($latestScreening) {
                    $this->notification($account['user'], 'screening-'.$key, DonorScreeningUpdated::class, [
                        'title' => 'Donation screening updated', 'status' => $latestScreening->status,
                        'donor_message' => $latestScreening->donor_message,
                        'review_on' => $latestScreening->review_on?->toDateString(),
                    ]);
                }
            }
        }
        foreach ($reservations as $reservation) {
            if (! $reservation->patient || $reservation->patient->trashed()) {
                continue;
            }
            $this->notification($reservation->patient, 'reservation-'.$reservation->reference, BloodReservationStatusChanged::class, [
                'title' => 'Reservation status updated', 'reservation_id' => $reservation->id,
                'reference' => $reservation->reference, 'status' => $reservation->status,
                'review_notes' => $reservation->review_notes,
            ], in_array($reservation->status, ['fulfilled', 'rejected'], true));
        }
        foreach ([$qao, $staff] as $recipient) {
            $this->notification($recipient, 'low-stock-'.$recipient->id, LowStockAlert::class, [
                'title' => 'Low blood stock detected', 'facility_id' => $staff->facility_id,
                'facility_name' => $staff->facility->name, 'blood_type' => 'O-',
                'component' => 'Packed Red Blood Cells', 'units_available' => 2,
                'expiration_date' => today()->addDays(18)->toDateString(),
            ]);
            $submitted = collect($reservations)->firstWhere('reference', 'REQ-2026-001');
            if ($submitted) {
                $this->notification($recipient, 'new-reservation-'.$recipient->id, BloodReservationSubmitted::class, [
                    'title' => 'New blood reservation', 'reservation_id' => $submitted->id,
                    'reference' => $submitted->reference, 'facility_id' => $submitted->facility_id,
                    'blood_type' => $submitted->blood_type, 'component' => $submitted->component,
                ]);
            }
        }
    }

    /** @param class-string $type */
    private function notification(User $user, string $key, string $type, array $data, bool $read = false): void
    {
        $id = substr(hash('sha256', 'cbis-showcase-'.$key.'-'.$user->id), 0, 32);
        $user->notifications()->firstOrCreate(['id' => $id], [
            'type' => $type, 'data' => $data,
            'read_at' => $read ? now() : null,
        ]);
    }
}
