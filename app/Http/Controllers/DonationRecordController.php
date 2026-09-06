<?php

namespace App\Http\Controllers;

use App\Events\DonationRecorded;
use App\Http\Requests\StoreDonationRecordRequest;
use App\Http\Requests\UpdateDonationRecordRequest;
use App\Models\BloodInventory;
use App\Models\DonationRecord;
use App\Models\Donor;
use App\Models\Facility;
use App\Support\DonorScope;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DonationRecordController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $records = FacilityScope::apply(DonationRecord::query()->with(['facility', 'donor']), auth()->user())
            ->latest('donated_at')
            ->paginate(15);

        return view('donation-records.index', compact('records'));
    }

    public function create(): View
    {
        return view('donation-records.create');
    }

    public function store(StoreDonationRecordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }
        $data['recorded_by'] = auth()->id();
        $record = DonationRecord::create($data);

        event(new DonationRecorded($record));
        $this->logAudit('donation_record.created', $record, $data, $request);

        return redirect()->route('donation-records.index')->with('success', $record->status === 'verified' ? 'Verified donation recorded and inventory added.' : 'Donation recorded. Inventory is added only after verification.');
    }

    public function show(DonationRecord $donationRecord): View
    {
        $this->authorizeRecord($donationRecord);

        return view('donation-records.show', compact('donationRecord'));
    }

    public function edit(DonationRecord $donationRecord): View
    {
        $this->authorizeRecord($donationRecord);
        $user = auth()->user();
        $donors = DonorScope::apply(Donor::query()->orderBy('last_name'), $user)->get();
        $facilities = Facility::orderBy('name')->get();

        return view('donation-records.edit', compact('donationRecord', 'donors', 'facilities'));
    }

    public function update(UpdateDonationRecordRequest $request, DonationRecord $donationRecord): RedirectResponse
    {
        $this->authorizeRecord($donationRecord);

        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        if (BloodInventory::withTrashed()->where('donation_record_id', $donationRecord->id)->exists()) {
            $candidate = clone $donationRecord;
            $candidate->fill($data);
            foreach (['donor_id', 'facility_id', 'blood_type', 'volume_ml', 'expiration_date', 'status'] as $field) {
                if ($candidate->isDirty($field)) {
                    throw ValidationException::withMessages([$field => 'This donation has already created inventory. Its donor, stock details and verification status cannot be changed here.']);
                }
            }
        }
        $donationRecord->update($data);
        event(new DonationRecorded($donationRecord->fresh()));
        $this->logAudit('donation_record.updated', $donationRecord, $data, $request);

        return redirect()->route('donation-records.index')->with('success', 'Donation record updated.');
    }

    public function destroy(DonationRecord $donationRecord): RedirectResponse
    {
        $this->authorizeRecord($donationRecord);
        if (BloodInventory::withTrashed()->where('donation_record_id', $donationRecord->id)->exists()) {
            return back()->withErrors(['donation' => 'A donation linked to inventory cannot be deleted. Keep it for traceability.']);
        }
        $donationRecord->delete();
        $this->logAudit('donation_record.deleted', $donationRecord);

        return redirect()->route('donation-records.index')->with('success', 'Donation record deleted.');
    }

    private function authorizeRecord(DonationRecord $record): void
    {
        if (! auth()->user()->isCentralAdmin() && $record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }
}
