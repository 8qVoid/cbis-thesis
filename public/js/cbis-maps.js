(() => {
    const addLayers = (map) => {
        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);
        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19, attribution: 'Tiles &copy; Esri',
        });
        L.control.layers({ Street: street, Satellite: satellite }, {}, { collapsed: false }).addTo(map);
    };

    const directions = (map, status) => {
        let controller;
        let routeLayer;
        let originMarker;
        let sequence = 0;
        const clear = () => {
            sequence++;
            controller?.abort();
            if (routeLayer) map.removeLayer(routeLayer);
            routeLayer = null;
        };
        const show = async (destination, origin = null) => {
            clear();
            const request = sequence;
            status('Getting your location…');
            try {
                if (!origin) {
                    const position = await new Promise((resolve, reject) => {
                        if (!navigator.geolocation) return reject(new Error('Location is unavailable.'));
                        navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 10000, maximumAge: 60000 });
                    });
                    origin = L.latLng(position.coords.latitude, position.coords.longitude);
                }
                if (request !== sequence) return;
                if (originMarker) originMarker.setLatLng(origin);
                else originMarker = L.circleMarker(origin, { radius: 8, color: '#fff', fillColor: '#2563eb', fillOpacity: 1, weight: 3 }).addTo(map).bindPopup('Your location');
                status('Finding a driving route along roads…');
                controller = new AbortController();
                const timeout = setTimeout(() => controller?.abort(), 15000);
                let result;
                try {
                    const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${origin.lng},${origin.lat};${destination.lng},${destination.lat}?overview=full&geometries=geojson`, { signal: controller.signal });
                    if (!response.ok) throw new Error('Routing unavailable');
                    result = await response.json();
                } finally { clearTimeout(timeout); }
                if (request !== sequence) return;
                const route = result.routes?.[0];
                if (result.code !== 'Ok' || !route?.geometry?.coordinates?.length) throw new Error('No route');
                routeLayer = L.geoJSON(route.geometry, { style: { color: '#2563eb', weight: 5, opacity: 0.85 } }).addTo(map);
                map.fitBounds(routeLayer.getBounds(), { padding: [35, 35], maxZoom: 16 });
                status(`Driving route: ${(route.distance / 1000).toFixed(1)} km · about ${Math.max(1, Math.round(route.duration / 60))} min. Route ends at the nearest mapped road; travel time excludes live traffic.`);
            } catch (error) {
                if (request !== sequence) return;
                status(error.code === 1 ? 'Allow location access to show directions.' : 'Could not load a road route. Try again or open directions in Google Maps.', true);
            }
        };
        return { show, clear };
    };
    window.CbisMaps = { addLayers, directions };
})();
