@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Event Schedules</h1>
        <p class="cbis-page-subtitle">Plan and publish blood donation and bloodletting activities.</p>
    </div>
    <a href="{{ route('donation-schedules.create') }}" class="btn btn-danger">Create Event</a>
</div>

<form method="GET" class="card card-body mb-3 cbis-filter-card">
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
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-outline-danger w-100">Filter</button>
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
                <th>Registrations</th>
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
                    <td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>
                    <td>{{ $schedule->venue }}</td>
                    <td><span class="badge {{ in_array($schedule->status, ['planned', 'ongoing']) ? 'cbis-status-active' : 'cbis-status-expired' }}">{{ ucfirst($schedule->status) }}</span></td>
                    <td>{{ $schedule->registrations_count ?? 0 }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('donation-schedules.show', $schedule) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        <a href="{{ route('donation-schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('donation-schedules.destroy', $schedule) }}" class="d-inline" onsubmit="return confirm('Delete this event?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
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
