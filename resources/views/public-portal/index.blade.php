@extends('layouts.app')
@section('content')
<div class="p-4 bg-white rounded shadow-sm mb-4 cbis-card">
    <h1 class="cbis-page-title">Centralized Blood Inventory Public Portal</h1>
    <p class="cbis-page-subtitle">View verified upcoming events and map locations in real time.</p>
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
            <button class="btn btn-outline-danger w-100">Filter Events</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header">Upcoming Public Events</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Facility</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue / Address</th>
                        <th>Contact</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                        <tr>
                            <td>{{ $schedule->facility?->name ?? '-' }}</td>
                            <td>{{ $schedule->title }}</td>
                            <td>{{ $schedule->event_type_label }}</td>
                            <td>{{ $schedule->event_date?->toDateString() }}</td>
                            <td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>
                            <td>{{ $schedule->venue }}</td>
                            <td>{{ $schedule->contact_person ?? '-' }} / {{ $schedule->contact_number ?? '-' }}</td>
                            <td>
                                <a href="{{ route('donor.events.join', $schedule) }}" class="btn btn-sm btn-outline-danger">Register for this Event</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No upcoming public events.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="{{ route('public.map') }}" class="btn btn-danger">View Events & Map</a>
</div>
@endsection
