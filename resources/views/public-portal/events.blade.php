@extends('layouts.app')
@section('content')
<div class="mb-3">
    <h1 class="cbis-page-title mb-0">Public Event Schedules</h1>
    <p class="cbis-page-subtitle">Upcoming donation and bloodletting events visible to the community.</p>
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
        <div class="col-md-3">
            <label class="form-label">Facility</label>
            <select name="facility_id" class="form-select">
                <option value="">All</option>
                @foreach($facilities as $facility)
                    <option value="{{ $facility->id }}" @selected((int) request('facility_id') === $facility->id)>{{ $facility->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="event_date" class="form-control" value="{{ request('event_date') }}">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-outline-danger w-100">Filter</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Facility</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue / Address</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->event_type_label }}</td>
                            <td>{{ $event->facility?->name ?? '-' }}</td>
                            <td>{{ $event->event_date?->toDateString() }}</td>
                            <td>{{ $event->start_time }} - {{ $event->end_time }}</td>
                            <td>{{ $event->venue }}</td>
                            <td>{{ $event->contact_person ?? '-' }} / {{ $event->contact_number ?? '-' }}</td>
                            <td>{{ ucfirst($event->status) }}</td>
                            <td>
                                <a href="{{ route('donor.events.join', $event) }}" class="btn btn-sm btn-outline-danger">Register for this Event</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No public events found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $events->links() }}
</div>
@endsection
