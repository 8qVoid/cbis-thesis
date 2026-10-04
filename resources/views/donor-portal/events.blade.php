@extends('layouts.app')

@section('content')
<div class="cbis-page-heading">
    <div>
        <h1 class="cbis-page-title mb-0">My Event Registrations</h1>
        <p class="cbis-page-subtitle">Track your upcoming activities and attendance.</p>
    </div>
    <div class="cbis-heading-actions">
        <a href="{{ route('public.map') }}" class="btn btn-danger">Find Events on Map</a>
        @if(auth('donor')->check() && ! auth('web')->check())
            <a href="{{ route('donor.portal.profile') }}" class="btn btn-outline-secondary">Back to Profile</a>
        @endif
    </div>
</div>

<div class="card cbis-record-table cbis-reservation-directory" data-live-region="registrations-list">
    <div class="card-body p-0">
        <div class="table-responsive cbis-mobile-table-wrap">
            <table class="table table-hover align-middle mb-0 cbis-mobile-card-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Type</th>
                        <th>Facility</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrations as $registration)
                        <tr class="cbis-reservation-row">
                            <td data-label="Event"><strong>{{ $registration->event?->title ?? '-' }}</strong></td>
                            <td data-label="Type"><span>{{ $registration->event?->event_type_label ?? '-' }}</span></td>
                            <td data-label="Facility"><span>{{ $registration->event?->facility?->name ?? '-' }}</span></td>
                            <td data-label="Date"><span>{{ $registration->event?->event_date?->format('M d, Y') ?? '-' }}</span></td>
                            <td data-label="Time"><span>{{ $registration->event?->time_range_label ?? '-' }}</span></td>
                            <td data-label="Status"><span class="badge text-bg-light">{{ $registration->status === 'no_show' ? 'No-show' : ucfirst($registration->status) }}</span></td>
                            <td data-label="Action" class="cbis-record-actions cbis-table-actions">
                                @if($registration->status === 'registered' && $registration->event?->isRegistrationOpen())
                                    <form
                                        method="POST"
                                        action="{{ route('donor.events.cancel', $registration->event) }}"
                                        class="d-inline js-confirm-action"
                                        data-confirm-title="Cancel registration?"
                                        data-confirm-message="This will cancel your registration for {{ $registration->event->title }}."
                                        data-confirm-button="Cancel Registration"
                                        data-confirm-variant="danger"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="cbis-table-empty">
                            <td colspan="7"><div class="cbis-empty-state"><strong>No event registrations yet.</strong><span>Use Find Events on Map to register and track your attendance here.</span></div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3" data-live-region="registrations-pages">
    {{ $registrations->links() }}
</div>
@endsection
