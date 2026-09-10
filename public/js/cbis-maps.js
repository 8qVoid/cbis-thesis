(() => {
    const addLayers = (map) => {
        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);
        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19, attribution: 'Tiles &copy; Esri',
        });
        const Layers = L.Control.extend({
            options: { position: 'topright' },
            onAdd() {
                const container = L.DomUtil.create('div', 'cbis-map-layers');
                container.setAttribute('role', 'group');
                container.setAttribute('aria-label', 'Map appearance');
                L.DomEvent.disableClickPropagation(container);
                L.DomEvent.disableScrollPropagation(container);
                const options = [[ 'Street', street ], [ 'Satellite', satellite ]];
                const buttons = options.map(([label, layer]) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = label;
                    button.setAttribute('aria-pressed', String(layer === street));
                    button.addEventListener('click', () => {
                        options.forEach(([, other]) => { if (other !== layer) map.removeLayer(other); });
                        layer.addTo(map);
                        buttons.forEach((item, index) => item.setAttribute('aria-pressed', String(options[index][1] === layer)));
                    });
                    container.append(button);
                    return button;
                });
                return container;
            },
        });
        new Layers().addTo(map);
    };

    const locate = () => new Promise((resolve, reject) => {
        if (!navigator.geolocation) return reject(new Error('Location is unavailable.'));
        navigator.geolocation.getCurrentPosition(position => resolve(L.latLng(position.coords.latitude, position.coords.longitude)), reject,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    });

    const statusView = (element) => (message, error = false, state = {}) => {
        element.replaceChildren();
        element.classList.toggle('text-danger', error);
        element.setAttribute('aria-busy', String(Boolean(state.loading)));
        if (state.loading) {
            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm';
            spinner.setAttribute('aria-hidden', 'true');
            element.append(spinner);
        }
        const text = document.createElement('span');
        text.textContent = message;
        element.append(text);
    };

    const directions = (map, status) => {
        let controller;
        let routeLayer;
        let originMarker;
        let sequence = 0;
        let loading = false;
        const cache = new Map();
        const fit = () => { if (routeLayer) map.fitBounds(routeLayer.getBounds(), { padding: [35, 35], maxZoom: 16 }); };
        const updateOrigin = origin => {
            if (originMarker) originMarker.setLatLng(origin);
            else originMarker = L.circleMarker(origin, { radius: 8, color: '#fff', fillColor: '#2563eb', fillOpacity: 1, weight: 3 }).addTo(map).bindPopup('Your location');
        };
        const clear = () => {
            sequence++;
            controller?.abort();
            if (routeLayer) map.removeLayer(routeLayer);
            routeLayer = null;
            loading = false;
            status('Directions cleared. Select a pin to show a route.', false, { cleared: true });
        };
        const show = async (destination, origin = null) => {
            const request = ++sequence;
            controller?.abort();
            loading = true;
            status('Getting your location…', false, { loading: true });
            try {
                if (!origin) {
                    origin = await locate();
                }
                if (request !== sequence) return;
                updateOrigin(origin);
                status('Finding a driving route…', false, { loading: true });
                const key = `${origin.lng},${origin.lat};${destination.lng},${destination.lat}`;
                const requestController = new AbortController();
                controller = requestController;
                const timeout = setTimeout(() => requestController.abort(), 15000);
                let result = cache.get(key);
                try {
                    if (!result) {
                        const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${origin.lng},${origin.lat};${destination.lng},${destination.lat}?overview=full&geometries=geojson`, { signal: requestController.signal });
                        if (!response.ok) throw new Error('Routing unavailable');
                        result = await response.json();
                    }
                } finally { clearTimeout(timeout); }
                if (request !== sequence) return;
                const route = result.routes?.[0];
                if (result.code !== 'Ok' || !route?.geometry?.coordinates?.length) throw new Error('No route');
                cache.set(key, result);
                if (routeLayer) map.removeLayer(routeLayer);
                routeLayer = L.geoJSON(route.geometry, { interactive: false, style: { color: '#2563eb', weight: 5, opacity: 0.85 } }).addTo(map);
                fit();
                loading = false;
                status(`Driving route: ${(route.distance / 1000).toFixed(1)} km · about ${Math.max(1, Math.round(route.duration / 60))} min. Route ends at the nearest mapped road; travel time excludes live traffic.`, false, { route, destination });
            } catch (error) {
                if (request !== sequence) return;
                loading = false;
                status(error.code === 1 ? 'Allow location access to show directions.' : (routeLayer ? 'Could not load the selected route. The previous route is still shown. Try again or open Google Maps.' : 'Could not load a road route. Try again or open directions in Google Maps.'), true);
            }
        };
        return { show, clear, fit, updateOrigin, hasRoute: () => Boolean(routeLayer), isLoading: () => loading };
    };

    const toolbar = (map, routing, element, status) => {
        element.classList.add('cbis-map-tools');
        const makeButton = (label, handler) => {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'btn btn-sm btn-outline-secondary';
            button.textContent = label; button.addEventListener('click', handler); element.append(button);
            return button;
        };
        const location = makeButton('My location', async () => {
            location.disabled = true;
            status('Getting your location…', false, { loading: true });
            try {
                const origin = await locate(); routing.updateOrigin(origin); map.setView(origin, 15);
                status('Your location is marked in blue. Select a pin for directions.');
            } catch (error) { status(error.code === 1 ? 'Allow location access to find your position.' : 'Could not get your location. Please try again.', true); }
            finally { location.disabled = false; }
        });
        const fit = makeButton('Fit route', routing.fit);
        const clear = makeButton('Clear directions', routing.clear);
        const refresh = () => { fit.disabled = !routing.hasRoute(); clear.disabled = !routing.hasRoute() && !routing.isLoading(); };
        refresh();
        return refresh;
    };
    window.CbisMaps = { addLayers, directions, statusView, toolbar };
})();
