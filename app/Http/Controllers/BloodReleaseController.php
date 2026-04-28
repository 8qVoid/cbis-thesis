<?php

namespace App\Http\Controllers;

use App\Events\BloodReleased;
use App\Http\Requests\StoreBloodReleaseRequest;
use App\Http\Requests\UpdateBloodReleaseRequest;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\Facility;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BloodReleaseController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $releases = FacilityScope::apply(BloodRelease::query()->with(['facility', 'inventory']), auth()->user())
            ->latest('released_at')
            ->paginate(15);

        return view('blood-releases.index', compact('releases'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $inventory = FacilityScope::apply(BloodInventory::query()->with('facility')->where('status', '!=', 'expired'), $user)->get();
        $facilities = Facility::orderBy('name')->get();

        return view('blood-releases.create', compact('inventory', 'facilities'));
    }

    public function store(StoreBloodReleaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $inventory = FacilityScope::apply(BloodInventory::query(), auth()->user())
            ->findOrFail($data['blood_inventory_id']);
        $data['facility_id'] = $inventory->facility_id;
        $data['released_by'] = auth()->id();

        $release = BloodRelease::create($data);
        event(new BloodReleased($release));

        $this->logAudit('blood_release.created', $release, $data, $request);

        return redirect()->route('blood-releases.index')->with('success', 'Blood release recorded and inventory updated.');
    }

    public function show(BloodRelease $bloodRelease): View
    {
        $this->authorizeRecord($bloodRelease);

        return view('blood-releases.show', compact('bloodRelease'));
    }

    public function edit(BloodRelease $bloodRelease): View
    {
        $this->authorizeRecord($bloodRelease);
        $user = auth()->user();
        $inventory = FacilityScope::apply(BloodInventory::query()->with('facility')->where('status', '!=', 'expired'), $user)->get();
        $facilities = Facility::orderBy('name')->get();

        return view('blood-releases.edit', compact('bloodRelease', 'inventory', 'facilities'));
    }

    public function update(UpdateBloodReleaseRequest $request, BloodRelease $bloodRelease): RedirectResponse
    {
        $this->authorizeRecord($bloodRelease);

        $data = $request->validated();
        $inventory = FacilityScope::apply(BloodInventory::query(), auth()->user())
            ->findOrFail($data['blood_inventory_id']);
        $data['facility_id'] = $inventory->facility_id;

        $bloodRelease->update($data);
        $this->logAudit('blood_release.updated', $bloodRelease, $data, $request);

        return redirect()->route('blood-releases.index')->with('success', 'Blood release updated.');
    }

    public function destroy(BloodRelease $bloodRelease): RedirectResponse
    {
        $this->authorizeRecord($bloodRelease);
        $bloodRelease->delete();
        $this->logAudit('blood_release.deleted', $bloodRelease);

        return redirect()->route('blood-releases.index')->with('success', 'Blood release deleted.');
    }

    private function authorizeRecord(BloodRelease $record): void
    {
        if (! auth()->user()->isCentralAdmin() && $record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }
}
