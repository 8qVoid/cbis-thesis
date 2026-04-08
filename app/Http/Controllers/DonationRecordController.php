<?php

namespace App\Http\Controllers;

use App\Events\DonationRecorded;
use App\Http\Requests\StoreDonationRecordRequest;
use App\Http\Requests\UpdateDonationRecordRequest;
use App\Models\DonationRecord;
use App\Models\Donor;
use App\Models\Facility;
use App\Support\DonorScope;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
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

        return redirect()->route('donation-records.index')->with('success', 'Donation recorded and inventory updated.');
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

        $donationRecord->update($data);
        $this->logAudit('donation_record.updated', $donationRecord, $data, $request);

        return redirect()->route('donation-records.index')->with('success', 'Donation record updated.');
    }

    public function destroy(DonationRecord $donationRecord): RedirectResponse
    {
        $this->authorizeRecord($donationRecord);
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
