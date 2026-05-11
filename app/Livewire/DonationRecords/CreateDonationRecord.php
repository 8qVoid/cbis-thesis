<?php

namespace App\Livewire\DonationRecords;

use App\Events\DonationRecorded;
use App\Models\DonationRecord;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\EventRegistration;
use App\Models\Facility;
use App\Models\User;
use App\Support\DonorScope;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateDonationRecord extends Component
{
    use LogsAudit;

    public array $donors = [];

    public array $events = [];

    public array $registeredDonors = [];

    public array $matchingDonors = [];

    public array $facilities = [];

    public bool $isCentralAdmin = false;

    public ?int $facility_id = null;

    public string|int|null $selected_event_id = null;

    public ?int $donor_id = null;

    public string $donor_search = '';

    public string $donation_no = '';

    public ?string $donated_at = null;

    public string $blood_type = '';

    public ?int $volume_ml = null;

    public ?string $expiration_date = null;

    public string $status = 'pending';

    public function mount(): void
    {
        $user = $this->currentUser();
        abort_unless($user->can('manage donation records'), 403);

        $this->isCentralAdmin = $user->isCentralAdmin();
        $this->donation_no = $this->generateDonationNumber();

        if ($this->isCentralAdmin) {
            $facilityCollection = Facility::query()->orderBy('name')->get();
            $this->facilities = $facilityCollection
                ->map(fn (Facility $facility): array => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                ])
                ->values()
                ->all();
            $this->facility_id = $this->facilities[0]['id'] ?? null;
        } else {
            $this->facility_id = $user->facility_id;
        }

        $this->loadDonors();
        $this->loadEvents();

        if (! empty($this->donors)) {
            $this->donor_id = (int) $this->donors[0]['id'];
            $this->blood_type = (string) $this->donors[0]['blood_type'];
        }
    }

    public function updatedFacilityId(): void
    {
        $this->selected_event_id = null;
        $this->registeredDonors = [];
        $this->loadDonors();
        $this->loadEvents();
        $this->refreshMatchingDonors();
    }

    public function updatedSelectedEventId(): void
    {
        $this->selected_event_id = $this->selected_event_id ? (int) $this->selected_event_id : null;
        $this->loadRegisteredDonors();
    }

    public function updatedDonorSearch(): void
    {
        $this->refreshMatchingDonors();
    }

    public function updatedDonorId($value): void
    {
        $selectedId = (int) $value;
        $donor = collect($this->donors)->firstWhere('id', $selectedId);

        if ($donor) {
            $this->blood_type = (string) $donor['blood_type'];
            $this->donor_search = (string) $donor['name'];
        }
    }

    public function selectDonor(int $donorId): void
    {
        $donor = collect($this->donors)
            ->merge($this->registeredDonors)
            ->firstWhere('id', $donorId);

        if (! $donor) {
            return;
        }

        $this->donor_id = (int) $donor['id'];
        $this->blood_type = (string) $donor['blood_type'];
        $this->donor_search = (string) $donor['name'];
        $this->refreshMatchingDonors();
    }

    public function save(): mixed
    {
        $user = $this->currentUser();
        $data = $this->validate($this->rules());

        if (! $this->isCentralAdmin) {
            $data['facility_id'] = $user->facility_id;
        }

        if ($this->selected_event_id !== null && ! $this->selectedEventRegistrationExists((int) $data['donor_id'], (int) $data['facility_id'])) {
            $this->addError('selected_event_id', 'The selected donor is not registered for this event.');

            return null;
        }

        unset($data['selected_event_id']);

        // Keep donation number fixed in UI but still protect against edge-case collision.
        if (DonationRecord::query()->where('donation_no', $data['donation_no'])->exists()) {
            $data['donation_no'] = $this->generateDonationNumber();
            $this->donation_no = $data['donation_no'];
        }

        $data['recorded_by'] = $user->id;
        $record = DonationRecord::create($data);

        $this->markEventRegistrationAsAttended($record);

        event(new DonationRecorded($record));
        $this->logAudit('donation_record.created', $record, $data);

        session()->flash('success', 'Donation recorded and inventory updated.');

        return redirect()->route('donation-records.index');
    }

    public function render()
    {
        return view('livewire.donation-records.create-donation-record');
    }

    protected function rules(): array
    {
        return [
            'facility_id' => [$this->isCentralAdmin ? 'required' : 'nullable', 'integer', 'exists:facilities,id'],
            'selected_event_id' => [
                'nullable',
                'integer',
                Rule::exists('donation_schedules', 'id')
                    ->where(fn ($query) => $query
                        ->where('facility_id', $this->facility_id)
                        ->whereIn('status', ['planned', 'ongoing'])),
            ],
            'donor_id' => ['required', 'integer', 'exists:donors,id'],
            'donation_no' => ['required', 'string', 'max:50', 'unique:donation_records,donation_no'],
            'donated_at' => ['required', 'date'],
            'blood_type' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'volume_ml' => ['required', 'integer', 'min:1', 'max:5000'],
            'expiration_date' => ['required', 'date', 'after:donated_at'],
            'status' => ['required', 'in:pending,verified,rejected'],
        ];
    }

    private function loadDonors(): void
    {
        $user = $this->currentUser();
        $query = DonorScope::apply(Donor::query()->orderBy('last_name')->orderBy('first_name'), $user);

        if ($this->isCentralAdmin && $this->facility_id !== null) {
            $facilityId = $this->facility_id;
            $query->where(function (Builder $builder) use ($facilityId): void {
                $builder->where('facility_id', $facilityId)
                    ->orWhereHas('donationRecords', fn (Builder $q) => $q->where('facility_id', $facilityId))
                    ->orWhereHas('eventRegistrations', fn (Builder $q) => $q->where('facility_id', $facilityId));
            });
        }

        $this->donors = $query->get()
            ->map(fn (Donor $donor): array => $this->donorOption($donor))
            ->values()
            ->all();
    }

    private function markEventRegistrationAsAttended(DonationRecord $record): void
    {
        if ($this->selected_event_id === null) {
            return;
        }

        EventRegistration::query()
            ->where('donation_schedule_id', $this->selected_event_id)
            ->where('donor_id', $record->donor_id)
            ->where('facility_id', $record->facility_id)
            ->where('status', 'registered')
            ->update(['status' => 'attended']);
    }

    private function selectedEventRegistrationExists(int $donorId, int $facilityId): bool
    {
        if ($this->selected_event_id === null) {
            return true;
        }

        return EventRegistration::query()
            ->where('donation_schedule_id', $this->selected_event_id)
            ->where('donor_id', $donorId)
            ->where('facility_id', $facilityId)
            ->where('status', 'registered')
            ->exists();
    }

    private function loadEvents(): void
    {
        $user = $this->currentUser();
        $query = FacilityScope::apply(
            DonationSchedule::query()
                ->withCount(['eventRegistrations as registered_count' => fn (Builder $q) => $q->where('status', 'registered')])
                ->whereIn('status', ['planned', 'ongoing'])
                ->orderByDesc('event_date'),
            $user
        );

        if ($this->isCentralAdmin && $this->facility_id !== null) {
            $query->where('facility_id', $this->facility_id);
        }

        $this->events = $query->get()
            ->map(fn (DonationSchedule $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->event_date?->toDateString(),
                'registered_count' => $event->registered_count,
            ])
            ->values()
            ->all();
    }

    private function loadRegisteredDonors(): void
    {
        $this->registeredDonors = [];

        if ($this->selected_event_id === null) {
            return;
        }

        $user = $this->currentUser();
        $query = EventRegistration::query()
            ->with('donor')
            ->where('donation_schedule_id', $this->selected_event_id)
            ->where('status', 'registered')
            ->orderBy('registered_at');

        if (! $user->isCentralAdmin()) {
            $query->where('facility_id', $user->facility_id);
        } elseif ($this->facility_id !== null) {
            $query->where('facility_id', $this->facility_id);
        }

        $this->registeredDonors = $query->get()
            ->filter(fn (EventRegistration $registration): bool => $registration->donor !== null)
            ->map(function (EventRegistration $registration): array {
                $donor = $registration->donor;

                return [
                    ...$this->donorOption($donor),
                    'registered_at' => $registration->registered_at?->format('Y-m-d H:i') ?? 'N/A',
                ];
            })
            ->values()
            ->all();
    }

    private function refreshMatchingDonors(): void
    {
        $search = Str::lower(trim($this->donor_search));

        if (strlen($search) < 2) {
            $this->matchingDonors = [];

            return;
        }

        $this->matchingDonors = collect($this->donors)
            ->filter(fn (array $donor): bool => str_contains(Str::lower($donor['name']), $search))
            ->take(6)
            ->values()
            ->all();
    }

    private function donorOption(Donor $donor): array
    {
        return [
            'id' => $donor->id,
            'name' => $donor->full_name,
            'blood_type' => $donor->blood_type,
        ];
    }

    private function generateDonationNumber(): string
    {
        do {
            $candidate = 'DN-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (DonationRecord::query()->where('donation_no', $candidate)->exists());

        return $candidate;
    }

    private function currentUser(): User
    {
        $user = Auth::guard('web')->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
