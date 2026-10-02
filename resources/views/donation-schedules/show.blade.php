@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageSchedules = ($currentUser?->can('manage schedules') ?? false);
@endphp
<div class="cbis-page-heading" data-live-region="event-detail-actions">
    <div><div class="cbis-eyebrow">{{ $donationSchedule->event_type_label }}</div><h1 class="cbis-page-title mb-0">{{ $donationSchedule->title }}</h1><p class="cbis-page-subtitle">Schedule, venue, publication status, and registrations.</p></div><div class="d-flex flex-wrap gap-2"><a href="{{ route('donation-schedules.index') }}" class="btn btn-outline-secondary">Back to events</a>
    @if($canManageSchedules && in_array($donationSchedule->status, ['planned', 'ongoing'], true))
        <form
            method="POST"
            action="{{ route('donation-schedules.end', $donationSchedule) }}"
            class="js-confirm-action"
            data-confirm-title="End event?"
            data-confirm-message="This will mark {{ $donationSchedule->title }} as completed, remove it from public upcoming event listings, and mark remaining registered donors as no-show."
            data-confirm-button="End Event"
            data-confirm-variant="success"
        >
            @csrf
            @method('PATCH')
            <button class="btn btn-outline-success">End Event</button>
        </form>
    @endif</div>
</div>
<div class="card card-body" data-live-region="event-details">
    @if($donationSchedule->photo_path)
        <img src="{{ asset('storage/'.$donationSchedule->photo_path) }}" alt="{{ $donationSchedule->title }}" class="cbis-detail-photo mb-3">
    @endif
    <dl class="cbis-record-details mb-0"><div><dt>Type</dt><dd>{{ $donationSchedule->event_type_label }}</dd></div><div><dt>Facility</dt><dd>{{ $donationSchedule->facility?->name ?? 'Not assigned' }}</dd></div><div><dt>Date</dt><dd>{{ $donationSchedule->event_date?->format('M j, Y') }}</dd></div><div><dt>Time</dt><dd>{{ $donationSchedule->time_range_label }}</dd></div><div class="cbis-detail-span"><dt>Venue / address</dt><dd>{{ $donationSchedule->venue }}</dd></div>
    @if($donationSchedule->contact_person || $donationSchedule->contact_number)
    <div><dt>Contact</dt><dd>{{ collect([$donationSchedule->contact_person, $donationSchedule->contact_number])->filter()->implode(' / ') }}</dd></div>
    @endif
    <div><dt>Status</dt><dd><span class="cbis-inline-status {{ in_array($donationSchedule->status, ['planned','ongoing']) ? 'cbis-tone-success' : 'cbis-tone-warning' }}">{{ ucfirst($donationSchedule->status) }}</span></dd></div><div><dt>Public listing</dt><dd>{{ $donationSchedule->is_public ? 'Visible' : 'Private' }}</dd></div><div><dt>Coordinates</dt><dd>{{ $donationSchedule->latitude ?? '—' }}, {{ $donationSchedule->longitude ?? '—' }}</dd></div><div class="cbis-detail-span"><dt>Description</dt><dd>{{ $donationSchedule->description ?: 'No description provided' }}</dd></div></dl>
</div>

<div class="card mt-3" data-live-region="event-registrations">
    <div class="card-header">Event Registrations</div>
    <div class="card-body p-0">
        <div class="table-responsive cbis-mobile-table-wrap">
        <table class="table table-striped mb-0 cbis-mobile-card-table cbis-status-table">
            <thead>
                <tr>
                    <th>Donor Name</th>
                    <th>Blood Type</th>
                    <th>Contact</th>
                    <th>Registered At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($donationSchedule->eventRegistrations as $registration)
                    @php
                        $statusClasses = [
                            'registered' => 'cbis-status-active',
                            'attended' => 'text-bg-success',
                            'no_show' => 'text-bg-warning',
                            'cancelled' => 'text-bg-secondary',
                        ];
                        $statusLabels = [
                            'registered' => 'Registered',
                            'attended' => 'Attended',
                            'no_show' => 'No-show',
                            'cancelled' => 'Cancelled',
                        ];
                    @endphp
                    <tr class="cbis-reservation-row">
                        <td data-label="Donor">{{ $registration->donor?->full_name ?? '-' }}</td>
                        <td data-label="Blood type">{{ $registration->donor?->blood_type ?? '-' }}</td>
                        <td data-label="Contact">{{ $registration->donor?->contact_number ?? '-' }}</td>
                        <td data-label="Registered">{{ $registration->registered_at?->format('M j, Y · g:i A') ?? '-' }}</td>
                        <td data-label="Status">
                            <span class="badge {{ $statusClasses[$registration->status] ?? 'text-bg-secondary' }}">
                                {{ $statusLabels[$registration->status] ?? ucfirst($registration->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No donor registrations yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
