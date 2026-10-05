const test = require('node:test');
const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const relaySource = fs.readFileSync(path.resolve(__dirname, '../../scripts/google-mail-relay/Code.gs'), 'utf8');
const fixedNow = Date.UTC(2026, 9, 5, 12);
const relaySecret = 'test-only-relay-secret-with-at-least-32-characters';

function createRelay(overrides = {}) {
    const state = {
        now: fixedNow, secret: relaySecret, quota: 100, lockAvailable: true,
        sends: [], cache: new Map(), releases: 0, lockAttempts: 0, ...overrides,
    };
    let locked = false;
    class RelayDate extends Date {
        static now() { return state.now; }
    }
    const asBytes = value => Buffer.isBuffer(value) ? value
        : Array.isArray(value) ? Buffer.from(value.map(byte => byte & 255))
            : Buffer.from(value, 'utf8');
    const signedBytes = buffer => Array.from(buffer, byte => byte > 127 ? byte - 256 : byte);
    const context = vm.createContext({
        Date: RelayDate,
        PropertiesService: {
            getScriptProperties: () => ({ getProperty: name => name === 'RELAY_SECRET' ? state.secret : null }),
        },
        Utilities: {
            computeHmacSha256Signature: (input, secret) => signedBytes(
                crypto.createHmac('sha256', secret).update(input, 'utf8').digest(),
            ),
            base64Decode: value => {
                if (typeof value !== 'string' || !/^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$/.test(value)) {
                    throw new Error('Invalid base64');
                }
                return signedBytes(Buffer.from(value, 'base64'));
            },
            newBlob: (value, type, name) => {
                const bytes = asBytes(value);
                return { getDataAsString: () => bytes.toString('utf8'), getBytes: () => signedBytes(bytes), type, name };
            },
        },
        CacheService: {
            getScriptCache: () => ({
                get: key => {
                    const entry = state.cache.get(key);
                    return entry && entry.expires > state.now ? entry.value : null;
                },
                put: (key, value, ttl) => state.cache.set(key, { value, expires: state.now + ttl * 1000 }),
            }),
        },
        LockService: {
            getScriptLock: () => ({
                tryLock: () => {
                    state.lockAttempts++;
                    locked = state.lockAvailable;
                    return locked;
                },
                hasLock: () => locked,
                releaseLock: () => { locked = false; state.releases++; },
            }),
        },
        MailApp: {
            getRemainingDailyQuota: () => state.quota,
            sendEmail: options => {
                if (state.deliveryError) throw new Error(state.deliveryError);
                state.sends.push(options);
            },
        },
        ContentService: {
            MimeType: { JSON: 'application/json' },
            createTextOutput: text => ({ text, setMimeType(type) { this.mimeType = type; return this; } }),
        },
    });
    vm.runInContext(relaySource, context, { filename: 'Code.gs' });
    return {
        state,
        sign(message, fields = {}) {
            const request = {
                timestamp: Math.floor(state.now / 1000),
                nonce: crypto.randomBytes(16).toString('hex'),
                payload: Buffer.from(JSON.stringify(message), 'utf8').toString('base64'),
                ...fields,
            };
            request.signature = crypto.createHmac('sha256', relaySecret)
                .update(`${request.timestamp}\n${request.nonce}\n${request.payload}`, 'utf8').digest('hex');
            return request;
        },
        post(request) {
            const response = context.doPost({ postData: { contents: JSON.stringify(request) } });
            assert.equal(response.mimeType, 'application/json');
            return JSON.parse(response.text);
        },
        rawPost(event) {
            const response = context.doPost(event);
            return JSON.parse(response.text);
        },
        get() { return JSON.parse(context.doGet().text); },
    };
}

const message = changes => ({
    to: ['recipient@example.test'], subject: 'Verify your CBIS account',
    text: 'Open https://cbis.example.test/verify?token=test-only-token',
    html: '<p>Confirm your email 🩸</p><a href="https://cbis.example.test/verify?token=test-only-token">Verify email</a>',
    ...changes,
});

test('a valid signed request delivers the UTF-8 text and HTML without altering its verification link', () => {
    const relay = createRelay();
    const email = message({ name: 'CBIS Notifications', replyTo: 'support@example.test' });
    assert.deepEqual(relay.post(relay.sign(email)), { ok: true });
    assert.equal(relay.state.sends.length, 1);
    const sent = relay.state.sends[0];
    assert.equal(sent.to, email.to[0]);
    assert.equal(sent.subject, email.subject);
    assert.equal(sent.body, email.text);
    assert.equal(sent.htmlBody, email.html);
    assert.equal(sent.name, 'CBIS Notifications');
    assert.equal(sent.replyTo, 'support@example.test');
    assert.equal(relay.state.releases, 1);
});

test('a missing or short shared secret cannot send a message', () => {
    for (const secret of [null, '', 'too-short']) {
        const relay = createRelay({ secret });
        assert.deepEqual(relay.post(relay.sign(message())), { ok: false, error: 'not_configured' });
        assert.equal(relay.state.sends.length, 0);
        assert.equal(relay.state.lockAttempts, 0);
    }
});

test('a forged signature or a modified signed payload cannot send', () => {
    for (const tamper of [
        request => { request.signature = '0'.repeat(64); },
        request => { request.payload = Buffer.from(JSON.stringify(message({ to: ['attacker@example.test'] }))).toString('base64'); },
        request => { request.nonce = crypto.randomBytes(16).toString('hex'); },
    ]) {
        const relay = createRelay();
        const request = relay.sign(message());
        tamper(request);
        assert.deepEqual(relay.post(request), { ok: false, error: 'unauthorized' });
        assert.equal(relay.state.sends.length, 0);
        assert.equal(relay.state.lockAttempts, 0);
    }
});

test('requests outside the five-minute clock window are rejected even with valid signatures', () => {
    for (const offset of [-301, 301]) {
        const relay = createRelay();
        const timestamp = Math.floor(fixedNow / 1000) + offset;
        assert.deepEqual(relay.post(relay.sign(message(), { timestamp })), { ok: false, error: 'unauthorized' });
        assert.equal(relay.state.sends.length, 0);
    }
});

test('invalid timestamp, nonce and signature shapes are rejected before acquiring a delivery lock', () => {
    for (const fields of [
        { timestamp: String(Math.floor(fixedNow / 1000)) },
        { timestamp: Math.floor(fixedNow / 1000) + 0.5 },
        { nonce: 'not-a-nonce' }, { nonce: 'A'.repeat(32) },
    ]) {
        const relay = createRelay();
        assert.deepEqual(relay.post(relay.sign(message(), fields)), { ok: false, error: 'unauthorized' });
        assert.equal(relay.state.sends.length, 0);
        assert.equal(relay.state.lockAttempts, 0);
    }
    const relay = createRelay();
    const request = relay.sign(message());
    request.signature = 'short';
    assert.deepEqual(relay.post(request), { ok: false, error: 'unauthorized' });
    assert.equal(relay.state.sends.length, 0);
});

test('replaying a successful request sends only once and releases the lock on both paths', () => {
    const relay = createRelay();
    const request = relay.sign(message());
    assert.deepEqual(relay.post(request), { ok: true });
    assert.deepEqual(relay.post(request), { ok: true, duplicate: true });
    assert.equal(relay.state.sends.length, 1);
    assert.equal(relay.state.releases, 2);
    assert.equal(relay.state.cache.get(`sent:${request.nonce}`).expires, fixedNow + 600000);
});

test('a busy delivery lock sends nothing and does not release a lock it never acquired', () => {
    const relay = createRelay({ lockAvailable: false });
    assert.deepEqual(relay.post(relay.sign(message())), { ok: false, error: 'busy' });
    assert.equal(relay.state.sends.length, 0);
    assert.equal(relay.state.releases, 0);
});

test('quota covers all To, CC and BCC recipients and exhausted requests can be retried', () => {
    const relay = createRelay({ quota: 2 });
    const request = relay.sign(message({ cc: ['copy@example.test'], bcc: ['hidden@example.test'] }));
    assert.deepEqual(relay.post(request), { ok: false, error: 'quota_exceeded' });
    assert.equal(relay.state.sends.length, 0);
    assert.equal(relay.state.cache.size, 0);
    assert.equal(relay.state.releases, 1);
    relay.state.quota = 3;
    assert.deepEqual(relay.post(request), { ok: true });
    assert.equal(relay.state.sends.length, 1);
    assert.equal(relay.state.releases, 2);
});

test('invalid addresses in To, CC, BCC or Reply-To never reach MailApp', () => {
    for (const badAddress of ['missing-at-sign.test', 'a@example.test\r\nBcc: x@example.test', 'a@example.test,b@example.test']) {
        for (const email of [
            message({ to: [badAddress] }), message({ cc: [badAddress] }),
            message({ bcc: [badAddress] }), message({ replyTo: badAddress }),
        ]) {
            const relay = createRelay();
            assert.equal(relay.post(relay.sign(email)).ok, false);
            assert.equal(relay.state.sends.length, 0);
            assert.equal(relay.state.lockAttempts, 0);
        }
    }
});

test('recipient counts, subject injection and combined UTF-8 body size are constrained', () => {
    for (const email of [
        message({ to: [] }),
        message({ to: Array.from({ length: 51 }, (_, index) => `recipient${index}@example.test`) }),
        message({ subject: 'Verify\r\nBcc: attacker@example.test' }),
        message({ subject: 'x'.repeat(999) }),
        message({ text: '🩸'.repeat(51201), html: '' }),
        message({ text: 'x'.repeat(102401), html: 'x'.repeat(102400) }),
        message({ text: null }), message({ html: null }),
    ]) {
        const relay = createRelay();
        assert.deepEqual(relay.post(relay.sign(email)), { ok: false, error: 'invalid_message' });
        assert.equal(relay.state.sends.length, 0);
    }
    const relay = createRelay();
    assert.deepEqual(relay.post(relay.sign(message({ text: '🩸'.repeat(51200), html: '' }))), { ok: true });
});

test('BCC recipients remain separate from visible recipient fields and message contents', () => {
    const relay = createRelay();
    const hidden = 'private-recipient@example.test';
    const email = message({ cc: ['copy@example.test'], bcc: [hidden] });
    assert.deepEqual(relay.post(relay.sign(email)), { ok: true });
    const sent = relay.state.sends[0];
    assert.equal(sent.bcc, hidden);
    for (const field of ['to', 'cc', 'subject', 'body', 'htmlBody']) {
        assert.ok(!sent[field].includes(hidden), `${field} does not expose BCC recipients`);
    }
});

test('HTML-only email receives a text fallback and text-only email has no HTML field', () => {
    const relay = createRelay();
    assert.deepEqual(relay.post(relay.sign(message({ text: '' }))), { ok: true });
    assert.equal(relay.state.sends[0].body, 'View the HTML version of this email.');
    assert.deepEqual(relay.post(relay.sign(message({ html: '' }))), { ok: true });
    assert.equal(relay.state.sends[1].htmlBody, undefined);
});

test('signed attachments preserve their bytes while malformed attachment metadata is blocked', () => {
    const relay = createRelay();
    const contents = Buffer.from('CBIS attachment 🩸', 'utf8');
    const attachment = { name: 'receipt.txt', type: 'text/plain', base64: contents.toString('base64') };
    assert.deepEqual(relay.post(relay.sign(message({ attachments: [attachment] }))), { ok: true });
    const sentAttachment = relay.state.sends[0].attachments[0];
    assert.equal(sentAttachment.name, attachment.name);
    assert.equal(sentAttachment.type, attachment.type);
    assert.deepEqual(Buffer.from(sentAttachment.getBytes().map(byte => byte & 255)), contents);
    for (const attachments of [
        [Object.assign({}, attachment, { type: 'text/plain\r\nInjected: value' })],
        [Object.assign({}, attachment, { name: 'x'.repeat(256) })],
        [Object.assign({}, attachment, { base64: '@invalid!' })],
        Array(11).fill(attachment), 'not-an-array',
    ]) {
        const rejected = createRelay();
        assert.equal(rejected.post(rejected.sign(message({ attachments }))).ok, false);
        assert.equal(rejected.state.sends.length, 0);
    }
});

test('delivery exceptions release the lock, permit a retry and expose only a generic error', () => {
    const email = message();
    const relay = createRelay({ deliveryError: `Failure for ${email.to[0]} using ${relaySecret}: ${email.html}` });
    const request = relay.sign(email);
    const response = relay.post(request);
    assert.deepEqual(response, { ok: false, error: 'delivery_failed' });
    for (const sensitive of [relaySecret, email.to[0], email.html, request.signature, request.payload]) {
        assert.ok(!JSON.stringify(response).includes(sensitive));
    }
    assert.equal(relay.state.releases, 1);
    assert.equal(relay.state.cache.size, 0);
    relay.state.deliveryError = null;
    assert.deepEqual(relay.post(request), { ok: true });
    assert.equal(relay.state.sends.length, 1);
    assert.equal(relay.state.releases, 2);
});

test('malformed, missing and oversized request bodies do not send or reveal internal details', () => {
    for (const event of [undefined, {}, { postData: {} },
        { postData: { contents: 'not-json' } },
        { postData: { contents: 'null' } },
        { postData: { contents: 'x'.repeat(4 * 1024 * 1024 + 1) } },
    ]) {
        const relay = createRelay();
        const response = relay.rawPost(event);
        assert.equal(response.ok, false);
        assert.equal(relay.state.sends.length, 0);
        assert.equal(relay.state.lockAttempts, 0);
        assert.ok(!JSON.stringify(response).includes(relaySecret));
    }
});

test('the public health response does not send mail or expose configuration', () => {
    const relay = createRelay();
    assert.deepEqual(relay.get(), { ok: true, service: 'CBIS email relay' });
    assert.equal(relay.state.sends.length, 0);
    assert.equal(relay.state.lockAttempts, 0);
    assert.ok(!JSON.stringify(relay.get()).includes(relaySecret));
});
