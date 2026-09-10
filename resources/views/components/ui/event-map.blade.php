@props(['events', 'id' => 'dashboard-event-map'])
@php
$points = collect($events)->filter(fn ($event) => $event->latitude !== null && $event->longitude !== null)->map(fn ($event) => [
    'latitude' => (float) $event->latitude, 'longitude' => (float) $event->longitude,
    'title' => $event->title, 'venue' => $event->venue,
])->values();
@endphp
<div id="{{ $id }}" class="cbis-dashboard-map" role="region" aria-label="Event locations map"><p class="p-3 text-muted">Loading event map…</p></div>
<div id="{{ $id }}-tools" class="cbis-map-preview-tools" role="group" aria-label="Map directions controls"></div>
<div id="{{ $id }}-status" class="cbis-map-status" role="status" aria-live="polite" hidden></div>
<div class="cbis-map-caption">{{ $points->isEmpty() ? 'No event coordinates to display yet. Browse the full map for locations.' : 'Select a marker to see the activity and venue.' }}</div>
@push('scripts')
<script>
(() => {
 const element = document.getElementById(@js($id));
 if (!window.L) { element.textContent = 'Map could not load. Please check your connection and reload.'; return; }
 element.replaceChildren();
 const map = L.map(element, {scrollWheelZoom:true}).setView([10.45,123.05], 9);
 CbisMaps.addLayers(map);
 const status = document.getElementById(@js($id . '-status'));
 const renderStatus = CbisMaps.statusView(status);
 const showStatus = (...args) => { status.hidden = false; renderStatus(...args); };
 let refreshTools = () => {};
 const routing = CbisMaps.directions(map, (...args) => { showStatus(...args); refreshTools(); });
 refreshTools = CbisMaps.toolbar(map, routing, document.getElementById(@js($id . '-tools')), showStatus);
 let selectedMarker = null;
 const points = @js($points);
 points.forEach(point => {
   const popup = document.createElement('div');
   const title = document.createElement('strong'); title.textContent = point.title;
   const venue = document.createElement('div'); venue.textContent = point.venue || '';
   const button = document.createElement('button'); button.type = 'button';
   button.className = 'btn btn-sm btn-outline-danger mt-2'; button.textContent = 'Directions from my location';
   button.addEventListener('click', () => { map.closePopup(); routing.show({lat: point.latitude, lng: point.longitude}); });
   const google = document.createElement('a'); google.textContent = 'Open in Google Maps';
   google.className = 'd-block mt-2'; google.target = '_blank'; google.rel = 'noopener';
   google.href = `https://www.google.com/maps/dir/?api=1&destination=${point.latitude},${point.longitude}&travelmode=driving`;
   popup.append(title, venue, button, google);
   const marker = L.marker([point.latitude, point.longitude], {title: point.title, alt: point.title}).addTo(map).bindPopup(popup);
   marker.on('click', () => {
     selectedMarker?.getElement()?.classList.remove('cbis-map-preview-selected');
     selectedMarker = marker; marker.getElement()?.classList.add('cbis-map-preview-selected');
   });
 });
 if (points.length) map.fitBounds(points.map(p => [p.latitude,p.longitude]), {padding:[35,35],maxZoom:13});
 if (window.ResizeObserver) new ResizeObserver(() => map.invalidateSize()).observe(element);
})();
</script>
@endpush
