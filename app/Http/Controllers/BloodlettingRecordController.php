<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodlettingRecordRequest;
use App\Http\Requests\UpdateBloodlettingRecordRequest;
use App\Models\BloodlettingRecord;
use App\Models\DonationRecord;
use App\Models\Facility;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BloodlettingRecordController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $records = FacilityScope::apply(BloodlettingRecord::query()->with(['facility', 'donationRecord']), auth()->user())
            ->latest('bloodletting_at')
            ->paginate(15);

        return view('bloodletting-records.index', compact('records'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $donationRecords = FacilityScope::apply(DonationRecord::query()->orderByDesc('donated_at'), $user)->get();
        $facilities = Facility::orderBy('name')->get();

        return view('bloodletting-records.create', compact('donationRecords', 'facilities'));
    }

    public function store(StoreBloodlettingRecordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }
        $data['medical_technologist_id'] = auth()->id();

        $record = BloodlettingRecord::create($data);
        $this->logAudit('bloodletting_record.created', $record, $data, $request);

        return redirect()->route('bloodletting-records.index')->with('success', 'Bloodletting record saved.');
    }

    public function show(BloodlettingRecord $bloodlettingRecord): View
    {
        $this->authorizeRecord($bloodlettingRecord);

        return view('bloodletting-records.show', compact('bloodlettingRecord'));
    }

    public function edit(BloodlettingRecord $bloodlettingRecord): View
    {
        $this->authorizeRecord($bloodlettingRecord);
        $user = auth()->user();
        $donationRecords = FacilityScope::apply(DonationRecord::query()->orderByDesc('donated_at'), $user)->get();
        $facilities = Facility::orderBy('name')->get();

        return view('bloodletting-records.edit', compact('bloodlettingRecord', 'donationRecords', 'facilities'));
    }

    public function update(UpdateBloodlettingRecordRequest $request, BloodlettingRecord $bloodlettingRecord): RedirectResponse
    {
        $this->authorizeRecord($bloodlettingRecord);

        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        $bloodlettingRecord->update($data);
        $this->logAudit('bloodletting_record.updated', $bloodlettingRecord, $data, $request);

        return redirect()->route('bloodletting-records.index')->with('success', 'Bloodletting record updated.');
    }

    public function destroy(BloodlettingRecord $bloodlettingRecord): RedirectResponse
    {
        $this->authorizeRecord($bloodlettingRecord);
        $bloodlettingRecord->delete();
        $this->logAudit('bloodletting_record.deleted', $bloodlettingRecord);

        return redirect()->route('bloodletting-records.index')->with('success', 'Bloodletting record deleted.');
    }

    private function authorizeRecord(BloodlettingRecord $record): void
    {
        if (! auth()->user()->isCentralAdmin() && $record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }
}
