import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function setup(fetch) {
    const layers = new Set();
    const states = [];
    const fits = [];
    const map = { removeLayer: layer => layers.delete(layer), fitBounds: bounds => fits.push(bounds), setView() {} };
    const element = () => ({
        children: [], attrs: {}, handlers: {},
        classList: { add() {}, toggle() {} },
        setAttribute(name, value) { this.attrs[name] = value; },
        addEventListener(name, handler) { this.handlers[name] = handler; },
        append(...children) { this.children.push(...children); },
        replaceChildren() { this.children = []; },
    });
    const layer = geometry => ({ geometry, addTo() { layers.add(this); return this; }, bindPopup() { return this; }, setLatLng() {}, getBounds() { return geometry; } });
    const context = {
        window: {}, document: { createElement: element }, AbortController, setTimeout, clearTimeout, Map,
        navigator: { geolocation: { getCurrentPosition: callback => callback({ coords: { latitude: 10, longitude: 123 } }) } },
        fetch,
        L: {
            circleMarker: () => layer(), geoJSON: geometry => layer(geometry),
            latLng: (lat, lng) => ({ lat, lng }),
            tileLayer: () => layer(),
            DomUtil: { create: element }, DomEvent: { disableClickPropagation() {}, disableScrollPropagation() {} },
            Control: { extend: definition => class { addTo() { this.container = definition.onAdd(); map.layerButtons = this.container; } } },
        },
    };
    vm.runInNewContext(readFileSync(new URL('../../public/js/cbis-maps.js', import.meta.url), 'utf8'), context);
    const api = context.window.CbisMaps;
    const routing = api.directions(map, (message, error, state) => states.push({ message, error, state }));
    return { api, routing, map, states, fits, layers, element };
}
const origin = { lat: 10, lng: 123 };
const destination = { lat: 11, lng: 123 };
const response = (distance = 1200) => ({ ok: true, json: async () => ({ code: 'Ok', routes: [{ distance, duration: 300, geometry: { type: 'LineString', coordinates: [[123, 10], [123.1, 10.5], [123, 11]] } }] }) });

test('route state supports loading, road metrics, fit, clear, and cache without changing geometry', async () => {
    let requests = 0;
    const { routing, states, fits, layers } = setup(async () => { requests++; return response(); });
    await routing.show(destination, origin);
    assert.equal(states[0].state.loading, true);
    assert.equal(states.at(-1).state.route.distance, 1200);
    assert.equal(fits[0].coordinates.length, 3);
    routing.fit(); assert.equal(fits.length, 2);
    await routing.show(destination, origin); assert.equal(requests, 1);
    routing.clear(); assert.equal(routing.hasRoute(), false);
    assert.equal(states.at(-1).state.cleared, true);
    assert.equal([...layers].filter(layer => layer.geometry).length, 0);
});

test('a failed replacement keeps the previous route visible with an explicit error', async () => {
    let fail = false;
    const { routing, states } = setup(async () => { if (fail) throw Error('offline'); return response(); });
    await routing.show(destination, origin); fail = true;
    await routing.show({ lat: 12, lng: 123 }, origin);
    assert.equal(routing.hasRoute(), true);
    assert.equal(states.at(-1).error, true);
    assert.match(states.at(-1).message, /previous route/);
});

test('an older request cannot overwrite the newest route or restore a cleared route', async () => {
    const pending = [];
    const { routing, states } = setup(() => new Promise(resolve => pending.push(resolve)));
    const first = routing.show(destination, origin);
    const second = routing.show({ lat: 12, lng: 123 }, origin);
    pending[1](response(2400)); await second;
    pending[0](response(1200)); await first;
    assert.equal(states.at(-1).state.route.distance, 2400);
    const third = routing.show({ lat: 13, lng: 123 }, origin);
    routing.clear(); pending[2](response()); await third;
    assert.equal(routing.hasRoute(), false);
    assert.equal(states.at(-1).state.cleared, true);
});

test('Street/Satellite buttons expose selection and replace only the base layer', () => {
    const { api, map, layers } = setup();
    const route = {}; layers.add(route);
    api.addLayers(map);
    const [street, satellite] = map.layerButtons.children;
    assert.equal(street.attrs['aria-pressed'], 'true');
    satellite.handlers.click();
    assert.equal(satellite.attrs['aria-pressed'], 'true');
    assert.equal(street.attrs['aria-pressed'], 'false');
    assert.equal(layers.size, 2); assert.equal(layers.has(route), true);
});

test('route controls are disabled without a route; status spinner stops on completion', async () => {
    const { api, routing, map, element } = setup(async () => response());
    const container = element();
    const refresh = api.toolbar(map, routing, container, () => {});
    assert.equal(container.children[1].disabled, true);
    await routing.show(destination, origin); refresh();
    assert.equal(container.children[1].disabled, false);
    container.children[2].handlers.click(); refresh();
    assert.equal(container.children[1].disabled, true);
    const status = element(); const render = api.statusView(status);
    render('Loading', false, { loading: true }); assert.equal(status.attrs['aria-busy'], 'true');
    render('Ready'); assert.equal(status.attrs['aria-busy'], 'false'); assert.equal(status.children.length, 1);
});
