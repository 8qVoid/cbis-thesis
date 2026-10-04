@extends('layouts.app')
@section('content')
@php($latestReservation = $reservations->first())
<div class="cbis-reference cbis-combined">
    <div class="cbis-page-heading">
        <div><h1 class="cbis-page-title">Your dashboard</h1><p class="cbis-page-subtitle">Welcome, {{ $user->first_name ?: str($user->name)->before(' ') }}. Manage your donations and blood requests.</p></div>
        @include('account.view-switch')
    </div>
    @include('account.screening-status')
    <div class="cbis-combined-grid" data-live-region="account-combined-cards">
        <section class="card cbis-combined-card">
            <div class="cbis-reference-request-title"><span class="cbis-reference-icon"><x-ui.icon name="drop" /></span><h2>Donation Services</h2></div>
            <div class="cbis-service-card-content">
            @if($donationHistory->isEmpty())
                <div class="cbis-empty-message"><strong>No donations recorded yet</strong><p>Join an approved event. Staff record your donation after collection.</p></div>
            @endif
            <dl class="cbis-combined-facts cbis-combined-stats">
                <div><dt>Blood type</dt><dd>{{ $donor?->blood_type ?? 'Not recorded' }}</dd></div>
                @if($donationHistory->isNotEmpty())
                <div><dt>Donations</dt><dd>{{ $donationHistory->count() }}</dd></div>
                <div><dt>Last donation</dt><dd>{{ $donationHistory->first()?->donated_at?->format('M d, Y') ?? 'No donations recorded' }}</dd></div>
                @endif
                @if($upcomingEvents->isNotEmpty())
                <div><dt>Next approved event</dt><dd>{{ $upcomingEvents->first()?->event_date?->format('M d, Y') ?? 'No upcoming events' }}</dd></div>
                @endif
            </dl>
            </div>
            <div class="cbis-service-card-actions">
            <a href="{{ route('public.map') }}" class="btn btn-danger cbis-reference-action"><x-ui.icon name="calendar" /> Find Donation Event</a>
            <a href="{{ route('account.dashboard', ['view' => 'donor']) }}" class="cbis-combined-detail">Open Donor View <span aria-hidden="true">›</span></a>
            </div>
        </section>
        <section class="card cbis-combined-card">
            <div class="cbis-reference-request-title"><span class="cbis-reference-icon"><x-ui.icon name="report" /></span><h2>My Blood Requests</h2></div>
            <div class="cbis-service-card-content">
            @if($latestReservation)
            <dl class="cbis-combined-facts">
                <div><dt>Latest request</dt><dd>{{ $latestReservation->reference }}</dd></div>
                <div><dt>Status</dt><dd><span class="cbis-inline-status {{ in_array($latestReservation->status, ['rejected', 'cancelled']) ? 'cbis-tone-danger' : (in_array($latestReservation->status, ['approved', 'fulfilled']) ? 'cbis-tone-success' : 'cbis-tone-warning') }}">{{ str($latestReservation->status)->replace('_', ' ')->title() }}</span></dd></div>
                <div><dt>Required documents</dt><dd>ID + doctor's blood request</dd></div>
                <div><dt>Processing location</dt><dd>Bacolod Main Chapter</dd></div>
            </dl>
            @else
                <div class="cbis-empty-message"><strong>No requests yet</strong><p>Prepare your ID and doctor's blood request to get started.</p></div>
                <dl class="cbis-combined-facts"><div><dt>Processing location</dt><dd>Bacolod Main Chapter</dd></div></dl>
            @endif
            </div>
            <div class="cbis-service-card-actions">
            <a href="{{ route('reservations.create') }}" class="btn btn-danger cbis-reference-action"><x-ui.icon name="report" /> Request Blood</a>
            <a href="{{ route('account.dashboard', ['view' => 'patient']) }}" class="cbis-combined-detail">Open Patient View <span aria-hidden="true">›</span></a>
            </div>
        </section>
    </div>
    <details class="cbis-page-help mt-3"><summary>About your services</summary><p>Use Donor services to find events and view screening results. Use Patient services to submit and track blood requests. Blood Bank Staff handle screening and request decisions.</p></details>
</div>
@endsection
