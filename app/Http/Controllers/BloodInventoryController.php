<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodInventoryRequest;
use App\Http\Requests\UpdateBloodInventoryRequest;
use App\Models\BloodInventory;
use App\Models\Facility;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BloodInventoryController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $inventory = FacilityScope::apply(BloodInventory::query()->with('facility'), auth()->user())
            ->orderBy('blood_type')
            ->orderBy('expiration_date')
            ->paginate(20);

        return view('blood-inventory.index', compact('inventory'));
    }

    public function create(): View
    {
        $facilities = Facility::orderBy('name')->get();

        return view('blood-inventory.create', compact('facilities'));
    }

    public function store(StoreBloodInventoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        $record = BloodInventory::create($data);
        $this->logAudit('blood_inventory.created', $record, $data, $request);

        return redirect()->route('blood-inventory.index')->with('success', 'Inventory record created.');
    }

    public function show(BloodInventory $bloodInventory): View
    {
        $this->authorizeRecord($bloodInventory);

        return view('blood-inventory.show', compact('bloodInventory'));
    }

    public function edit(BloodInventory $bloodInventory): View
    {
        $this->authorizeRecord($bloodInventory);
        $facilities = Facility::orderBy('name')->get();

        return view('blood-inventory.edit', compact('bloodInventory', 'facilities'));
    }

    public function update(UpdateBloodInventoryRequest $request, BloodInventory $bloodInventory): RedirectResponse
    {
        $this->authorizeRecord($bloodInventory);

        $data = $request->validated();
        if (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        $bloodInventory->update($data);
        $this->logAudit('blood_inventory.updated', $bloodInventory, $data, $request);

        return redirect()->route('blood-inventory.index')->with('success', 'Inventory updated.');
    }

    public function destroy(BloodInventory $bloodInventory): RedirectResponse
    {
        $this->authorizeRecord($bloodInventory);
        $bloodInventory->delete();
        $this->logAudit('blood_inventory.deleted', $bloodInventory);

        return redirect()->route('blood-inventory.index')->with('success', 'Inventory entry deleted.');
    }

    private function authorizeRecord(BloodInventory $record): void
    {
        if (! auth()->user()->isCentralAdmin() && $record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }
}
