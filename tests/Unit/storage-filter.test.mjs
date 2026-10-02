import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

class Option {
    constructor(value, text) {
        this.value = value;
        this.text = text;
    }

    get outerHTML() {
        return `<option value="${this.value}">${this.text}</option>`;
    }

    cloneNode(deep) {
        assert.equal(deep, true);
        return new Option(this.value, this.text);
    }
}

class Select {
    constructor(options, value = '') {
        this.options = options;
        this.value = value;
        this.form = { dataset: {} };
        this.handlers = new Map();
        this.replacements = 0;
    }

    get innerHTML() {
        return this.options.map((option) => option.outerHTML).join('');
    }

    set value(value) {
        this.selectedValue = this.options.some((option) => option.value === value) ? value : '';
    }

    get value() {
        return this.selectedValue;
    }

    addEventListener(type, handler) {
        this.handlers.set(type, handler);
    }

    replaceChildren(...options) {
        this.options = options;
        this.selectedValue = options[0]?.value || '';
        this.replacements++;
    }

    change() {
        this.handlers.get('change')?.();
    }
}

const allDates = () => new Option('', 'All expiry dates');
const expiry = (value = '2026-10-04', units = 8) => new Option(value, `${value} · ${units} units`);

function setup({ selected = '', query = selected, focused = false, pending = false, currentMissing = false } = {}) {
    const current = new Select([allDates(), expiry()], selected);
    current.form.dataset.autoSubmitting = pending ? 'true' : 'false';
    let automaticSubmissions = 0;
    current.addEventListener('change', () => automaticSubmissions++);
    const handlers = new Map();
    const document = {
        activeElement: focused ? current : null,
        getElementById: (id) => id === 'storage-expiration-date' && !currentMissing ? current : null,
        addEventListener: (type, handler) => handlers.set(type, handler),
    };
    const url = new URL('http://localhost/blood-inventory/storage?blood_type=B%2B&component=fresh_frozen_plasma');
    if (query) url.searchParams.set('expiration_date', query);
    vm.runInNewContext(readFileSync(new URL('../../public/js/cbis-storage-filter.js', import.meta.url), 'utf8'), {
        document, window: { location: { href: url.href } }, URL,
    });
    return {
        current,
        document,
        automaticSubmissions: () => automaticSubmissions,
        refresh(next) {
            handlers.get('cbis:live-document')({ detail: {
                document: { getElementById: (id) => id === 'storage-expiration-date' ? next : null },
            } });
        },
        dispatch: (event) => handlers.get('cbis:live-document')(event),
    };
}

test('new expiry options and quantities update while preserving the select, selected date, and automatic-filter handler', () => {
    const page = setup({ selected: '2026-10-04' });
    const next = new Select([allDates(), expiry('2026-10-04', 6), expiry('2026-10-12', 5)], '2026-10-04');
    page.refresh(next);
    assert.equal(page.current.replacements, 1);
    assert.equal(page.document.getElementById('storage-expiration-date'), page.current);
    assert.equal(page.current.value, '2026-10-04');
    assert.equal(page.current.options.length, 3);
    assert.equal(page.current.options[1].text, '2026-10-04 · 6 units');
    assert.equal(page.current.options[2].value, '2026-10-12');
    assert.notEqual(page.current.options[2], next.options[2]);
    page.current.value = '2026-10-12';
    page.current.change();
    assert.equal(page.automaticSubmissions(), 1);
});

test('All expiry dates remains selected when an unfiltered page gains a new date', () => {
    const page = setup();
    page.refresh(new Select([allDates(), expiry(), expiry('2026-10-12', 5)]));
    assert.equal(page.current.value, '');
    assert.equal(page.current.options.length, 3);
    assert.equal(page.current.replacements, 1);
});

test('refresh preserves a selected date with no remaining stock using the server fallback option', () => {
    const page = setup({ selected: '2026-10-04' });
    page.refresh(new Select([allDates(), expiry('2026-10-12', 5), new Option('2026-10-04', '2026-10-04 · no stored units')], '2026-10-04'));
    assert.equal(page.current.value, '2026-10-04');
    assert.equal(page.current.options.at(-1).text, '2026-10-04 · no stored units');
    assert.equal(page.current.replacements, 1);
});

test('refresh leaves a focused selector untouched', () => {
    const page = setup({ focused: true });
    page.refresh(new Select([allDates(), expiry(), expiry('2026-10-12', 5)]));
    assert.equal(page.current.options.length, 2);
    assert.equal(page.current.replacements, 0);
});

test('refresh leaves an unsaved date choice and a pending filter submission untouched', () => {
    for (const settings of [{ selected: '2026-10-04', query: '' }, { pending: true }]) {
        const page = setup(settings);
        const previousValue = page.current.value;
        page.refresh(new Select([allDates(), expiry(), expiry('2026-10-12', 5)]));
        assert.equal(page.current.value, previousValue);
        assert.equal(page.current.options.length, 2);
        assert.equal(page.current.replacements, 0);
    }
});

test('unchanged options are not replaced', () => {
    const page = setup({ selected: '2026-10-04' });
    const previousOption = page.current.options[1];
    page.refresh(new Select([allDates(), expiry()], '2026-10-04'));
    assert.equal(page.current.options[1], previousOption);
    assert.equal(page.current.value, '2026-10-04');
    assert.equal(page.current.replacements, 0);
});

test('pages without the storage selector and events without an incoming selector are ignored', () => {
    const missingCurrent = setup({ currentMissing: true });
    missingCurrent.refresh(new Select([allDates(), expiry()]));
    assert.equal(missingCurrent.current.replacements, 0);
    const page = setup();
    page.refresh(null);
    page.dispatch({});
    assert.equal(page.current.replacements, 0);
});
