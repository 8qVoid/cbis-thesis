@extends('layouts.app')
@section('content')
@php
$user = auth()->user();
$isQao = $user->isQao();
$components = \App\Models\BloodInventory::COMPONENTS;
$statusClass = fn (int $units) => $units <= 5 ? 'cbis-tone-warning' : 'cbis-tone-success';
@endphp
<div class="cbis-dashboard-heading">
    <div><div class="cbis-eyebrow">{{ $isQao ? 'Central oversight' : 'Today’s operations' }}</div><h1 class="cbis-page-title">{{ $isQao ? 'Inventory Overview' : "Today's Work Queue" }}</h1><p class="cbis-page-subtitle">Bacolod Main Chapter · {{ now()->format('F d, Y') }}</p></div>
    <div class="cbis-heading-actions">
        @if($isQao)<a href="{{ route('donation-schedules.create') }}" class="btn btn-danger">Create Activity</a><a href="{{ route('reports.index') }}" class="btn btn-outline-danger">Export Reports</a>
        @else<a href="{{ route('donation-records.create') }}" class="btn btn-danger">Record Donation</a><a href="{{ route('blood-releases.create') }}" class="btn btn-outline-danger">Release Blood</a>@endif
    </div>
</div>

<div class="cbis-metric-grid mb-4">
    <x-ui.kpi-card label="{{ $isQao ? 'Total Blood Units' : 'Pending Requests' }}" :value="$isQao ? $totalUnits : $reservationQueue->count()" suffix="{{ $isQao ? 'Across four components' : 'Submitted or under review' }}" />
    <x-ui.kpi-card label="Low-stock Items" :value="$lowStockCount" statusClass="{{ $lowStockCount ? 'text-warning' : 'text-success' }}" suffix="Requires attention" />
    @if($isQao)
        <x-ui.kpi-card label="Activity Approvals" :value="$pendingActivityCount" suffix="Waiting for QAO review" />
        <x-ui.kpi-card label="Reservation Notices" :value="$reservationNotices->where('status', 'submitted')->count()" suffix="Monitoring only" />
    @else
        <x-ui.kpi-card label="Registered Donors" :value="$donors" suffix="Detailed records available" />
        <x-ui.kpi-card label="Blood Releases" :value="$releases" suffix="Recorded transactions" />
    @endif
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <section class="card h-100">
            <div class="card-header cbis-card-title"><span>{{ $isQao ? 'Inventory by Component' : "Today's Reservation Queue" }}</span><a href="{{ $isQao ? route('blood-inventory.index') : route('reservations.index') }}">View all</a></div>
            @if($isQao)
                <div class="card-body"><div class="cbis-component-grid">
                @foreach($components as $key => $label)
                    @php($componentRow = $inventoryByComponent->get($key))
                    @php($units = (int) ($componentRow?->units ?? 0))
                    <a href="{{ route('blood-inventory.index', ['component' => $key]) }}" class="cbis-component-card">
                        <span class="cbis-blood-symbol">●</span><span><small>{{ $label }}</small><strong>{{ $units }} <em>units</em></strong><span class="cbis-inline-status {{ $componentRow ? $statusClass($units) : 'cbis-tone-info' }}">{{ ! $componentRow ? 'No stock recorded' : ($units <= 5 ? 'Low stock' : 'Adequate') }}</span></span>
                    </a>
                @endforeach
                </div></div>
            @else
                <div class="table-responsive"><table class="table cbis-table-clean"><thead><tr><th>Reference</th><th>Blood</th><th>Needed</th><th>Requirements</th><th></th></tr></thead><tbody>
                @forelse($reservationQueue as $reservation)<tr><td><strong>{{ $reservation->reference }}</strong><div class="small text-muted">{{ $reservation->patient?->name }}</div></td><td>{{ $reservation->blood_type }} · {{ $components[$reservation->component] ?? $reservation->component }}<div class="small text-muted">{{ $reservation->units_requested }} unit(s)</div></td><td>{{ $reservation->needed_on?->format('M d, Y') }}</td><td><span class="cbis-inline-status {{ $reservation->documents->count() >= 2 ? 'cbis-tone-success' : 'cbis-tone-warning' }}">{{ $reservation->documents->count() >= 2 ? 'Complete' : 'Needs files' }}</span></td><td><a href="{{ route('reservations.show', $reservation) }}" class="btn btn-sm btn-outline-danger">Review</a></td></tr>@empty<tr><td colspan="5"><div class="cbis-empty-state"><strong>Work queue is clear</strong><span>New patient requests will appear here.</span></div></td></tr>@endforelse
                </tbody></table></div>
            @endif
        </section>
    </div>
    <div class="col-xl-5">
        <section class="card h-100">
            <div class="card-header cbis-card-title"><span>{{ $isQao ? 'Reservation Notices' : 'Inventory by Component' }}</span><a href="{{ $isQao ? route('reservations.index') : route('blood-inventory.index') }}">View all</a></div>
            <div class="card-body">
            @if($isQao)
                <div class="cbis-list-stack">@forelse($reservationNotices as $reservation)<a class="cbis-list-row" href="{{ route('reservations.show', $reservation) }}"><span class="cbis-list-icon">▤</span><span><strong>{{ $reservation->reference }}</strong><small>{{ $reservation->blood_type }} · {{ $components[$reservation->component] ?? $reservation->component }} · {{ $reservation->units_requested }} unit(s)</small></span><span class="cbis-view-only">View only</span></a>@empty<div class="cbis-empty-state"><strong>No reservation notices</strong><span>New requests will appear here for monitoring.</span></div>@endforelse</div>
            @else
                <div class="cbis-component-grid cbis-component-grid-compact">@foreach($components as $key => $label)@php($componentRow = $inventoryByComponent->get($key))@php($units = (int) ($componentRow?->units ?? 0))<a href="{{ route('blood-inventory.index', ['component' => $key]) }}" class="cbis-component-card"><span class="cbis-blood-symbol">●</span><span><small>{{ $label }}</small><strong>{{ $units }} <em>units</em></strong><span class="cbis-inline-status {{ $componentRow ? $statusClass($units) : 'cbis-tone-info' }}">{{ ! $componentRow ? 'No stock recorded' : ($units <= 5 ? 'Low stock' : 'Adequate') }}</span></span></a>@endforeach</div>
            @endif
            </div>
        </section>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <section class="card h-100"><div class="card-header cbis-card-title"><span>{{ $isQao ? 'Activity Approvals' : 'Expiring Soon' }}</span><a href="{{ $isQao ? route('donation-schedules.index') : route('blood-inventory.index') }}">View all</a></div>
        <div class="card-body p-0"><div class="cbis-list-stack cbis-list-flush">
        @if($isQao) @forelse($pendingActivities as $event)<a class="cbis-list-row" href="{{ route('donation-schedules.show', $event) }}"><span class="cbis-list-icon">□</span><span><strong>{{ $event->title }}</strong><small>{{ $event->facility?->name }} · {{ $event->event_date?->format('M d, Y') }}</small></span><span class="btn btn-sm btn-outline-danger">Review</span></a>@empty<div class="cbis-empty-state"><strong>No activities waiting</strong><span>Facilitator submissions will appear here.</span></div>@endforelse
        @else @forelse($expiringInventory as $item)<a class="cbis-list-row" href="{{ route('blood-inventory.show', $item) }}"><span class="cbis-list-icon">●</span><span><strong>{{ $item->blood_type }} · {{ $item->component_label }}</strong><small>{{ $item->units_available }} unit(s) · expires {{ $item->expiration_date?->format('M d, Y') }}</small></span><span class="cbis-inline-status cbis-tone-warning">{{ today()->diffInDays($item->expiration_date) }} days</span></a>@empty<div class="cbis-empty-state"><strong>No near-expiry stock</strong><span>Items expiring within 14 days will appear here.</span></div>@endforelse @endif
        </div></div></section>
    </div>
    <div class="col-xl-5">
        <section class="card h-100"><div class="card-header cbis-card-title"><span>Quick Actions</span></div><div class="card-body cbis-quick-grid">
        @if($isQao)<a href="{{ route('donation-schedules.create') }}" class="cbis-quick-action"><span>＋</span><strong>Create Activity</strong><small>Published automatically with a location</small></a><a href="{{ route('reports.index') }}" class="cbis-quick-action"><span>▥</span><strong>Export Reports</strong><small>Choose records, details, and file type</small></a><a href="{{ route('staff-users.index') }}" class="cbis-quick-action"><span>♧</span><strong>Staff Management</strong><small>Manage QAO, BBS, and Facilitators</small></a>
        @else<a href="{{ route('donation-records.create') }}" class="cbis-quick-action"><span>✚</span><strong>Record Donation</strong><small>Add a completed collection</small></a><a href="{{ route('blood-inventory.create') }}" class="cbis-quick-action"><span>▣</span><strong>Add Inventory</strong><small>Record blood stock by component</small></a><a href="{{ route('blood-releases.create') }}" class="cbis-quick-action"><span>◇</span><strong>Release Blood</strong><small>Fulfill an approved request</small></a>@endif
        </div></section>
    </div>
</div>
@endsection
