<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodBankLocationRequest;
use App\Http\Requests\UpdateBloodBankLocationRequest;
use App\Models\BloodBankLocation;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BloodBankLocationController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $this->authorizeFacilityLocations();

        $locations = FacilityScope::apply(BloodBankLocation::query()->with('facility'), auth()->user())
            ->latest()
            ->paginate(15);
        $existingLocation = $this->existingFacilityLocation();

        return view('blood-bank-locations.index', compact('locations', 'existingLocation'));
    }

    public function create(): RedirectResponse|View
    {
        $this->authorizeFacilityLocations();

        $existingLocation = $this->existingFacilityLocation();

        if ($existingLocation) {
            return redirect()
                ->route('blood-bank-locations.edit', $existingLocation)
                ->with('success', 'Location already set. Edit the current location instead.');
        }

        return view('blood-bank-locations.create');
    }

    public function store(StoreBloodBankLocationRequest $request): RedirectResponse
    {
        $this->authorizeFacilityLocations();

        $existingLocation = $this->existingFacilityLocation();

        if ($existingLocation) {
            return redirect()
                ->route('blood-bank-locations.edit', $existingLocation)
                ->with('success', 'Location already set. Edit the current location instead.');
        }

        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('location-photos', 'public');
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

        return view('blood-bank-locations.edit', compact('bloodBankLocation'));
    }

    public function update(UpdateBloodBankLocationRequest $request, BloodBankLocation $bloodBankLocation): RedirectResponse
    {
        $this->authorizeRecord($bloodBankLocation);

        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            if ($bloodBankLocation->photo_path) {
                Storage::disk('public')->delete($bloodBankLocation->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('location-photos', 'public');
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
        $this->authorizeFacilityLocations();

        if ($record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }

    private function authorizeFacilityLocations(): void
    {
        if (auth()->user()?->isCentralAdmin()) {
            abort(403);
        }
    }

    private function existingFacilityLocation(): ?BloodBankLocation
    {
        return BloodBankLocation::query()
            ->where('facility_id', auth()->user()?->facility_id)
            ->first();
    }
}
