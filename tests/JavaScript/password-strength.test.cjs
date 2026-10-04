const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const projectRoot = path.resolve(__dirname, '../..');
const source = file => fs.readFileSync(path.join(projectRoot, file), 'utf8');

function browserContext(document) {
    const context = vm.createContext({ document });
    context.window = context;
    context.self = context;
    vm.runInContext(source('public/js/vendor/zxcvbn.js'), context, { filename: 'zxcvbn.js' });
    vm.runInContext(source('public/js/password-estimator.js'), context, { filename: 'password-estimator.js' });
    return context;
}

const engine = browserContext();
const estimate = (password, inputs = []) => engine.CBISPasswordEstimator.estimatePasswordStrength(password, inputs);
const collect = values => engine.CBISPasswordEstimator.collectPasswordUserInputs(values);

test('empty passwords keep the placeholder without implying a strength rating', () => {
    const result = estimate('');
    assert.equal(result.level, 'empty');
    assert.equal(result.label, 'Enter a password');
    assert.equal(result.width, 0);
    assert.equal(result.score, null);
});

test('real guessing estimates map to all four visible strength levels', () => {
    for (const [password, score, level, label, width] of [
        ['password', 0, 'weak', 'Weak', 25],
        ['Password123!', 1, 'weak', 'Weak', 25],
        ['xQ7!vM2@', 2, 'medium', 'Medium', 50],
        ['J4r%9tK&2m', 3, 'strong', 'Strong', 75],
        ['vJ8!qM4#tR2@', 4, 'very-strong', 'Very Strong', 100],
    ]) {
        const result = estimate(password);
        assert.equal(result.score, score, password);
        assert.equal(result.level, level, password);
        assert.equal(result.label, label, password);
        assert.equal(result.width, width, password);
        assert.ok(Number.isFinite(result.estimatedGuessBits), password);
    }
});

test('common passwords stay weak despite upper/lowercase, numbers, symbols and valid length', () => {
    const result = estimate('Password123!');
    assert.equal(result.level, 'weak');
    assert.match(result.feedback, /common|similar/i);
});

test('repeats, sequences, keyboard paths and dates are detected as predictable', () => {
    for (const [password, expectedPattern, feedbackPattern] of [
        ['Aaaaaa1!Aaaaaa1!Aaaaaa1!', 'repeat', /repeat/i],
        ['abcdefghijklmno', 'sequence', /sequence/i],
        ['zxcvbnm,./', 'spatial', /keyboard|rows/i],
        ['19991022', 'date', /date|year/i],
    ]) {
        assert.ok(engine.zxcvbn(password).sequence.some(match => match.pattern === expectedPattern), password);
        const result = estimate(password);
        assert.equal(result.level, 'weak', password);
        assert.match(result.feedback, feedbackPattern, password);
    }
});

test('long predictable strings rate below shorter unrelated random passwords', () => {
    const predictable = 'Aaaaaa1!Aaaaaa1!Aaaaaa1!';
    const unrelated = 'J4r%9tK&2m';
    assert.ok(predictable.length > unrelated.length);
    assert.ok(estimate(predictable).score < estimate(unrelated).score);
    assert.ok(estimate(predictable).estimatedGuessBits < estimate(unrelated).estimatedGuessBits);
});

test('personal input collection splits names and emails, expands birth dates and ignores blanks', () => {
    const values = [' Cassiopeia Velazquez ', 'cassiopeia.velazquez@example.test', '2001-10-22', '09170000104', '', null];
    const original = values.slice();
    const inputs = Array.from(collect(values));
    for (const token of ['cassiopeia', 'velazquez', 'example', '2001', '20011022', '22102001', '10222001', '09170000104']) {
        assert.ok(inputs.includes(token), token);
    }
    assert.equal(inputs.length, new Set(inputs).size);
    assert.ok(!inputs.includes(''));
    assert.deepEqual(values, original);
});

test('name, email and date-derived words lower guesses and provide account-specific feedback', () => {
    for (const [password, values] of [
        ['Cassiopeia!2001', ['Cassiopeia']],
        ['Cassiopeia!2001', ['cassiopeia@example.test']],
        ['20011022!Xy', ['2001-10-22']],
    ]) {
        const generic = estimate(password);
        const personal = estimate(password, values);
        assert.ok(personal.estimatedGuessBits < generic.estimatedGuessBits, password);
        assert.match(personal.feedback, /associated with your account/i, password);
    }
    assert.equal(estimate('Cassiopeia!2001').level, 'strong');
    assert.equal(estimate('Cassiopeia!2001', ['Cassiopeia']).level, 'medium');
});

test('Unicode profile words are normalized and included in dictionary matching', () => {
    const inputs = Array.from(collect([' ＭＡＲＫ ', 'Álvaro O’Connor', 'álvaro@example.test']));
    assert.ok(inputs.includes('mark'));
    assert.ok(inputs.includes('álvaro'));
    assert.ok(inputs.includes('connor'));
    assert.equal(inputs.filter(value => value === 'álvaro').length, 1);
});

test('an unavailable engine shows an explicit status rather than a misleading rating', () => {
    const context = vm.createContext({});
    vm.runInContext(source('public/js/password-estimator.js'), context);
    const result = context.CBISPasswordEstimator.estimatePasswordStrength('Password123!');
    assert.equal(result.level, 'unavailable');
    assert.equal(result.score, null);
    assert.equal(result.width, 0);
});

class Element {
    constructor(properties = {}) {
        Object.assign(this, {
            value: '', type: 'text', checked: false, textContent: '', hidden: false,
            dataset: {}, style: {}, attributes: {}, listeners: {}, customValidity: '',
        }, properties);
    }

    addEventListener(type, listener) { (this.listeners[type] ||= []).push(listener); }
    dispatch(type) { for (const listener of this.listeners[type] || []) listener({ target: this }); }
    setAttribute(name, value) { this.attributes[name] = String(value); }
    getAttribute(name) { return this.attributes[name] ?? null; }
    setCustomValidity(message) { this.customValidity = message; }
}

function registrationUI() {
    const elements = {};
    const add = (id, properties) => elements[id] = new Element(properties);
    for (const id of [
        'registrationFirstName', 'registrationMiddleName', 'registrationLastName',
        'registrationEmail', 'registrationMobile', 'birthDate',
    ]) add(id);
    const password = add('registrationPassword', { type: 'password' });
    const confirmation = add('registrationPasswordConfirmation', { type: 'password' });
    const show = add('showRegistrationPassword', { type: 'checkbox' });
    const match = add('passwordMatch');
    const feedback = add('passwordStrengthFeedback');
    const strength = add('passwordStrength');
    const label = new Element();
    const bar = new Element();
    const meter = new Element();
    const address = [new Element(), new Element()];
    strength.querySelector = selector => ({
        strong: label,
        span: bar,
        '.cbis-password-strength-track': meter,
    })[selector] || null;
    const document = {
        getElementById: id => elements[id] || null,
        querySelectorAll: selector => selector === '.js-negros-address select' ? address : [],
    };
    const context = browserContext(document);
    vm.runInContext(source('public/js/password-strength.js'), context, { filename: 'password-strength.js' });
    return {
        elements, password, confirmation, show, match, feedback, strength, label, bar, meter, address,
        input(id, value, event = 'input') {
            elements[id].value = value;
            elements[id].dispatch(event);
        },
    };
}

test('typing and clearing the password updates the real label, meter, ARIA state and feedback', () => {
    const ui = registrationUI();
    assert.equal(ui.label.textContent, 'Enter a password');
    assert.equal(ui.bar.style.width, '0%');
    assert.equal(ui.meter.getAttribute('aria-valuenow'), '0');
    for (const [password, label, level, score, width] of [
        ['Password123!', 'Weak', 'weak', '1', '25%'],
        ['xQ7!vM2@', 'Medium', 'medium', '2', '50%'],
        ['J4r%9tK&2m', 'Strong', 'strong', '3', '75%'],
        ['vJ8!qM4#tR2@', 'Very Strong', 'very-strong', '4', '100%'],
    ]) {
        ui.input('registrationPassword', password);
        assert.equal(ui.label.textContent, label);
        assert.equal(ui.strength.dataset.strength, level);
        assert.equal(ui.bar.style.width, width);
        assert.equal(ui.meter.getAttribute('aria-valuenow'), score);
        assert.match(ui.meter.getAttribute('aria-valuetext'), new RegExp(label));
    }
    assert.equal(ui.feedback.hidden, true, 'Strong passwords do not retain stale warning text');
    ui.input('registrationPassword', '');
    assert.equal(ui.label.textContent, 'Enter a password');
    assert.equal(ui.bar.style.width, '0%');
    assert.equal(ui.meter.getAttribute('aria-valuenow'), '0');
    assert.equal(ui.feedback.textContent, '');
    assert.equal(ui.feedback.hidden, true);
});

test('changing names or email recalculates a previously entered password immediately', () => {
    for (const [id, value, event] of [
        ['registrationFirstName', 'Cassiopeia', 'input'],
        ['registrationMiddleName', 'Cassiopeia', 'change'],
        ['registrationLastName', 'Cassiopeia', 'input'],
        ['registrationEmail', 'cassiopeia@example.test', 'input'],
    ]) {
        const ui = registrationUI();
        ui.input('registrationPassword', 'Cassiopeia!2001');
        assert.equal(ui.label.textContent, 'Strong', id);
        ui.input(id, value, event);
        assert.equal(ui.label.textContent, 'Medium', id);
        assert.match(ui.feedback.textContent, /associated with your account/i, id);
        ui.input(id, '', event);
        assert.equal(ui.label.textContent, 'Strong', id);
    }
});

test('changing birth date and address also recalculates personal-word guesses', () => {
    const birthdayUI = registrationUI();
    birthdayUI.input('registrationPassword', '20011022!Xy');
    assert.equal(birthdayUI.label.textContent, 'Medium');
    birthdayUI.input('birthDate', '2001-10-22', 'change');
    assert.equal(birthdayUI.label.textContent, 'Weak');
    assert.match(birthdayUI.feedback.textContent, /associated with your account/i);

    const addressUI = registrationUI();
    addressUI.input('registrationPassword', 'Cassiopeia!2001');
    assert.equal(addressUI.label.textContent, 'Strong');
    addressUI.address[0].value = 'Cassiopeia';
    addressUI.address[0].dispatch('change');
    assert.equal(addressUI.label.textContent, 'Medium');
});

test('password estimates do not add a strength gate and confirmation matching remains unchanged', () => {
    const ui = registrationUI();
    ui.input('registrationPassword', 'Password123!');
    assert.equal(ui.label.textContent, 'Weak');
    assert.equal(ui.password.customValidity, '');
    ui.input('registrationPasswordConfirmation', 'Password123!');
    assert.equal(ui.confirmation.customValidity, '');
    assert.equal(ui.match.textContent, 'Passwords match.');
    ui.input('registrationPasswordConfirmation', 'Different123!');
    assert.equal(ui.confirmation.customValidity, 'Passwords do not match.');
    assert.equal(ui.match.textContent, 'Passwords do not match.');
    ui.input('registrationPassword', 'Different123!');
    assert.equal(ui.confirmation.customValidity, '');
    assert.equal(ui.match.textContent, 'Passwords match.');
    ui.input('registrationPasswordConfirmation', '');
    assert.equal(ui.confirmation.customValidity, '');
    assert.equal(ui.match.textContent, '');
});

test('show passwords toggles both input types without changing values or strength', () => {
    const ui = registrationUI();
    ui.input('registrationPassword', 'Password123!');
    ui.input('registrationPasswordConfirmation', 'Password123!');
    ui.show.checked = true;
    ui.show.dispatch('change');
    assert.equal(ui.password.type, 'text');
    assert.equal(ui.confirmation.type, 'text');
    assert.equal(ui.password.value, 'Password123!');
    assert.equal(ui.confirmation.value, 'Password123!');
    assert.equal(ui.label.textContent, 'Weak');
    ui.show.checked = false;
    ui.show.dispatch('change');
    assert.equal(ui.password.type, 'password');
    assert.equal(ui.confirmation.type, 'password');
    assert.equal(ui.label.textContent, 'Weak');
});
