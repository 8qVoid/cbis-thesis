@extends('layouts.app')
@section('content')
@php
    $isPatient = auth()->user()->hasRole('Patient');
@endphp
@if(request('status') === 'pending')
    <p class="small text-muted">Showing submitted and under-review requests. <a href="{{ route('reservations.index') }}">View all requests</a></p>
@endif
<div class="d-flex justify-content-between align-items-end mb-3 gap-3 flex-wrap">
    <div>
        <h1 class="cbis-page-title mb-0">{{ $isPatient ? 'My Blood Requests' : 'Blood Reservations' }}</h1>
        <p class="cbis-page-subtitle">{{ $isPatient ? 'Track your submitted requests and staff decisions.' : 'Review patient requests, documents, stock readiness, and release progress.' }}</p>
    </div>
    @if($isPatient)
        <a href="{{ route('reservations.create') }}" class="btn btn-danger">New Request</a>
    @endif
</div>

<div class="cbis-reservation-list">
    @forelse($reservations as $reservation)
        <article class="card cbis-reservation-card">
            <div class="cbis-reservation-main">
                <div>
                    <small>Reference</small>
                    <strong>{{ $reservation->reference }}</strong>
                    @unless($isPatient)
                        <span>{{ $reservation->patient?->name ?? 'No patient name' }}</span>
                    @endunless
                </div>
                <div>
                    <small>Request</small>
                    <strong>{{ $reservation->blood_type }} · {{ \App\Models\BloodInventory::COMPONENTS[$reservation->component] ?? $reservation->component }}</strong>
                    <span>{{ $reservation->units_requested }} unit{{ $reservation->units_requested == 1 ? '' : 's' }} needed {{ $reservation->needed_on?->format('M d, Y') }}</span>
                </div>
                <div>
                    <small>Branch</small>
                    <strong>{{ $reservation->facility?->name ?? 'No branch' }}</strong>
                    <span>{{ str($reservation->status)->replace('_',' ')->title() }}</span>
                </div>
                <div class="cbis-reservation-status">
                    <x-ui.request-status :status="$reservation->status" />
                </div>
            </div>
            <div class="cbis-reservation-footer">
                <span>
                    @if($reservation->review_notes)
                        Staff note: {{ $reservation->review_notes }}
                    @else
                        {{ $isPatient ? 'Open to view documents, status, and next steps.' : 'Open to review documents, approve or reject, and record releases.' }}
                    @endif
                </span>
                <a href="{{ route('reservations.show',$reservation) }}" class="btn btn-sm btn-outline-danger">View request</a>
            </div>
        </article>
    @empty
        <div class="card">
            <div class="cbis-empty-state">
                <strong>No blood requests found</strong>
                <span>{{ $isPatient ? 'Use New Request to submit your ID and doctor’s blood request for staff review.' : 'New patient submissions will appear here for review.' }}</span>
                @if($isPatient)
                    <a href="{{ route('reservations.create') }}" class="btn btn-danger mt-2">Create a blood request</a>
                @elseif(auth()->user()->isBloodBankStaff())
                    <a href="{{ route('blood-inventory.index') }}" class="btn btn-outline-secondary mt-2">Check inventory</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mt-2">Back to dashboard</a>
                @endif
            </div>
        </div>
    @endforelse
</div>

<div class="mt-3">{{ $reservations->links() }}</div>
@endsection
