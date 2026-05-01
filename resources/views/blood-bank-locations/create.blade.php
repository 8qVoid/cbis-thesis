@extends('layouts.app')
@section('content')
<h4>Add Blood Bank Location</h4>
<form method="POST" action="{{ route('blood-bank-locations.store') }}" class="card card-body" enctype="multipart/form-data">@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Facility</label><select name="facility_id" class="form-select">@foreach($facilities as $facility)<option value="{{ $facility->id }}">{{ $facility->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control" placeholder="+63 917 123 4567 or 09171234567"></div>
<div class="col-md-6"><label class="form-label">Location Photo</label><input name="photo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" required><small class="text-muted">Upload a JPG, PNG, or WebP image up to 4 MB.</small></div>
<div class="col-12">
<label class="form-label">Pick Coordinates on Map</label>
<div id="location-map" class="rounded border" style="height: 320px"></div>
<small class="text-muted">Click map to set latitude and longitude within Negros.</small>
</div>
<div class="col-md-3"><label class="form-label">Latitude</label><input id="latitude" name="latitude" class="form-control bg-light" value="{{ old('latitude') }}" required readonly></div>
<div class="col-md-3"><label class="form-label">Longitude</label><input id="longitude" name="longitude" class="form-control bg-light" value="{{ old('longitude') }}" required readonly></div>
<div class="col-md-6 d-flex align-items-end"><button class="btn btn-danger">Save</button></div>
</div></form>
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

const map = L.map('location-map', {
    maxBounds: NEGROS_BOUNDS,
    maxBoundsViscosity: 1.0
}).setView(initialPoint, initialZoom);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const facilityIcon = L.divIcon({
    className: 'cbis-map-pin-wrap',
    html: '<span class="cbis-map-pin cbis-map-pin-facility"></span>',
    iconSize: [22, 22],
    iconAnchor: [11, 22],
});

let marker = null;
if (typedPoint && NEGROS_BOUNDS.contains(typedPoint)) {
    marker = L.marker(typedPoint, { icon: facilityIcon }).addTo(map);
}

map.on('click', (event) => {
    const { lat, lng } = event.latlng;
    document.getElementById('latitude').value = lat.toFixed(7);
    document.getElementById('longitude').value = lng.toFixed(7);

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { icon: facilityIcon }).addTo(map);
    }
});
</script>
@endpush
