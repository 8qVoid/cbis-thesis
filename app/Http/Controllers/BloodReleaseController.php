<?php

namespace App\Http\Controllers;

use App\Events\BloodReleased;
use App\Http\Requests\StoreBloodReleaseRequest;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
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
        $inventory = FacilityScope::apply(
            BloodInventory::query()
                ->with('facility')
                ->where('status', '!=', 'expired')
                ->where('units_available', '>', 0)
                ->whereDate('expiration_date', '>=', now()->toDateString()),
            $user
        )->get();

        return view('blood-releases.create', compact('inventory'));
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

    private function authorizeRecord(BloodRelease $record): void
    {
        if (! auth()->user()->isCentralAdmin() && $record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }
}
