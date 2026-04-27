@extends('layouts.app')
@section('content')
<h4>Create Event Schedule</h4>
<form method="POST" action="{{ route('donation-schedules.store') }}" class="card card-body">
    @csrf
    <div class="row g-3">
        @if(auth('web')->user()?->isCentralAdmin())
            <div class="col-md-4">
                <label class="form-label">Facility</label>
                <select name="facility_id" class="form-select" required>
                    @foreach($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected((int) old('facility_id') === $facility->id)>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-4">
            <label class="form-label">Event Type</label>
            <select name="event_type" class="form-select" required>
                <option value="blood_donation" @selected(old('event_type') === 'blood_donation')>Blood Donation</option>
                <option value="bloodletting" @selected(old('event_type') === 'bloodletting')>Bloodletting</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                @foreach(['planned', 'ongoing', 'completed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('status', 'planned') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Title</label>
            <input name="title" class="form-control" value="{{ old('title') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Venue / Address</label>
            <input name="venue" class="form-control" value="{{ old('venue') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Date</label>
            <input name="event_date" type="date" class="form-control" value="{{ old('event_date') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Start Time</label>
            <input name="start_time" type="time" class="form-control" value="{{ old('start_time') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">End Time</label>
            <input name="end_time" type="time" class="form-control" value="{{ old('end_time') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Contact Person</label>
            <input name="contact_person" class="form-control" value="{{ old('contact_person') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Contact Number (PH)</label>
            <input name="contact_number" class="form-control" value="{{ old('contact_number') }}" placeholder="+63 917 123 4567 or 09171234567">
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Show to Public</label>
            <select name="is_public" class="form-select">
                <option value="1" @selected(old('is_public', '1') === '1')>Yes</option>
                <option value="0" @selected(old('is_public') === '0')>No</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Pick Coordinates on Map (optional)</label>
            <div id="event-map" class="rounded border" style="height: 320px"></div>
            <small class="text-muted">Click map to set latitude and longitude.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Latitude</label>
            <input id="latitude" name="latitude" class="form-control bg-light" value="{{ old('latitude') }}" placeholder="e.g. 14.5995000" readonly>
        </div>
        <div class="col-md-6">
            <label class="form-label">Longitude</label>
            <input id="longitude" name="longitude" class="form-control bg-light" value="{{ old('longitude') }}" placeholder="e.g. 120.9842000" readonly>
        </div>
        <div class="col-12">
            <button class="btn btn-danger">Save Event</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
const NEGROS_CENTER = [10.6765, 122.9511];
const NEGROS_BOUNDS = L.latLngBounds([9.0, 122.0], [11.5, 123.8]);

const typedLat = parseFloat(document.getElementById('latitude').value);
const typedLng = parseFloat(document.getElementById('longitude').value);
const hasTypedCoords = !isNaN(typedLat) && !isNaN(typedLng);
const typedPoint = hasTypedCoords ? L.latLng(typedLat, typedLng) : null;
const initialPoint = typedPoint && NEGROS_BOUNDS.contains(typedPoint) ? typedPoint : L.latLng(NEGROS_CENTER[0], NEGROS_CENTER[1]);
const initialZoom = typedPoint && NEGROS_BOUNDS.contains(typedPoint) ? 13 : 9;

const map = L.map('event-map', {
    maxBounds: NEGROS_BOUNDS,
    maxBoundsViscosity: 1.0
}).setView(initialPoint, initialZoom);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let marker = null;
if (typedPoint && NEGROS_BOUNDS.contains(typedPoint)) {
    marker = L.marker(typedPoint).addTo(map);
}

map.on('click', (event) => {
    const { lat, lng } = event.latlng;
    document.getElementById('latitude').value = lat.toFixed(7);
    document.getElementById('longitude').value = lng.toFixed(7);

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map);
    }
});
</script>
@endpush
