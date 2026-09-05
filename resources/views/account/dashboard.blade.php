@extends('layouts.app')
@section('content')
@php
$firstName = $user->first_name ?: str($user->name)->before(' ');
$activeRegistrations = $eventRegistrations->where('status', 'registered')->count();
$latestReservation = $reservations->first();
@endphp
<div class="cbis-dashboard-heading cbis-member-heading">
    <div><div class="cbis-eyebrow">Welcome, {{ $firstName }}</div><h1 class="cbis-page-title">{{ $selectedView === 'both' ? 'Your Overview' : ($selectedView === 'donor' ? 'Donation Dashboard' : 'Blood Requests') }}</h1><p class="cbis-page-subtitle">{{ $selectedView === 'both' ? 'A quick look at your Patient/Donor services.' : ($selectedView === 'donor' ? 'Find events and follow your donation history.' : 'Submit and track your blood requests.') }}</p></div>
    <div class="cbis-heading-actions"><a href="{{ route('account.details.edit') }}" class="btn btn-outline-secondary">My Profile</a><a href="{{ $user->hasDonorAccess() ? route('public.map') : route('account.profile.edit', ['service' => 'donor']) }}" class="btn btn-outline-danger">Donate Blood</a><a href="{{ $user->hasPatientAccess() ? route('reservations.create') : route('account.profile.edit', ['service' => 'patient']) }}" class="btn btn-danger">Request Blood</a></div>
</div>

<nav class="cbis-view-switch mb-4" aria-label="Profile view">
    <span class="small text-muted me-2">View as</span>
    @foreach(['both' => 'Patient/Donor', 'donor' => 'Donor only', 'patient' => 'Patient only'] as $view => $label)
        @if(isset($availableViews[$view]))<a class="btn {{ $selectedView === $view ? 'btn-danger' : 'btn-outline-secondary' }}" href="{{ route('account.dashboard', ['view' => $view]) }}" @if($selectedView === $view) aria-current="page" @endif>{{ $label }}</a>@endif
    @endforeach
    @if(!$user->hasDonorAccess())<a href="{{ route('account.profile.edit', ['service' => 'donor']) }}" class="btn btn-link">Enable Donor Services</a>@endif
    @if(!$user->hasPatientAccess())<a href="{{ route('account.profile.edit', ['service' => 'patient']) }}" class="btn btn-link">Enable Patient Services</a>@endif
</nav>
@if($selectedView === 'both')
<div class="row g-4 mb-4">
    <div class="col-md-6"><section class="card h-100"><div class="card-body d-flex flex-column"><span class="cbis-action-icon mb-3"><x-ui.icon name="drop" /></span><h2 class="h5">Donation Services</h2><dl class="cbis-overview-facts"><div><dt>Last donation</dt><dd>{{ $donationHistory->first()?->donated_at?->format('M d, Y') ?? 'No donations recorded' }}</dd></div><div><dt>Next approved event</dt><dd>{{ $upcomingEvents->first()?->event_date?->format('M d, Y') ?? 'No upcoming events' }}</dd></div></dl><a class="btn btn-outline-danger mt-auto" href="{{ route('account.dashboard', ['view' => 'donor']) }}">Open Donor View</a></div></section></div>
    <div class="col-md-6"><section class="card h-100"><div class="card-body d-flex flex-column"><span class="cbis-action-icon mb-3"><x-ui.icon name="report" /></span><h2 class="h5">My Blood Requests</h2><dl class="cbis-overview-facts"><div><dt>Latest request</dt><dd>{{ $latestReservation?->reference ?? 'No requests yet' }}</dd></div><div><dt>Status</dt><dd>{{ $latestReservation ? str($latestReservation->status)->replace('_', ' ')->title() : 'No request submitted' }}</dd></div><div><dt>Processing location</dt><dd>Bacolod Main Chapter</dd></div></dl><a class="btn btn-outline-danger mt-auto" href="{{ route('account.dashboard', ['view' => 'patient']) }}">Open Patient View</a></div></section></div>
</div>
<section class="card"><div class="card-body d-flex flex-wrap align-items-center gap-3"><span class="cbis-action-icon"><x-ui.icon name="users" /></span><div><strong>{{ $user->name }}</strong><p class="small text-muted mb-0">One shared profile. Donation and reservation histories stay separate.</p></div><a class="btn btn-outline-secondary ms-auto" href="{{ route('account.details.edit') }}">My Profile</a></div></section>
@else
@if($showDonor)
<div class="row g-4 mb-4"><div class="col-xl-7"><div class="row g-3">
    <div class="col-12"><section class="card h-100 cbis-primary-action-card"><div class="card-body"><span class="cbis-action-icon"><x-ui.icon name="pin" /></span><h2>Find a Donation Event</h2><p>Explore QAO-approved activities around Negros Occidental and register online.</p><a href="{{ route('public.map') }}" class="btn btn-danger">Find Events on Map</a></div></section></div>
    <div class="col-md-6"><section class="card h-100"><div class="card-header cbis-card-title"><span>My Donation Status</span></div><div class="card-body"><div class="cbis-donor-status"><span class="cbis-blood-type">{{ $donor?->blood_type ?? '?' }}</span><div><small>Eligibility</small><strong>{{ $donor?->is_eligible ? 'Eligible to donate' : 'Pending staff verification' }}</strong></div></div><p class="text-muted mb-0">{{ $donor?->is_eligible ? 'You can register for an open activity. Final screening still happens at the venue.' : 'Blood Bank Staff will update your eligibility after reviewing your donor record.' }}</p></div></section></div>
    <div class="col-md-6"><section class="card h-100"><div class="card-header cbis-card-title"><span>Donation History</span></div><div class="card-body cbis-number-card"><strong>{{ $donationHistory->count() }}</strong><span>donation record(s)</span><small>{{ $activeRegistrations }} active event registration(s)</small><a href="{{ route('donor.events.index') }}" class="btn btn-outline-danger">My Registrations</a></div></section></div>
</div>
</div><div class="col-xl-5"><section class="card h-100 cbis-member-map"><div class="card-header cbis-card-title"><span>Explore Events Near You</span><a href="{{ route('public.map') }}">Open full map</a></div><x-ui.event-map :events="$upcomingEvents" /></section></div></div>
<section class="card mb-4"><div class="card-header cbis-card-title"><span>Upcoming Donation Events</span><a href="{{ route('public.map') }}">View all on map</a></div><div class="card-body"><div class="cbis-event-grid">
@forelse($upcomingEvents as $event)<article class="cbis-event-card"><div class="cbis-event-date"><strong>{{ $event->event_date?->format('d') }}</strong><span>{{ $event->event_date?->format('M') }}</span></div><div><h3>{{ $event->title }}</h3><p>{{ $event->facility?->name }}</p><small>⌖ {{ $event->venue }} · {{ $event->time_range_label }}</small></div><a href="{{ route('donor.events.join', $event) }}" class="btn btn-sm btn-outline-danger">Register</a></article>@empty<div class="cbis-empty-state cbis-grid-empty"><strong>No upcoming approved events</strong><span>Check again after QAO publishes an activity.</span><a href="{{ route('public.map') }}" class="btn btn-outline-danger mt-2">Open Map</a></div>@endforelse
</div></div></section>
@endif

@if($showPatient)
<section class="card mb-4"><div class="card-header cbis-card-title"><span>My Blood Requests</span><div><strong>{{ $reservations->count() }}</strong> total</div></div><div class="card-body">
@if($latestReservation)<div class="cbis-request-summary"><div><small>Latest request</small><h3>{{ $latestReservation->reference }}</h3><p>{{ $latestReservation->blood_type }} · {{ \App\Models\BloodInventory::COMPONENTS[$latestReservation->component] ?? $latestReservation->component }} · {{ $latestReservation->units_requested }} unit(s)</p></div><span class="cbis-inline-status {{ in_array($latestReservation->status, ['approved','fulfilled']) ? 'cbis-tone-success' : ($latestReservation->status === 'rejected' ? 'cbis-tone-danger' : 'cbis-tone-warning') }}">{{ str($latestReservation->status)->replace('_', ' ')->title() }}</span><a href="{{ route('reservations.show', $latestReservation) }}" class="btn btn-outline-danger">View Request</a></div>
@else<div class="cbis-empty-state"><strong>No blood requests</strong><span>If you need blood, submit your ID and doctor's blood request together.</span><a href="{{ route('reservations.create') }}" class="btn btn-danger mt-2">Request Blood</a></div>@endif
</div></section>
@endif

@if($showPatient && $latestReservation)
<div class="row g-4 mb-4"><div class="col-md-6"><section class="card h-100"><div class="card-header cbis-card-title"><span>2 Required Documents</span></div><div class="card-body"><p class="small text-muted">For {{ $latestReservation->reference }} · Bacolod Main Chapter</p>
@foreach(['identification' => 'ID', 'blood_request' => "Doctor’s blood request"] as $type => $label)<div class="d-flex justify-content-between gap-3 py-2 border-bottom"><span>{{ $label }}</span><strong class="small {{ $latestReservation->documents->contains('type', $type) ? 'text-success' : 'text-warning' }}">{{ $latestReservation->documents->contains('type', $type) ? 'Uploaded' : 'Not uploaded' }}</strong></div>@endforeach
</div></section></div><div class="col-md-6"><section class="card h-100"><div class="card-header cbis-card-title"><span>Request Status</span></div><div class="card-body"><ol class="cbis-request-steps"><li><strong>Submitted</strong><small>{{ $latestReservation->created_at?->format('M d, Y') }}</small></li><li><strong>{{ str($latestReservation->status)->replace('_', ' ')->title() }}</strong><small>{{ $latestReservation->reviewed_at ? $latestReservation->reviewed_at->format('M d, Y') : 'Awaiting a staff update' }}</small></li></ol><a href="{{ route('reservations.show', $latestReservation) }}" class="btn btn-outline-danger mt-3">View Request Details</a></div></section></div></div>
@endif
<div class="row g-4">
    @if($showDonor)<div class="col-lg-7"><section class="card h-100"><div class="card-header cbis-card-title"><span>Recent Donation History</span></div><div class="card-body p-0"><div class="cbis-list-stack cbis-list-flush">@forelse($donationHistory->take(5) as $record)<div class="cbis-list-row"><span class="cbis-list-icon"><x-ui.icon name="drop" /></span><span><strong>{{ $record->donation_no }}</strong><small>{{ $record->donated_at?->format('M d, Y') }} · {{ $record->blood_type }}</small></span><span class="cbis-inline-status cbis-tone-success">{{ str($record->status)->title() }}</span></div>@empty<div class="cbis-empty-state"><strong>No completed donations yet</strong><span>Donations recorded by Blood Bank Staff will appear here.</span></div>@endforelse</div></div></section></div>@endif
    <div class="{{ $showDonor ? 'col-lg-5' : 'col-12' }}"><section class="card h-100"><div class="card-header cbis-card-title"><span>Latest Updates</span></div><div class="card-body p-0"><div class="cbis-list-stack cbis-list-flush">@forelse($user->notifications()->latest()->limit(5)->get() as $notification)<div class="cbis-list-row"><span class="cbis-list-icon"><x-ui.icon name="bell" /></span><span><strong>{{ $notification->data['title'] ?? 'Account update' }}</strong><small>{{ $notification->data['message'] ?? $notification->created_at?->diffForHumans() }}</small></span></div>@empty<div class="cbis-empty-state"><strong>No updates yet</strong><span>Registration and reservation updates will appear here.</span></div>@endforelse</div></div></section></div>
</div>
@endif
@endsection
