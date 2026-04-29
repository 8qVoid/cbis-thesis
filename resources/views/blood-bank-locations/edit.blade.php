@extends('layouts.app')
@section('content')
<h4>Edit Blood Bank Location</h4>
<form method="POST" action="{{ route('blood-bank-locations.update',$bloodBankLocation) }}" class="card card-body" enctype="multipart/form-data">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Facility</label><select name="facility_id" class="form-select">@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected($bloodBankLocation->facility_id==$facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address',$bloodBankLocation->address) }}" required></div>
<div class="col-md-6"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control" value="{{ old('contact_number',$bloodBankLocation->contact_number) }}" placeholder="+63 917 123 4567 or 09171234567"></div>
<div class="col-md-6">
<label class="form-label">Location Photo</label>
@if($bloodBankLocation->photo_path)
<div class="mb-2"><img src="{{ asset('storage/'.$bloodBankLocation->photo_path) }}" alt="{{ $bloodBankLocation->facility?->name ?? 'Location photo' }}" class="img-fluid rounded border" style="max-height: 180px; object-fit: cover;"></div>
@endif
<input name="photo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" @required(! $bloodBankLocation->photo_path)>
<small class="text-muted">{{ $bloodBankLocation->photo_path ? 'Upload a new image only if you want to replace the current photo.' : 'Upload a JPG, PNG, or WebP image up to 4 MB.' }}</small>
</div>
<div class="col-12">
<label class="form-label">Pick Coordinates on Map</label>
<div id="location-map" class="rounded border" style="height: 320px"></div>
<small class="text-muted">Click map to update latitude and longitude within Negros.</small>
</div>
<div class="col-md-3"><label class="form-label">Latitude</label><input id="latitude" name="latitude" class="form-control bg-light" value="{{ old('latitude',$bloodBankLocation->latitude) }}" required readonly></div>
<div class="col-md-3"><label class="form-label">Longitude</label><input id="longitude" name="longitude" class="form-control bg-light" value="{{ old('longitude',$bloodBankLocation->longitude) }}" required readonly></div>
<div class="col-md-6 d-flex align-items-end"><button class="btn btn-danger">Update</button></div>
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
