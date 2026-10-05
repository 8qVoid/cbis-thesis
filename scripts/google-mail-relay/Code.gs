/** HTTPS mail relay for the Render testing copy; no inbox access. */
function doGet() {
  return relayJson_({ok: true, service: 'CBIS email relay'});
}

function doPost(e) {
  let lock;
  try {
    const raw = e && e.postData && e.postData.contents;
    if (typeof raw !== 'string' || raw.length > 4 * 1024 * 1024) {
      return relayJson_({ok: false, error: 'invalid_request'});
    }
    const request = JSON.parse(raw);
    const secret = PropertiesService.getScriptProperties().getProperty('RELAY_SECRET');
    if (!secret || secret.length < 32) {
      return relayJson_({ok: false, error: 'not_configured'});
    }
    if (!Number.isInteger(request.timestamp) ||
        Math.abs(Math.floor(Date.now() / 1000) - request.timestamp) > 300 ||
        typeof request.nonce !== 'string' || !/^[a-f0-9]{32}$/.test(request.nonce) ||
        typeof request.payload !== 'string' ||
        typeof request.signature !== 'string' || !/^[a-f0-9]{64}$/.test(request.signature)) {
      return relayJson_({ok: false, error: 'unauthorized'});
    }
    const input = request.timestamp + '\n' + request.nonce + '\n' + request.payload;
    const expected = Utilities.computeHmacSha256Signature(input, secret)
      .map(b => ('0' + ((b + 256) % 256).toString(16)).slice(-2)).join('');
    let mismatch = 0;
    for (let i = 0; i < expected.length; i++) {
      mismatch |= expected.charCodeAt(i) ^ request.signature.charCodeAt(i);
    }
    if (mismatch !== 0) return relayJson_({ok: false, error: 'unauthorized'});

    const message = JSON.parse(Utilities.newBlob(Utilities.base64Decode(request.payload)).getDataAsString());
    const to = relayAddresses_(message.to);
    const cc = relayAddresses_(message.cc || []);
    const bcc = relayAddresses_(message.bcc || []);
    const recipients = to.length + cc.length + bcc.length;
    if (recipients < 1 || recipients > 50 ||
        typeof message.subject !== 'string' || message.subject.length > 998 || /[\r\n]/.test(message.subject) ||
        typeof message.text !== 'string' || typeof message.html !== 'string' ||
        Utilities.newBlob(message.text + message.html).getBytes().length > 200 * 1024) {
      return relayJson_({ok: false, error: 'invalid_message'});
    }
    const options = {to: to.join(','), subject: message.subject, body: message.text || 'View the HTML version of this email.'};
    if (cc.length) options.cc = cc.join(',');
    if (bcc.length) options.bcc = bcc.join(',');
    if (message.html) options.htmlBody = message.html;
    if (typeof message.name === 'string' && message.name.length <= 100 && !/[\r\n]/.test(message.name)) {
      options.name = message.name;
    }
    if (message.replyTo) options.replyTo = relayAddresses_([message.replyTo])[0];
    if (message.attachments) {
      if (!Array.isArray(message.attachments) || message.attachments.length > 10) {
        return relayJson_({ok: false, error: 'invalid_message'});
      }
      options.attachments = message.attachments.map(attachment => {
        if (typeof attachment.name !== 'string' || attachment.name.length > 255 ||
            typeof attachment.type !== 'string' || !/^[\w.+-]+\/[\w.+-]+$/.test(attachment.type) ||
            typeof attachment.base64 !== 'string') throw new Error('invalid_attachment');
        return Utilities.newBlob(Utilities.base64Decode(attachment.base64), attachment.type, attachment.name);
      });
    }
    lock = LockService.getScriptLock();
    if (!lock.tryLock(5000)) return relayJson_({ok: false, error: 'busy'});
    const cache = CacheService.getScriptCache();
    const replayKey = 'sent:' + request.nonce;
    if (cache.get(replayKey)) return relayJson_({ok: true, duplicate: true});
    if (MailApp.getRemainingDailyQuota() < recipients) {
      return relayJson_({ok: false, error: 'quota_exceeded'});
    }
    MailApp.sendEmail(options);
    cache.put(replayKey, '1', 600);
    return relayJson_({ok: true});
  } catch (error) {
    // Do not log or echo email contents, recipients, signatures, or secrets.
    return relayJson_({ok: false, error: 'delivery_failed'});
  } finally {
    if (lock && lock.hasLock()) lock.releaseLock();
  }
}

function relayAddresses_(addresses) {
  if (!Array.isArray(addresses) || addresses.some(address =>
      typeof address !== 'string' || address.length > 254 || !/^[^\s@,;<>]+@[^\s@,;<>]+\.[^\s@,;<>]+$/.test(address))) {
    throw new Error('invalid_recipient');
  }
  return addresses;
}

function relayJson_(value) {
  return ContentService.createTextOutput(JSON.stringify(value)).setMimeType(ContentService.MimeType.JSON);
}
