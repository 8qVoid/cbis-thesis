@extends('layouts.app')
@section('content')
@php($latest = $reservations->first())
<div class="cbis-reference">
    <div class="cbis-reference-profile"><a href="{{ route('account.details.edit') }}"><x-ui.icon name="users" /> My Profile</a></div>
    <nav class="cbis-segments" aria-label="Profile view">
        @foreach(['both' => 'Patient/Donor', 'donor' => 'Donor only', 'patient' => 'Patient only'] as $view => $label)
            @if(isset($availableViews[$view]))<a href="{{ route('account.dashboard', ['view' => $view]) }}" class="{{ $selectedView === $view ? 'active' : '' }}" @if($selectedView === $view) aria-current="page" @endif>{{ $label }}</a>@endif
        @endforeach
    </nav>
    @if($showDonor)
        <h1>Donation Dashboard</h1>
        <a href="{{ route('public.map') }}" class="btn btn-danger cbis-reference-action" aria-label="Find Events on Map"><x-ui.icon name="calendar" /> Find Donation Event</a>
        <div class="cbis-reference-metrics">
            <section class="card"><span class="cbis-reference-icon"><x-ui.icon name="drop" /></span><div><small>Blood type</small><strong>{{ $donor?->blood_type ?? 'Not recorded' }}</strong></div></section>
            <section class="card"><span class="cbis-reference-icon"><x-ui.icon name="pin" /></span><div><small>Donations</small><strong>{{ $donationHistory->count() }}</strong></div></section>
        </div>
        <section class="card cbis-reference-map"><h2>Approved Donation Events</h2><x-ui.event-map :events="$upcomingEvents" /></section>
        <section class="card cbis-reference-history"><div class="d-flex justify-content-between align-items-center"><h2>Donation History</h2><a href="{{ route('donor.events.index') }}" class="small">My Registrations</a></div>
            @forelse($donationHistory->take(5) as $record)
                <details><summary><x-ui.icon name="calendar" /><span>{{ $record->donated_at?->format('M d, Y') }}</span><span>{{ $record->facility?->name ?? 'Bacolod Main Chapter' }}</span><span aria-hidden="true">›</span></summary><div class="small text-muted p-3">{{ $record->donation_no }} · {{ $record->blood_type }} · {{ str($record->status)->title() }}</div></details>
            @empty<div class="cbis-reference-empty">No donations recorded yet.</div>@endforelse
        </section>
    @else
        <h1>Blood Requests</h1>
        <a href="{{ route('reservations.create') }}" class="btn btn-danger cbis-reference-action"><span aria-hidden="true">⊕</span> Request Blood</a>
        <section class="card cbis-reference-request" aria-label="My Blood Requests">
            <div class="cbis-reference-request-title"><span class="cbis-reference-icon"><x-ui.icon name="report" /></span><div><h2>{{ $latest?->reference ?? 'No requests yet' }}</h2>
                @if($latest)<span class="cbis-inline-status {{ in_array($latest->status, ['rejected','cancelled']) ? 'cbis-tone-danger' : (in_array($latest->status, ['approved','fulfilled']) ? 'cbis-tone-success' : 'cbis-tone-warning') }}">{{ str($latest->status)->replace('_', ' ')->title() }}</span>@endif
                <p>Bacolod Main Chapter</p></div></div>
            <div class="cbis-reference-docs"><strong><x-ui.icon name="report" /> 2 required documents</strong>
                @foreach(['identification' => 'ID', 'blood_request' => "Doctor’s blood request"] as $type => $label)
                    @php($uploaded = $latest?->documents->contains('type', $type) ?? false)
                    <p><span class="{{ $uploaded ? 'text-success' : 'text-muted' }}" aria-hidden="true">{{ $uploaded ? '✓' : '○' }}</span> {{ $label }} {{ $uploaded ? 'uploaded' : 'not uploaded' }}</p>
                @endforeach
            </div>
            @if($latest)<a class="small" href="{{ route('reservations.show', $latest) }}">View request details</a>@endif
        </section>
        <section class="card cbis-reference-timeline"><h2>Request Status</h2>
            @php($decided = $latest && in_array($latest->status, ['approved', 'rejected', 'fulfilled', 'cancelled']))
            <ol>
                <li class="{{ $latest ? 'complete' : '' }}"><span class="cbis-reference-icon"><x-ui.icon name="report" /></span><strong>Submitted</strong><small>{{ $latest?->created_at?->format('M d, Y') ?? 'Not submitted' }}</small></li>
                <li class="{{ $latest?->status === 'under_review' ? 'current' : ($decided ? 'complete' : '') }}"><span class="cbis-reference-icon"><x-ui.icon name="search" /></span><strong>{{ $latest?->status === 'cancelled' ? 'Review' : 'Under review' }}</strong><small>{{ $latest?->status === 'under_review' ? 'In progress' : ($latest?->status === 'cancelled' ? 'Not applicable' : ($decided ? 'Completed' : 'Pending')) }}</small></li>
                <li class="{{ $decided ? 'current' : '' }}"><span class="cbis-reference-icon"><x-ui.icon name="decision" /></span><strong>Decision</strong><small>{{ $decided ? str($latest->status)->title() : 'Pending' }}</small>@if($decided && $latest->reviewed_at)<small>{{ $latest->reviewed_at->format('M d, Y') }}</small>@endif</li>
            </ol>
        </section>
        <a href="{{ route('reservations.index') }}" class="small d-block text-end mt-3">View all blood requests</a>
    @endif
    @if(!$user->hasDonorAccess())<a href="{{ route('account.profile.edit', ['service' => 'donor']) }}" class="small d-block mt-3">Enable Donor Services</a>@endif
    @if(!$user->hasPatientAccess())<a href="{{ route('account.profile.edit', ['service' => 'patient']) }}" class="small d-block mt-3">Enable Patient Services</a>@endif
</div>
@endsection
