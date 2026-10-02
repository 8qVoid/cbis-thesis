@extends('layouts.app')
@section('content')
@php
$user = auth()->user();
$isQao = $user->isQao();
$components = \App\Models\BloodInventory::COMPONENTS;
$statusClass = fn (int $units) => $units <= 5 ? 'cbis-tone-warning' : 'cbis-tone-success';
@endphp
<div data-live-region="staff-dashboard">
<div class="cbis-dashboard-heading">
    <div><div class="cbis-eyebrow">{{ $isQao ? 'Central oversight' : 'Today’s operations' }}</div><h1 class="cbis-page-title">{{ $isQao ? 'QAO Overview' : "Today's Work Queue" }}</h1><p class="cbis-page-subtitle">Bacolod Main Chapter · {{ now()->format('F d, Y') }}</p></div>
    <div class="cbis-heading-actions">
        @if($isQao)
            <a href="{{ route('donation-schedules.create') }}" class="btn btn-sm btn-danger" title="Published automatically with a location">Create Activity</a>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary" title="Choose records, details, and file type">Export Reports</a>
            <a href="{{ route('staff-users.index') }}" class="btn btn-sm btn-outline-secondary" title="Manage staff and public accounts">User Management</a>
        @else
            <a href="{{ route('donation-records.create') }}" class="btn btn-sm btn-danger" title="Add a completed collection">Record Donation</a>
            <a href="{{ route('blood-inventory.create') }}" class="btn btn-sm btn-outline-secondary" title="Record blood stock by component">Add Inventory</a>
            <a href="{{ route('blood-releases.create') }}" class="btn btn-sm btn-outline-secondary" title="Fulfill an approved request">Release Blood</a>
        @endif
    </div>
</div>

@unless($isQao)
    <details class="cbis-help mb-2">
        <summary>How the blood request workflow works</summary>
        <p class="mt-2 mb-0">Screen donors, record collections, and review patient requests. Approval reserves matching stock; recording a release completes the request and deducts inventory.</p>
    </details>
@endunless

<div class="cbis-metric-grid mb-3">
    @if($isQao)
        <x-ui.kpi-card label="Activity Approvals" :value="$pendingActivityCount" suffix="Waiting for QAO review" :href="route('donation-schedules.index', ['approval_status' => 'pending'])" />
        <x-ui.kpi-card label="Active Facilities" :value="$activeFacilityCount" suffix="Enabled facilities" :href="route('facilities.index')" />
        <x-ui.kpi-card label="Public Users" :value="$publicUserCount" suffix="Donor and patient accounts, counted once" :href="route('staff-users.index', ['category' => 'public'])" />
        <x-ui.kpi-card label="Total Blood Units" :value="$totalUnits" suffix="Recorded across all components" :href="route('blood-inventory.index')" />
    @else
        <x-ui.kpi-card label="Pending Requests" :value="$pendingRequestCount" suffix="Submitted or under review" :href="route('reservations.index', ['status' => 'pending'])" />
        <x-ui.kpi-card label="Low-stock Items" :value="$lowStockCount" suffix="Requires attention" :href="route('blood-inventory.index', ['status' => 'low_stock'])" />
        <x-ui.kpi-card label="Expiring Stock Items" :value="$expiringStockCount" suffix="With stock, within 14 days" :href="route('blood-inventory.index', ['expiring' => 'soon'])" />
        <x-ui.kpi-card label="Awaiting Screening" :value="$awaitingScreeningCount" suffix="Donors without a screening decision" :href="route('donors.index', ['eligibility' => 'awaiting'])" />
    @endif
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <section class="card h-100">
            <div class="card-header cbis-card-title"><span>{{ $isQao ? 'Inventory by Component' : "Today's Reservation Queue" }}</span><a href="{{ $isQao ? route('blood-inventory.index') : route('reservations.index') }}">View all</a></div>
            @if($isQao)
                <div class="card-body"><div class="cbis-component-grid">
                @foreach($components as $key => $label)
                    @php($componentRow = $inventoryByComponent->get($key))
                    @php($units = (int) ($componentRow?->units ?? 0))
                    <a href="{{ route('blood-inventory.index', ['component' => $key]) }}" class="cbis-component-card">
                        <span class="cbis-blood-symbol"><x-ui.icon name="drop" /></span><span><small>{{ $label }}</small><strong>{{ $units }} <em>units</em></strong><span class="cbis-inline-status {{ $componentRow ? $statusClass($units) : 'cbis-tone-info' }}">{{ ! $componentRow ? 'No stock recorded' : ($units <= 5 ? 'Low stock' : 'Adequate') }}</span></span>
                    </a>
                @endforeach
                </div>
                <div class="mt-3 pt-2 border-top"><h2 class="h6 mb-2">Recorded Units by Blood Type</h2>
                    @php($maximumUnits = max(1, (int) $inventoryByType->max('units')))
                    @foreach(\App\Models\BloodInventory::BLOOD_TYPES as $type)
                        @php($typeUnits = (int) ($inventoryByType->firstWhere('blood_type', $type)?->units ?? 0))
                        <div class="cbis-bar-row"><strong>{{ $type }}</strong><div class="cbis-bar-track" aria-hidden="true"><span style="width:{{ round($typeUnits / $maximumUnits * 100) }}%"></span></div><span>{{ $typeUnits }} units</span></div>
                    @endforeach
                    <p class="small text-muted mb-0 mt-2">Recorded stock totals. Check individual units and expiry dates before release.</p>
                </div></div>
            @else
                <div class="table-responsive"><table class="table cbis-table-clean"><thead><tr><th>Reference</th><th>Blood</th><th>Needed</th><th>Requirements</th><th></th></tr></thead><tbody>
                @forelse($reservationQueue as $reservation)<tr><td><strong>{{ $reservation->reference }}</strong><div class="small text-muted">{{ $reservation->patient?->name }}</div></td><td>{{ $reservation->blood_type }} · {{ $components[$reservation->component] ?? $reservation->component }}<div class="small text-muted">{{ $reservation->units_requested }} unit(s)</div></td><td>{{ $reservation->needed_on?->format('M d, Y') }}</td><td><span class="cbis-inline-status {{ $reservation->documents->count() >= 2 ? 'cbis-tone-success' : 'cbis-tone-warning' }}">{{ $reservation->documents->count() >= 2 ? 'Complete' : 'Needs files' }}</span></td><td><a href="{{ route('reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary">Review</a></td></tr>@empty<tr><td colspan="5"><div class="cbis-empty-state"><strong>Work queue is clear</strong><span>New patient requests will appear here.</span></div></td></tr>@endforelse
                </tbody></table></div>
            @endif
        </section>
    </div>
    <div class="col-xl-5 d-flex flex-column gap-3">
        <section class="card">
            <div class="card-header cbis-card-title"><span>{{ $isQao ? 'Reservation Notices' : 'Inventory by Component' }}</span><a href="{{ $isQao ? route('reservations.index') : route('blood-inventory.index') }}">View all</a></div>
            <div class="card-body">
            @if($isQao)
                <div class="cbis-list-stack">@forelse($reservationNotices as $reservation)<a class="cbis-list-row" href="{{ route('reservations.show', $reservation) }}"><span class="cbis-list-icon"><x-ui.icon name="report" /></span><span><strong>{{ $reservation->reference }}</strong><small>{{ $reservation->blood_type }} · {{ $components[$reservation->component] ?? $reservation->component }} · {{ $reservation->units_requested }} unit(s)</small></span><span class="cbis-view-only">View only</span></a>@empty<div class="cbis-empty-state"><strong>No reservation notices</strong><span>New requests will appear here for monitoring.</span></div>@endforelse</div>
            @else
                <div class="cbis-component-grid cbis-component-grid-compact">@foreach($components as $key => $label)@php($componentRow = $inventoryByComponent->get($key))@php($units = (int) ($componentRow?->units ?? 0))<a href="{{ route('blood-inventory.index', ['component' => $key]) }}" class="cbis-component-card"><span class="cbis-blood-symbol"><x-ui.icon name="drop" /></span><span><small>{{ $label }}</small><strong>{{ $units }} <em>units</em></strong><span class="cbis-inline-status {{ $componentRow ? $statusClass($units) : 'cbis-tone-info' }}">{{ ! $componentRow ? 'No stock recorded' : ($units <= 5 ? 'Low stock' : 'Adequate') }}</span></span></a>@endforeach</div>
            @endif
            </div>
        </section>
        <section class="card flex-grow-1"><div class="card-header cbis-card-title"><span>{{ $isQao ? 'Activity Approvals' : 'Expiring Soon' }}</span><a href="{{ $isQao ? route('donation-schedules.index') : route('blood-inventory.index') }}">View all</a></div>
        <div class="card-body p-0"><div class="cbis-list-stack cbis-list-flush">
        @if($isQao) @forelse($pendingActivities as $event)<a class="cbis-list-row" href="{{ route('donation-schedules.show', $event) }}"><span class="cbis-list-icon"><x-ui.icon name="calendar" /></span><span><strong>{{ $event->title }}</strong><small>{{ $event->facility?->name }} · {{ $event->event_date?->format('M d, Y') }}</small></span><span class="btn btn-sm btn-outline-secondary">Review</span></a>@empty<div class="cbis-empty-state"><strong>No activities waiting</strong><span>Facilitator submissions will appear here.</span></div>@endforelse
        @else @forelse($expiringInventory as $item)<a class="cbis-list-row" href="{{ route('blood-inventory.show', $item) }}"><span class="cbis-list-icon"><x-ui.icon name="drop" /></span><span><strong>{{ $item->blood_type }} · {{ $item->component_label }}</strong><small>{{ $item->units_available }} unit(s) · expires {{ $item->expiration_date?->format('M d, Y') }}</small></span><span class="cbis-inline-status cbis-tone-warning">{{ today()->diffInDays($item->expiration_date) }} days</span></a>@empty<div class="cbis-empty-state"><strong>No near-expiry stock</strong><span>Items expiring within 14 days will appear here.</span></div>@endforelse @endif
        </div></div></section>
    </div>
</div>
</div>
@endsection
