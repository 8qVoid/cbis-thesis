@extends('layouts.app')
@section('content')
@php
    $currentUser = auth('web')->user();
    $canManageSchedules = ! ($currentUser?->isCentralAdmin() ?? false) && ($currentUser?->can('manage schedules') ?? false);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Event Schedules</h1>
        <p class="cbis-page-subtitle">Plan and publish blood donation and bloodletting activities.</p>
    </div>
    @if($canManageSchedules)
        <a href="{{ route('donation-schedules.create') }}" class="btn btn-danger">Create Event</a>
    @endif
</div>

<form method="GET" class="card card-body mb-3 cbis-filter-card" data-auto-filter="true">
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label">Event Type</label>
            <select name="event_type" class="form-select">
                <option value="">All</option>
                <option value="blood_donation" @selected(request('event_type') === 'blood_donation')>Blood Donation</option>
                <option value="bloodletting" @selected(request('event_type') === 'bloodletting')>Bloodletting</option>
            </select>
        </div>
        @if(auth('web')->user()?->isCentralAdmin())
            <div class="col-md-3">
                <label class="form-label">Facility</label>
                <select name="facility_id" class="form-select">
                    <option value="">All</option>
                    @foreach($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected((int) request('facility_id') === $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-2">
            <label class="form-label">Date</label>
            <input type="date" name="event_date" class="form-control" value="{{ request('event_date') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                @foreach(['planned', 'ongoing', 'completed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped bg-white">
        <thead>
            <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Facility</th>
                <th>Date</th>
                <th>Time</th>
                <th>Venue</th>
                <th>Status</th>
                <th>Registration Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $schedule)
                <tr>
                    <td>{{ $schedule->title }}</td>
                    <td>{{ $schedule->event_type_label }}</td>
                    <td>{{ $schedule->facility?->name ?? '-' }}</td>
                    <td>{{ $schedule->event_date?->toDateString() }}</td>
                    <td>{{ $schedule->time_range_label }}</td>
                    <td>{{ $schedule->venue }}</td>
                    <td><span class="badge {{ in_array($schedule->status, ['planned', 'ongoing']) ? 'cbis-status-active' : 'cbis-status-expired' }}">{{ ucfirst($schedule->status) }}</span></td>
                    <td>
                        <div class="small">
                            <span class="badge cbis-status-active">Registered {{ $schedule->registrations_count ?? 0 }}</span>
                            <span class="badge text-bg-success">Attended {{ $schedule->attended_count ?? 0 }}</span>
                            <span class="badge text-bg-warning">No-show {{ $schedule->no_show_count ?? 0 }}</span>
                            <span class="badge text-bg-secondary">Cancelled {{ $schedule->cancelled_count ?? 0 }}</span>
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('donation-schedules.show', $schedule) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @if($canManageSchedules)
                            <a href="{{ route('donation-schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @if(in_array($schedule->status, ['planned', 'ongoing'], true))
                                <form
                                    method="POST"
                                    action="{{ route('donation-schedules.end', $schedule) }}"
                                    class="d-inline js-confirm-action"
                                    data-confirm-title="End event?"
                                    data-confirm-message="This will mark {{ $schedule->title }} as completed, remove it from public upcoming event listings, and mark remaining registered donors as no-show."
                                    data-confirm-button="End Event"
                                    data-confirm-variant="success"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success">End Event</button>
                                </form>
                            @endif
                            <form
                                method="POST"
                                action="{{ route('donation-schedules.destroy', $schedule) }}"
                                class="d-inline js-confirm-action"
                                data-confirm-title="Delete event?"
                                data-confirm-message="This will permanently remove {{ $schedule->title }} from the schedule list."
                                data-confirm-button="Delete Event"
                                data-confirm-variant="danger"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No events found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $schedules->links() }}
@endsection
