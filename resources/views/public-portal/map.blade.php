@extends('layouts.app')
@section('content')
<div class="mb-3">
    <h1 class="cbis-page-title mb-0">Events and Map</h1>
    <p class="cbis-page-subtitle">Upcoming public blood donation and bloodletting activities in Negros.</p>
</div>

@include('public-portal.partials.nav')

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
            <button class="btn btn-outline-danger w-100">Apply Filters</button>
        </div>
    </div>
</form>

<div id="map" style="height:520px" class="rounded border mb-3 cbis-card"></div>

<div class="card">
    <div class="card-header">Upcoming Events</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Facility</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue</th>
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
                            <td>
                                @if(in_array($event->id, $registeredEventIds ?? [], true))
                                    <span class="badge text-bg-success">Already Registered</span>
                                @else
                                    <a href="{{ route('donor.events.join', $event) }}" class="btn btn-sm btn-outline-danger">Register for this Event</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No public events found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const NEGROS_CENTER = [10.6765, 122.9511];
const NEGROS_BOUNDS = L.latLngBounds([9.0, 122.0], [11.5, 123.8]);
const map = L.map('map', {
    maxBounds: NEGROS_BOUNDS,
    maxBoundsViscosity: 1.0
}).setView(NEGROS_CENTER, 9);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
const data = @json($mapLocations);
const inBoundsMarkers = [];

if (data.length === 0) {
    L.popup()
        .setLatLng(NEGROS_CENTER)
        .setContent('No map coordinates available for the current event filters.')
        .openOn(map);
}

data.forEach((item) => {
    if (!NEGROS_BOUNDS.contains([item.lat, item.lng])) {
        return;
    }
    const marker = L.marker([item.lat, item.lng]).addTo(map);
    inBoundsMarkers.push(marker);
    marker.bindPopup(
        `<strong>${item.title}</strong><br>` +
        `Type: ${item.event_type}<br>` +
        `Facility: ${item.facility}<br>` +
        `Date: ${item.date}<br>` +
        `Time: ${item.time}<br>` +
        `Venue: ${item.venue}<br>` +
        `Contact: ${item.contact_person} / ${item.contact_number}`
    );
});

if (inBoundsMarkers.length > 0) {
    const featureGroup = L.featureGroup(inBoundsMarkers);
    map.fitBounds(featureGroup.getBounds().pad(0.15));
}
</script>
@endpush
