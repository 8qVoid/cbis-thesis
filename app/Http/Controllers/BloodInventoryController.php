<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodInventoryRequest;
use App\Http\Requests\UpdateBloodInventoryRequest;
use App\Models\AuditLog;
use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\DonationRecord;
use App\Models\Facility;
use App\Support\FacilityScope;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BloodInventoryController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $filters = request()->validate([
            'blood_type' => ['nullable', 'in:'.implode(',', BloodInventory::BLOOD_TYPES)],
            'component' => ['nullable', 'in:'.implode(',', array_keys(BloodInventory::COMPONENTS))],
            'status' => ['nullable', 'in:low_stock'],
            'expiring' => ['nullable', 'in:soon'],
        ]);
        $inventory = FacilityScope::apply(BloodInventory::query()->with(['facility', 'donationRecord']), auth()->user())
            ->when($filters['blood_type'] ?? null, fn ($query, $type) => $query->where('blood_type', $type))
            ->when($filters['component'] ?? null, fn ($query, $component) => $query->where('component', $component))
            ->when($filters['status'] ?? null, fn ($query) => $query->where('units_available', '>', 0)->where('units_available', '<=', 5)->where('status', '!=', 'expired')->whereDate('expiration_date', '>=', today()))
            ->when($filters['expiring'] ?? null, fn ($query) => $query->where('status', '!=', 'expired')->where('units_available', '>', 0)->whereDate('expiration_date', '>=', today())->whereDate('expiration_date', '<=', today()->addDays(14)))
            ->orderBy('blood_type')
            ->orderBy('expiration_date')
            ->paginate(20)->withQueryString();

        $scopedInventory = FacilityScope::apply(BloodInventory::query(), auth()->user());
        $storageRows = (clone $scopedInventory)
            ->selectRaw('blood_type, component, SUM(units_available) as units, MIN(expiration_date) as next_expiration, COUNT(*) as batch_count')
            ->where('units_available', '>', 0)->where('status', '!=', 'expired')
            ->whereDate('expiration_date', '>=', today())->groupBy('blood_type', 'component')->get()
            ->keyBy(fn ($row) => $row->blood_type.'|'.$row->component);
        $totalAvailableUnits = (int) $storageRows->sum('units');
        $lowStockGroups = $storageRows->filter(fn ($row) => (int) $row->units <= 5)->count();
        $expiringStorageGroups = $storageRows->filter(fn ($row) => $row->next_expiration && today()->diffInDays($row->next_expiration, false) <= 14)->count();

        $recentStockIns = FacilityScope::apply(DonationRecord::query()->with(['donor', 'inventory'])->whereHas('inventory'), auth()->user())
            ->latest('donated_at')->limit(20)->get()->map(fn ($record) => [
                'type' => 'in', 'date' => $record->donated_at, 'blood_type' => $record->blood_type,
                'component' => $record->inventory?->component_label ?? 'Donation stock',
                'units' => max(1, (int) floor($record->volume_ml / 450)),
                'source' => 'Donation '.$record->donation_no,
                'person' => auth()->user()->can('manage donation records') ? ($record->donor?->full_name ?? 'Recorded donor') : 'Verified donation',
                'url' => auth()->user()->can('manage donation records')
                    ? route('donation-records.show', $record)
                    : route('blood-inventory.show', $record->inventory),
            ]);
        $recentStockOuts = FacilityScope::apply(BloodRelease::query()->with(['inventory', 'reservation']), auth()->user())
            ->latest('released_at')->limit(20)->get()->map(fn ($release) => [
                'type' => 'out', 'date' => $release->released_at, 'blood_type' => $release->inventory?->blood_type ?? '-',
                'component' => $release->inventory?->component_label ?? 'Released stock', 'units' => $release->units_released,
                'source' => $release->reservation?->reference ? 'Reservation '.$release->reservation->reference : 'Direct release',
                'person' => $release->patient_name, 'url' => route('blood-releases.show', $release),
            ]);
        $adjustments = AuditLog::query()->where('auditable_type', BloodInventory::class)
            ->whereIn('auditable_id', (clone $scopedInventory)->withTrashed()->select('id'))
            ->whereIn('action', ['blood_inventory.created', 'blood_inventory.updated', 'blood_inventory.deleted'])
            ->whereNotNull('details->movement_delta')->where('details->movement_delta', '!=', 0)
            ->latest()->limit(20)->get()->map(fn ($log) => [
                'type' => $log->details['movement_delta'] > 0 ? 'in' : 'out', 'date' => $log->created_at,
                'blood_type' => $log->details['blood_type'],
                'component' => BloodInventory::COMPONENTS[$log->details['component']] ?? $log->details['component'],
                'units' => abs($log->details['movement_delta']), 'source' => 'Manual adjustment #'.$log->auditable_id,
                'person' => 'Balance '.$log->details['before_units'].' to '.$log->details['units_available'],
                'url' => $log->action === 'blood_inventory.deleted' ? route('blood-inventory.index') : route('blood-inventory.show', $log->auditable_id),
            ]);
        $stockMovements = $recentStockIns->concat($recentStockOuts)->concat($adjustments)
            ->sortByDesc(fn ($movement) => $movement['date']?->timestamp ?? 0)->take(20)->values();

        return view('blood-inventory.index', compact('inventory', 'storageRows', 'totalAvailableUnits', 'lowStockGroups', 'expiringStorageGroups', 'stockMovements'));
    }

    public function storage(Request $request): View
    {
        $filters = $request->validate([
            'blood_type' => ['required', 'string', 'in:'.implode(',', BloodInventory::BLOOD_TYPES)],
            'component' => ['required', 'string', 'in:'.implode(',', array_keys(BloodInventory::COMPONENTS))],
            'expiration_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:oldest,latest'],
        ]);
        $bloodType = $filters['blood_type'];
        $componentKey = $filters['component'];
        $componentLabel = BloodInventory::COMPONENTS[$componentKey];
        $selectedExpiry = $filters['expiration_date'] ?? null;
        $sort = $filters['sort'] ?? 'oldest';

        $stockQuery = FacilityScope::apply(BloodInventory::query(), auth()->user())
            ->where('blood_type', $bloodType)->where('component', $componentKey)
            ->where('units_available', '>', 0)->where('status', '!=', 'expired')
            ->whereDate('expiration_date', '>=', today());

        $expiryGroups = (clone $stockQuery)
            ->selectRaw('expiration_date, SUM(units_available) as units, COUNT(*) as batch_count')
            ->groupBy('expiration_date')->orderBy('expiration_date')->get();
        $totalUnits = (int) $expiryGroups->sum('units');
        $batchCount = (int) $expiryGroups->sum('batch_count');
        $filteredUnits = $selectedExpiry === null
            ? $totalUnits
            : (int) ($expiryGroups->first(fn ($group) => $group->expiration_date->toDateString() === $selectedExpiry)?->units ?? 0);

        $batches = (clone $stockQuery)
            ->with(['donationRecord:id,donation_no', 'facility'])
            ->when($selectedExpiry, fn ($query, $date) => $query->whereDate('expiration_date', $date))
            ->orderBy('expiration_date', $sort === 'latest' ? 'desc' : 'asc')->orderBy('id')
            ->paginate(20)->withQueryString();

        return view('blood-inventory.storage', compact(
            'bloodType', 'componentKey', 'componentLabel', 'totalUnits', 'batchCount',
            'expiryGroups', 'selectedExpiry', 'sort', 'batches', 'filteredUnits',
        ));
    }

    public function create(): View
    {
        $facilities = auth()->user()->isCentralAdmin()
            ? Facility::orderBy('name')->get()
            : Facility::query()->whereKey(auth()->user()->facility_id)->get();

        return view('blood-inventory.create', compact('facilities'));
    }

    public function store(StoreBloodInventoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['donation_record_id'])) {
            $donationRecord = FacilityScope::apply(DonationRecord::query(), auth()->user())
                ->findOrFail($data['donation_record_id']);
            $data['facility_id'] = $donationRecord->facility_id;
        } elseif (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        DB::transaction(function () use ($data, $request) {
            Facility::whereKey($data['facility_id'])->lockForUpdate()->firstOrFail();
            $this->preventDuplicate($data);
            $record = BloodInventory::create($data);
            $this->logAudit('blood_inventory.created', $record, $data + ['before_units' => 0, 'movement_delta' => (int) $data['units_available']], $request);
        });

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
        $facilities = auth()->user()->isCentralAdmin()
            ? Facility::orderBy('name')->get()
            : Facility::query()->whereKey(auth()->user()->facility_id)->get();

        return view('blood-inventory.edit', compact('bloodInventory', 'facilities'));
    }

    public function update(UpdateBloodInventoryRequest $request, BloodInventory $bloodInventory): RedirectResponse
    {
        $this->authorizeRecord($bloodInventory);

        $data = $request->validated();
        if (! empty($data['donation_record_id'])) {
            $donationRecord = FacilityScope::apply(DonationRecord::query(), auth()->user())
                ->findOrFail($data['donation_record_id']);
            $data['facility_id'] = $donationRecord->facility_id;
        } elseif (! auth()->user()->isCentralAdmin()) {
            $data['facility_id'] = auth()->user()->facility_id;
        }

        DB::transaction(function () use ($data, $request, $bloodInventory) {
            Facility::whereKey($bloodInventory->facility_id)->lockForUpdate()->firstOrFail();
            $record = BloodInventory::whereKey($bloodInventory->id)->lockForUpdate()->firstOrFail();
            foreach (['facility_id', 'blood_type', 'component'] as $field) {
                if ((string) $data[$field] !== (string) $record->$field) {
                    throw ValidationException::withMessages([$field => 'The facility, blood type, and component identify this stock and cannot be changed. Record separate stock instead.']);
                }
            }
            if (array_key_exists('donation_record_id', $data) && $data['donation_record_id'] != $record->donation_record_id) {
                throw ValidationException::withMessages(['donation_record_id' => 'The source donation cannot be replaced.']);
            }
            $this->preventDuplicate($data, $record);
            $before = (int) $record->units_available;
            $record->update($data);
            $this->logAudit('blood_inventory.updated', $record, $data + ['before_units' => $before, 'movement_delta' => (int) $data['units_available'] - $before], $request);
        });

        return redirect()->route('blood-inventory.index')->with('success', 'Inventory updated.');
    }

    public function destroy(BloodInventory $bloodInventory): RedirectResponse
    {
        $this->authorizeRecord($bloodInventory);
        DB::transaction(function () use ($bloodInventory) {
            Facility::whereKey($bloodInventory->facility_id)->lockForUpdate()->firstOrFail();
            $record = BloodInventory::whereKey($bloodInventory->id)->lockForUpdate()->firstOrFail();
            $details = ['blood_type' => $record->blood_type, 'component' => $record->component,
                'before_units' => (int) $record->units_available, 'units_available' => 0,
                'movement_delta' => -(int) $record->units_available];
            $record->delete();
            $this->logAudit('blood_inventory.deleted', $record, $details);
        });

        return redirect()->route('blood-inventory.index')->with('success', 'Inventory entry deleted.');
    }

    private function preventDuplicate(array $data, ?BloodInventory $record = null): void
    {
        if ($record?->donation_record_id || ! empty($data['donation_record_id'])) {
            return;
        }
        $exists = BloodInventory::where('facility_id', $data['facility_id'])->whereNull('donation_record_id')
            ->where('blood_type', $data['blood_type'])->where('component', $data['component'])
            ->whereDate('expiration_date', $data['expiration_date'])
            ->when($record, fn ($query) => $query->whereKeyNot($record->id))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['expiration_date' => 'Manual stock with this facility, blood type, component, and expiration date already exists. Edit its unit balance instead of adding a duplicate.']);
        }
    }

    private function authorizeRecord(BloodInventory $record): void
    {
        if (! auth()->user()->isCentralAdmin() && $record->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }
    }
}
