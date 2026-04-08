<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodBankLocationRequest;
use App\Http\Requests\UpdateBloodBankLocationRequest;
use App\Models\BloodBankLocation;
use App\Models\Facility;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BloodBankLocationController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $locations = FacilityScope::apply(BloodBankLocation::query()->with('facility'), auth()->user())
            ->latest()
            ->paginate(15);

        return view('blood-bank-locations.index', compact('locations'));
    }

    public function create(): View
    {
        $facilities = Facility::orderBy('name')->get();

        return view('blood-bank-locations.create', compact('facilities'));
    }

    public function store(StoreBloodBankLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        $location = BloodBankLocation::create($data);
        $this->logAudit('blood_bank_location.created', $location, $data, $request);

        return redirect()->route('blood-bank-locations.index')->with('success', 'Location saved.');
    }

    public function show(BloodBankLocation $bloodBankLocation): View
    {
        $this->authorizeRecord($bloodBankLocation);

        return view('blood-bank-locations.show', compact('bloodBankLocation'));
    }

    public function edit(BloodBankLocation $bloodBankLocation): View
    {
        $this->authorizeRecord($bloodBankLocation);
        $facilities = Facility::orderBy('name')->get();

        return view('blood-bank-locations.edit', compact('bloodBankLocation', 'facilities'));
    }

    public function update(UpdateBloodBankLocationRequest $request, BloodBankLocation $bloodBankLocation): RedirectResponse
    {
        $this->authorizeRecord($bloodBankLocation);

        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        $bloodBankLocation->update($data);
        $this->logAudit('blood_bank_location.updated', $bloodBankLocation, $data, $request);

        return redirect()->route('blood-bank-locations.index')->with('success', 'Location updated.');
    }

    public function destroy(BloodBankLocation $bloodBankLocation): RedirectResponse
    {
        $this->authorizeRecord($bloodBankLocation);
        $bloodBankLocation->delete();
        $this->logAudit('blood_bank_location.deleted', $bloodBankLocation);

        return redirect()->route('blood-bank-locations.index')->with('success', 'Location deleted.');
    }

    private function authorizeRecord(BloodBankLocation $record): void
    {
        if (! auth()->user()->isCentralAdmin() && $record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }
}
