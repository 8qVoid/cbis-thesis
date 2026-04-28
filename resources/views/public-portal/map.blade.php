@extends('layouts.app')
@section('content')
<div class="mb-3">
    <h1 class="cbis-page-title mb-0">Events, Facilities and Map</h1>
    <p class="cbis-page-subtitle">Upcoming public activities and blood service facilities in Negros.</p>
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

<div class="d-flex flex-wrap align-items-center gap-3 mb-2">
    <div class="d-flex align-items-center gap-2 small fw-semibold">
        <span class="cbis-map-dot cbis-map-dot-event"></span> Events / Activities
    </div>
    <div class="d-flex align-items-center gap-2 small fw-semibold">
        <span class="cbis-map-dot cbis-map-dot-facility"></span> Facilities
    </div>
    <div class="form-check form-switch ms-md-auto">
        <input class="form-check-input js-map-toggle" type="checkbox" id="toggleEvents" value="event" checked>
        <label class="form-check-label small" for="toggleEvents">Show events</label>
    </div>
    <div class="form-check form-switch">
        <input class="form-check-input js-map-toggle" type="checkbox" id="toggleFacilities" value="facility" checked>
        <label class="form-check-label small" for="toggleFacilities">Show facilities</label>
    </div>
</div>

<style>
    .cbis-map-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 999px;
        box-shadow: 0 0 0 3px rgba(0, 0, 0, .08);
    }

    .cbis-map-dot-event { background: #0d6efd; }
    .cbis-map-dot-facility { background: #dc3545; }

    .cbis-map-pin {
        display: block;
        position: relative;
        width: 22px;
        height: 22px;
        border: 3px solid #fff;
        border-radius: 999px 999px 999px 0;
        box-shadow: 0 3px 10px rgba(20, 24, 31, .3);
        transform: rotate(-45deg);
    }

    .cbis-map-pin::after {
        content: "";
        position: absolute;
        inset: 5px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .9);
    }

    .cbis-map-pin-event { background: #0d6efd; }
    .cbis-map-pin-facility { background: #dc3545; }

    .cbis-map-popup {
        width: min(280px, 72vw);
    }

    .cbis-map-popup img {
        width: 100%;
        height: 130px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: .75rem;
        background: #f1f3f5;
    }

    .cbis-map-popup-title {
        font-size: 1rem;
        font-weight: 800;
        margin-bottom: .35rem;
    }

    .cbis-map-popup-row {
        margin-bottom: .25rem;
    }
</style>

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
const markersByType = { event: [], facility: [] };

const markerIcons = {
    event: L.divIcon({
        className: 'cbis-map-pin-wrap',
        html: '<span class="cbis-map-pin cbis-map-pin-event"></span>',
        iconSize: [22, 22],
        iconAnchor: [11, 22],
        popupAnchor: [0, -20],
    }),
    facility: L.divIcon({
        className: 'cbis-map-pin-wrap',
        html: '<span class="cbis-map-pin cbis-map-pin-facility"></span>',
        iconSize: [22, 22],
        iconAnchor: [11, 22],
        popupAnchor: [0, -20],
    }),
};

const escapeHtml = (value) => String(value ?? 'N/A')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const popupImage = (item) => {
    if (!item.photo_url) {
        return '';
    }

    return `<img src="${escapeHtml(item.photo_url)}" alt="${escapeHtml(item.title)}">`;
};

const eventPopup = (item) => {
    const action = item.is_registered
        ? '<span class="badge text-bg-success mt-2">Already Registered</span>'
        : `<a href="${escapeHtml(item.action_url)}" class="btn btn-sm btn-outline-danger mt-2">Register for this Event</a>`;

    return `
        <div class="cbis-map-popup">
            ${popupImage(item)}
            <div class="cbis-map-popup-title">${escapeHtml(item.title)}</div>
            <div class="cbis-map-popup-row"><strong>Type:</strong> ${escapeHtml(item.event_type)}</div>
            <div class="cbis-map-popup-row"><strong>Facility:</strong> ${escapeHtml(item.facility)}</div>
            <div class="cbis-map-popup-row"><strong>Date:</strong> ${escapeHtml(item.date)}</div>
            <div class="cbis-map-popup-row"><strong>Time:</strong> ${escapeHtml(item.time)}</div>
            <div class="cbis-map-popup-row"><strong>Venue:</strong> ${escapeHtml(item.venue)}</div>
            <div class="cbis-map-popup-row"><strong>Contact:</strong> ${escapeHtml(item.contact_person)} / ${escapeHtml(item.contact_number)}</div>
            ${action}
        </div>
    `;
};

const facilityPopup = (item) => `
    <div class="cbis-map-popup">
        ${popupImage(item)}
        <div class="cbis-map-popup-title">${escapeHtml(item.title)}</div>
        <div class="cbis-map-popup-row"><strong>Type:</strong> ${escapeHtml(item.facility_type)}</div>
        <div class="cbis-map-popup-row"><strong>Address:</strong> ${escapeHtml(item.address)}</div>
        <div class="cbis-map-popup-row"><strong>Contact:</strong> ${escapeHtml(item.contact_person)} / ${escapeHtml(item.contact_number)}</div>
        <div class="cbis-map-popup-row"><strong>Email:</strong> ${escapeHtml(item.email)}</div>
    </div>
`;

const buildPopup = (item) => item.type === 'facility' ? facilityPopup(item) : eventPopup(item);

data.forEach((item) => {
    if (!NEGROS_BOUNDS.contains([item.lat, item.lng])) {
        return;
    }
    const marker = L.marker([item.lat, item.lng], {
        icon: markerIcons[item.type] ?? markerIcons.event,
    }).addTo(map);
    inBoundsMarkers.push(marker);
    markersByType[item.type]?.push(marker);
    marker.bindPopup(buildPopup(item), { maxWidth: 320 });
});

if (inBoundsMarkers.length === 0) {
    L.popup()
        .setLatLng(NEGROS_CENTER)
        .setContent('No map coordinates available for the current filters.')
        .openOn(map);
} else {
    const featureGroup = L.featureGroup(inBoundsMarkers);
    map.fitBounds(featureGroup.getBounds().pad(0.15));
}

document.querySelectorAll('.js-map-toggle').forEach((toggle) => {
    toggle.addEventListener('change', () => {
        const type = toggle.value;

        markersByType[type].forEach((marker) => {
            if (toggle.checked) {
                marker.addTo(map);
            } else {
                marker.removeFrom(map);
            }
        });
    });
});
</script>
@endpush
