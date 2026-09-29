import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function runPoll({ focused = false } = {}) {
    let tick;
    let replacement = null;
    let interval = null;
    const current = {
        outerHTML: '<div data-live-region="inventory-summary">Old</div>',
        getAttribute: () => 'inventory-summary',
        contains: () => focused,
        querySelector: () => null,
        replaceWith(node) { replacement = node; },
    };
    const next = {
        outerHTML: '<div data-live-region="inventory-summary">New</div>',
        getAttribute: () => 'inventory-summary',
    };
    const nextDocument = {
        querySelector: () => null,
        querySelectorAll: () => [next],
    };
    const document = {
        visibilityState: 'visible',
        activeElement: {},
        querySelector: () => null,
        querySelectorAll: () => [current],
        addEventListener: () => {},
        dispatchEvent: () => {},
    };
    const context = {
        document,
        window: {
            location: { href: 'http://localhost/dashboard', pathname: '/dashboard' },
            setInterval(callback, ms) { tick = callback; interval = ms; },
        },
        fetch: async () => ({
            ok: true,
            redirected: false,
            headers: { get: () => 'text/html' },
            text: async () => '<html></html>',
        }),
        DOMParser: class { parseFromString() { return nextDocument; } },
        CustomEvent: class { constructor(type, detail) { this.type = type; this.detail = detail; } },
    };
    vm.runInNewContext(readFileSync(new URL('../../public/js/cbis-live.js', import.meta.url), 'utf8'), context);
    return { tick, interval, replacement: () => replacement, next };
}

test('a visible data section updates automatically without reloading the page', async () => {
    const poll = runPoll();
    assert.equal(poll.interval, 15000);
    await poll.tick();
    assert.equal(poll.replacement(), poll.next);
});

test('a focused section is not replaced while someone is using it', async () => {
    const poll = runPoll({ focused: true });
    await poll.tick();
    assert.equal(poll.replacement(), null);
});
