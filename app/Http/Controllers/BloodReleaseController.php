<?php

namespace App\Http\Controllers;

use App\Events\BloodReleased;
use App\Http\Requests\StoreBloodReleaseRequest;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\BloodReservation;
use App\Models\Facility;
use App\Notifications\BloodReservationStatusChanged;
use App\Support\FacilityScope;
use App\Support\ReservedStock;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $reservation = null;
        if (request()->filled('reservation')) {
            $reservation = BloodReservation::where('facility_id', $user->facility_id)
                ->where('status', 'approved')->findOrFail(request()->integer('reservation'));
        }
        $inventory = FacilityScope::apply(
            BloodInventory::query()
                ->when($reservation, fn ($query) => $query->where('blood_type', $reservation->blood_type)->where('component', $reservation->component))
                ->with('facility')
                ->where('status', '!=', 'expired')
                ->where('units_available', '>', 0)
                ->whereDate('expiration_date', '>=', now()->toDateString()),
            $user
        )->get();

        return view('blood-releases.create', compact('inventory', 'reservation'));
    }

    public function store(StoreBloodReleaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $inventory = FacilityScope::apply(BloodInventory::query(), auth()->user())
            ->findOrFail($data['blood_inventory_id']);
        $data['facility_id'] = $inventory->facility_id;
        $data['released_by'] = auth()->id();

        $release = DB::transaction(function () use ($data) {
            // Serialize allocations and releases within the main chapter.
            Facility::whereKey($data['facility_id'])->lockForUpdate()->firstOrFail();
            $stock = BloodInventory::whereKey($data['blood_inventory_id'])->lockForUpdate()->firstOrFail();
            $reservation = null;
            if (! empty($data['blood_reservation_id'])) {
                $reservation = BloodReservation::where('facility_id', $data['facility_id'])
                    ->lockForUpdate()->findOrFail($data['blood_reservation_id']);
                if ($reservation->status !== 'approved' || $reservation->blood_type !== $stock->blood_type || $reservation->component !== $stock->component) {
                    throw ValidationException::withMessages(['blood_reservation_id' => 'Select approved reservation stock with the exact requested blood type and component.']);
                }
                $remaining = $reservation->units_requested - $reservation->releases()->sum('units_released');
                if ($data['units_released'] > $remaining) {
                    throw ValidationException::withMessages(['units_released' => 'This exceeds the remaining units on the reservation.']);
                }
                $data['patient_name'] = $reservation->patient->name;
            }
            $totalAvailable = BloodInventory::where('facility_id', $data['facility_id'])
                ->where('blood_type', $stock->blood_type)->where('component', $stock->component)
                ->whereIn('status', ['active', 'low_stock'])->whereDate('expiration_date', '>=', today())->sum('units_available');
            $reserved = ReservedStock::outstanding($data['facility_id'], $stock->blood_type, $stock->component, $reservation?->id);
            if ($totalAvailable - $reserved < $data['units_released']) {
                throw ValidationException::withMessages(['units_released' => 'These units are committed to other approved reservations. Open the relevant reservation to release its blood.']);
            }
            $release = BloodRelease::create($data);
            event(new BloodReleased($release));
            if ($reservation && $reservation->releases()->sum('units_released') >= $reservation->units_requested) {
                $reservation->update(['status' => 'fulfilled', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
            }

            return $release;
        });

        $this->logAudit('blood_release.created', $release, $data, $request);

        if ($release->reservation?->status === 'fulfilled') {
            try {
                $release->reservation->patient->notify(new BloodReservationStatusChanged($release->reservation));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($release->blood_reservation_id) {
            return redirect()->route('reservations.show', $release->blood_reservation_id)->with('success', 'Release recorded and stock deducted. The reservation is fulfilled when all requested units have been released.');
        }

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
