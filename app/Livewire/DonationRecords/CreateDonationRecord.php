<?php

namespace App\Livewire\DonationRecords;

use App\Events\DonationRecorded;
use App\Models\DonationRecord;
use App\Models\Donor;
use App\Models\Facility;
use App\Models\User;
use App\Support\DonorScope;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class CreateDonationRecord extends Component
{
    use LogsAudit;

    public array $donors = [];
    public array $facilities = [];
    public bool $isCentralAdmin = false;

    public ?int $facility_id = null;
    public ?int $donor_id = null;
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

        $donorCollection = DonorScope::apply(Donor::query()->orderBy('last_name'), $user)->get();
        $this->donors = $donorCollection
            ->map(fn (Donor $donor): array => [
                'id' => $donor->id,
                'name' => $donor->full_name,
                'blood_type' => $donor->blood_type,
            ])
            ->values()
            ->all();

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

        if (! empty($this->donors)) {
            $this->donor_id = (int) $this->donors[0]['id'];
            $this->blood_type = (string) $this->donors[0]['blood_type'];
        }
    }

    public function updatedDonorId($value): void
    {
        $selectedId = (int) $value;
        $donor = collect($this->donors)->firstWhere('id', $selectedId);

        if ($donor) {
            $this->blood_type = (string) $donor['blood_type'];
        }
    }

    public function save(): mixed
    {
        $user = $this->currentUser();
        $data = $this->validate($this->rules());

        if (! $this->isCentralAdmin) {
            $data['facility_id'] = $user->facility_id;
        }

        // Keep donation number fixed in UI but still protect against edge-case collision.
        if (DonationRecord::query()->where('donation_no', $data['donation_no'])->exists()) {
            $data['donation_no'] = $this->generateDonationNumber();
            $this->donation_no = $data['donation_no'];
        }

        $data['recorded_by'] = $user->id;
        $record = DonationRecord::create($data);

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
            'donor_id' => ['required', 'integer', 'exists:donors,id'],
            'donation_no' => ['required', 'string', 'max:50', 'unique:donation_records,donation_no'],
            'donated_at' => ['required', 'date'],
            'blood_type' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'volume_ml' => ['required', 'integer', 'min:1', 'max:5000'],
            'expiration_date' => ['required', 'date', 'after:donated_at'],
            'status' => ['required', 'in:pending,verified,rejected'],
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
