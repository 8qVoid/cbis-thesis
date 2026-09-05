@props(['events', 'id' => 'dashboard-event-map'])
@php
$points = collect($events)->filter(fn ($event) => $event->latitude !== null && $event->longitude !== null)->map(fn ($event) => [
    'latitude' => (float) $event->latitude, 'longitude' => (float) $event->longitude,
    'title' => $event->title, 'venue' => $event->venue,
])->values();
@endphp
<div id="{{ $id }}" class="cbis-dashboard-map" role="region" aria-label="Event locations map"><p class="p-3 text-muted">Loading event map…</p></div>
<div class="cbis-map-caption">{{ $points->isEmpty() ? 'No event coordinates to display yet. Browse the full map for locations.' : 'Select a marker to see the activity and venue.' }}</div>
@push('scripts')
<script>
(() => {
 const element = document.getElementById(@js($id));
 if (!window.L) { element.textContent = 'Map could not load. Please check your connection and reload.'; return; }
 element.replaceChildren();
 const map = L.map(element, {scrollWheelZoom:false}).setView([10.45,123.05], 9);
 L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; OpenStreetMap contributors'}).addTo(map);
 const points = @js($points);
 points.forEach(point => {
   const popup = document.createElement('div');
   const title = document.createElement('strong'); title.textContent = point.title;
   const venue = document.createElement('div'); venue.textContent = point.venue || '';
   popup.append(title, venue);
   L.marker([point.latitude, point.longitude]).addTo(map).bindPopup(popup);
 });
 if (points.length) map.fitBounds(points.map(p => [p.latitude,p.longitude]), {padding:[35,35],maxZoom:13});
 if (window.ResizeObserver) new ResizeObserver(() => map.invalidateSize()).observe(element);
})();
</script>
@endpush
