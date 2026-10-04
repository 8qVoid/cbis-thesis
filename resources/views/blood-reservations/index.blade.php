@extends('layouts.app')
@section('content')
@php
    $isPatient = auth()->user()->hasRole('Patient');
@endphp
@if(request('status') === 'pending')
    <p class="small text-muted">Showing submitted and under-review requests. <a href="{{ route('reservations.index') }}">View all requests</a></p>
@endif
<div class="cbis-page-heading">
    <div>
        <h1 class="cbis-page-title mb-0">{{ $isPatient ? 'My Blood Requests' : 'Blood Reservations' }}</h1>
        <p class="cbis-page-subtitle">{{ $isPatient ? 'Track your submitted requests and staff decisions.' : 'Review patient requests, documents, stock readiness, and release progress.' }}</p>
    </div>
    @if($isPatient)
        <a href="{{ route('reservations.create') }}" class="btn btn-danger">New Request</a>
    @endif
</div>

<div class="card cbis-record-table cbis-reservation-directory" data-live-region="reservation-list">
    <div class="table-responsive cbis-mobile-table-wrap">
        <table class="table cbis-reservation-table cbis-mobile-card-table">
            <caption class="caption-top px-3 py-2 small text-muted">{{ $isPatient ? 'Open a request to view documents, status, and next steps.' : 'Open a request to review documents, approve or reject, and record releases.' }}</caption>
            <thead>
                <tr>
                    <th scope="col">{{ $isPatient ? 'Reference / Branch' : 'Reference / Patient' }}</th>
                    <th scope="col">Blood request</th>
                    <th scope="col">Units</th>
                    <th scope="col">Needed date</th>
                    <th scope="col">Status</th>
                    <th scope="col">View</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $reservation)
                    <tr class="cbis-reservation-row">
                        <td data-label="Reference">
                            <strong>{{ $reservation->reference }}</strong>
                            @unless($isPatient)
                                <div class="cbis-record-meta">{{ $reservation->patient?->name ?? 'No patient name' }}</div>
                            @endunless
                            <div class="cbis-record-meta">{{ $reservation->facility?->name ?? 'No branch' }}</div>
                        </td>
                        <td data-label="Blood request">
                            <strong>{{ $reservation->blood_type }}</strong>
                            <div class="cbis-record-meta">{{ \App\Models\BloodInventory::COMPONENTS[$reservation->component] ?? $reservation->component }}</div>
                            @if($reservation->review_notes)
                                <div class="cbis-reservation-note">Staff note: {{ $reservation->review_notes }}</div>
                            @endif
                        </td>
                        <td data-label="Units">{{ $reservation->units_requested }} unit{{ $reservation->units_requested == 1 ? '' : 's' }}</td>
                        <td data-label="Needed date">{{ $reservation->needed_on?->format('M d, Y') }}</td>
                        <td data-label="Status"><x-ui.request-status :status="$reservation->status" compact /></td>
                        <td data-label="View" class="cbis-record-actions cbis-table-actions">
                            <a href="{{ route('reservations.show',$reservation) }}" class="btn btn-sm btn-outline-secondary">View request</a>
                        </td>
                    </tr>
                @empty
                    <tr class="cbis-table-empty">
                        <td colspan="6">
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
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3" data-live-region="reservation-pages">{{ $reservations->links() }}</div>
@endsection
