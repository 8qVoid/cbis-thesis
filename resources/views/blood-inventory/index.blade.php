@extends('layouts.app')

@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageInventory = $currentUser?->can('manage inventory') ?? false;
    $components = \App\Models\BloodInventory::COMPONENTS;
    $bloodTypes = \App\Models\BloodInventory::BLOOD_TYPES;
    $statusClass = fn (int $units): string => $units === 0 ? 'cbis-tone-info' : ($units <= 5 ? 'cbis-tone-warning' : 'cbis-tone-success');
    $statusLabel = fn (int $units): string => $units === 0 ? 'No stock' : ($units <= 5 ? 'Low stock' : 'In storage');
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Blood Inventory</h1>
        <p class="cbis-page-subtitle">Monitor stored blood, review batches, and track stock movements.</p>
    </div>
    @if($canManageInventory)
        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <a href="{{ route('donation-records.create') }}" class="btn btn-danger">Record Donation</a>
            <a href="{{ route('blood-inventory.create') }}" class="btn btn-outline-secondary">Add Manual Stock</a>
        </div>
    @endif
</div>

<nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Inventory sections">
    <a class="btn btn-sm btn-outline-secondary" href="#current-storage">Current storage</a>
    <a class="btn btn-sm btn-outline-secondary" href="#stock-batches">Stock records</a>
    <a class="btn btn-sm btn-outline-secondary" href="#stock-movements">Stock in / out</a>
</nav>

<div class="cbis-inventory-summary-grid mb-3">
    <section class="card cbis-inventory-summary-card">
        <small>Units in storage</small>
        <strong>{{ $totalAvailableUnits }}</strong>
        <span>Non-expired units, including reserved stock</span>
    </section>
    <section class="card cbis-inventory-summary-card">
        <small>Low-stock groups</small>
        <strong>{{ $lowStockGroups }}</strong>
        <span>Blood type/component groups at 5 units or fewer</span>
    </section>
    <section class="card cbis-inventory-summary-card">
        <small>Expiring soon</small>
        <strong>{{ $expiringStorageGroups }}</strong>
        <span>Groups with nearest expiry within 14 days</span>
    </section>
</div>

<section id="current-storage" class="card mb-4">
    <div class="card-header cbis-card-title">
        <span>Current Storage</span>
        <small class="text-muted">Grouped by blood type and component</small>
    </div>
    <div class="card-body">
        <div class="cbis-storage-grid" role="table" aria-label="Current blood storage by blood type and component">
            <div class="cbis-storage-header" role="row">
                <span role="columnheader">Blood type</span>
                @foreach($components as $componentLabel)
                    <span role="columnheader">{{ $componentLabel }}</span>
                @endforeach
            </div>
            @foreach($bloodTypes as $bloodType)
                <div class="cbis-storage-row" role="row">
                    <strong role="rowheader">{{ $bloodType }}</strong>
                    @foreach($components as $componentKey => $componentLabel)
                        @php($stock = $storageRows->get($bloodType.'|'.$componentKey))
                        @php($units = (int) ($stock?->units ?? 0))
                        <a
                            href="{{ route('blood-inventory.index', ['blood_type' => $bloodType, 'component' => $componentKey]).'#stock-batches' }}"
                            class="cbis-storage-cell"
                            role="cell"
                            aria-label="{{ $bloodType }} {{ $componentLabel }}: {{ $units }} units"
                        >
                            <span class="cbis-storage-units">{{ $units }}</span>
                            <span class="cbis-inline-status {{ $statusClass($units) }}">{{ $statusLabel($units) }}</span>
                            @if($stock?->next_expiration)
                                <small>Next expiry {{ \Illuminate\Support\Carbon::parse($stock->next_expiration)->format('M d, Y') }}</small>
                            @else
                                <small>No available stock</small>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="stock-batches" class="card">
    <div class="card-header cbis-card-title">
        <span>Stock Records</span>
        <small class="text-muted">Current balance and expiry for each recorded batch</small>
    </div>
    <form method="GET" action="{{ route('blood-inventory.index') }}#stock-batches" class="card-body border-bottom cbis-filter-card" data-auto-filter="true" data-auto-search="true">
        <div class="row g-2 align-items-end">
            <div class="col-sm-4"><label class="form-label" for="stock-type">Blood type</label><select id="stock-type" name="blood_type" class="form-select"><option value="">All blood types</option>@foreach($bloodTypes as $type)<option @selected(request('blood_type') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div class="col-sm-5"><label class="form-label" for="stock-component">Component</label><select id="stock-component" name="component" class="form-select"><option value="">All components</option>@foreach($components as $value => $label)<option value="{{ $value }}" @selected(request('component') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-sm-3 d-flex gap-2"><button class="btn btn-danger js-auto-filter-submit">Filter</button><a href="{{ route('blood-inventory.index') }}#stock-batches" class="btn btn-outline-secondary">Reset</a></div>
        </div>
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}"><small class="d-block mt-2">Showing non-expired batches with 1–5 units.</small>@endif
        @if(request('expiring'))<input type="hidden" name="expiring" value="{{ request('expiring') }}"><small class="d-block mt-2">Showing stock expiring within 14 days.</small>@endif
    </form>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Blood Type</th>
                    <th>Component</th>
                    <th>Remaining units</th>
                    <th>Expiry</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventory as $item)
                    <tr>
                        <td>{{ $item->blood_type }}</td>
                        <td>{{ $item->component_label }}</td>
                        <td>{{ $item->units_available }}</td>
                        <td>{{ $item->expiration_date?->toDateString() }}</td>
                        <td>{{ $item->donationRecord ? 'Donation '.$item->donationRecord->donation_no : 'Manual adjustment' }}</td>
                        @php($displayStatus = $item->status === 'expired' || $item->expiration_date?->lt(today()) ? 'Expired' : ((int) $item->units_available === 0 ? 'Depleted' : ((int) $item->units_available <= 5 ? 'Low stock' : 'In storage')))
                        <td><span class="badge {{ in_array($displayStatus, ['Expired', 'Depleted']) ? 'cbis-status-expired' : ($displayStatus === 'Low stock' ? 'cbis-status-low' : 'cbis-status-active') }}">{{ $displayStatus }}</span></td>
                        <td><a href="{{ route('blood-inventory.show',$item) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="cbis-empty-state">
                                <strong>No matching stock records</strong>
                                <span>Verified donations add stock automatically. Use Reset to clear filters, or record a verified donation to add stock.</span>
                                @if($canManageInventory)
                                    <a href="{{ route('donation-records.create') }}" class="btn btn-sm btn-outline-danger mt-2">Record a donation</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($inventory->hasPages())<div class="card-footer bg-white">{{ $inventory->fragment('stock-batches')->links() }}</div>@endif
</section>

<section id="stock-movements" class="card mb-4">
    <div class="card-header cbis-card-title">
        <span>Recent Stock In / Out</span>
        <small class="text-muted">Latest 20 donations, releases, and recorded manual adjustments</small>
    </div>
    <div class="card-body">
        @if($stockMovements->isNotEmpty())
            <div class="cbis-stock-movement-list">
                @foreach($stockMovements as $movement)
                    <a href="{{ $movement['url'] }}" class="cbis-stock-movement-item">
                        <span class="cbis-stock-movement-badge {{ $movement['type'] === 'in' ? 'is-in' : 'is-out' }}">
                            {{ $movement['type'] === 'in' ? 'IN' : 'OUT' }}
                        </span>
                        <span>
                            <strong>{{ $movement['blood_type'] }} · {{ $movement['component'] }}</strong>
                            <small>{{ $movement['source'] }} · {{ $movement['person'] }}</small>
                        </span>
                        <span class="cbis-stock-movement-units">
                            {{ $movement['type'] === 'in' ? '+' : '-' }}{{ $movement['units'] }} unit{{ (int) $movement['units'] === 1 ? '' : 's' }}
                            <small>{{ $movement['date']?->format('M d, Y') ?? 'No date' }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="cbis-empty-state py-4">
                <strong>No stock movement yet</strong>
                <span>Donation records will appear as stock in. Blood releases appear as stock out. New manual balance changes also appear here.</span>
            </div>
        @endif
    </div>
</section>

@endsection
