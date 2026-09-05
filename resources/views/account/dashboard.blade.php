@extends('layouts.app')
@section('content')
@php($latestReservation = $reservations->first())
<div class="cbis-reference cbis-combined">
    <div class="cbis-reference-profile"><a href="{{ route('account.details.edit') }}"><x-ui.icon name="users" /> My Profile</a></div>
    <nav class="cbis-segments" aria-label="Profile view">
        @foreach(['both' => 'Patient/Donor', 'donor' => 'Donor only', 'patient' => 'Patient only'] as $view => $label)
            @if(isset($availableViews[$view]))<a href="{{ route('account.dashboard', ['view' => $view]) }}" class="{{ $selectedView === $view ? 'active' : '' }}" @if($selectedView === $view) aria-current="page" @endif>{{ $label }}</a>@endif
        @endforeach
    </nav>
    <h1>Patient/Donor Dashboard</h1>
    <p class="small text-muted">Welcome, {{ $user->first_name ?: str($user->name)->before(' ') }}. Your donation and blood request services, together.</p>
    <div class="cbis-combined-grid">
        <section class="card cbis-combined-card">
            <div class="cbis-reference-request-title"><span class="cbis-reference-icon"><x-ui.icon name="drop" /></span><h2>Donation Services</h2></div>
            <dl class="cbis-combined-facts">
                <div><dt>Blood type</dt><dd>{{ $donor?->blood_type ?? 'Not recorded' }}</dd></div>
                <div><dt>Donations</dt><dd>{{ $donationHistory->count() }}</dd></div>
                <div><dt>Last donation</dt><dd>{{ $donationHistory->first()?->donated_at?->format('M d, Y') ?? 'No donations recorded' }}</dd></div>
                <div><dt>Next approved event</dt><dd>{{ $upcomingEvents->first()?->event_date?->format('M d, Y') ?? 'No upcoming events' }}</dd></div>
            </dl>
            <a href="{{ route('public.map') }}" class="btn btn-danger cbis-reference-action"><x-ui.icon name="calendar" /> Find Donation Event</a>
            <a href="{{ route('account.dashboard', ['view' => 'donor']) }}" class="cbis-combined-detail">Open Donor View <span aria-hidden="true">›</span></a>
        </section>
        <section class="card cbis-combined-card">
            <div class="cbis-reference-request-title"><span class="cbis-reference-icon"><x-ui.icon name="report" /></span><h2>My Blood Requests</h2></div>
            <dl class="cbis-combined-facts">
                <div><dt>Latest request</dt><dd>{{ $latestReservation?->reference ?? 'No requests yet' }}</dd></div>
                <div><dt>Status</dt><dd>{{ $latestReservation ? str($latestReservation->status)->replace('_', ' ')->title() : 'No request submitted' }}</dd></div>
                <div><dt>Required documents</dt><dd>ID + doctor's blood request</dd></div>
                <div><dt>Processing location</dt><dd>Bacolod Main Chapter</dd></div>
            </dl>
            <a href="{{ route('reservations.create') }}" class="btn btn-danger cbis-reference-action"><span aria-hidden="true">⊕</span> Request Blood</a>
            <a href="{{ route('account.dashboard', ['view' => 'patient']) }}" class="cbis-combined-detail">Open Patient View <span aria-hidden="true">›</span></a>
        </section>
    </div>
    <section class="card cbis-combined-profile"><span class="cbis-reference-icon"><x-ui.icon name="users" /></span><div><h2>{{ $user->name }}</h2><p>One shared profile. Donation and reservation histories stay separate.</p></div><a href="{{ route('account.details.edit') }}" class="small">My Profile</a></section>
</div>
@endsection
