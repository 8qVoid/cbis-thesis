# Email for the Render testing copy

Render Free blocks Gmail SMTP ports. The optional `google_script` mail transport
sends the same Laravel emails over HTTPS through an owner-authorized Google Apps
Script relay. Local Laragon continues to use `MAIL_MAILER=smtp`; do not change the
local `.env` for this setup. No database, login, verification, or password-reset
rules need to change.

## Google relay

1. Sign in to the CBIS notification Gmail account and create an Apps Script
   project. Copy `scripts/google-mail-relay/Code.gs` into `Code.gs`.
2. Enable the manifest in Project Settings and copy
   `scripts/google-mail-relay/appsscript.json` into it. The only OAuth permission
   is `script.send_mail`; the relay does not read the inbox.
3. Generate a random secret of at least 32 characters. Store it as `RELAY_SECRET`
   in Google Script Properties. Never place the secret in source, a URL, or Git.
4. Deploy a Web app that executes as the notification account, with access set to
   Anyone. The script authenticates every mail POST with a secret HMAC signature;
   the health GET endpoint cannot send mail. The account owner must authorize the
   send-mail permission. If Google presents a security warning, the owner must
   review and complete that step personally.
5. Copy the deployment's HTTPS `/exec` URL.

## Render environment only

Configure these variables in Render, using the exact same secret as Google:

```dotenv
MAIL_MAILER=google_script
GOOGLE_MAIL_RELAY_URL=https://script.google.com/macros/s/YOUR_DEPLOYMENT_ID/exec
GOOGLE_MAIL_RELAY_SECRET=YOUR_RANDOM_SECRET
MAIL_FROM_ADDRESS=cbis.notifications.ph@gmail.com
MAIL_FROM_NAME="CBIS Notifications"
QUEUE_CONNECTION=sync
```

Keep `APP_URL` set to the HTTPS Render URL so verification and password-reset
links point to the testing copy. The Google script owner's Gmail address is the
actual sender; changing `MAIL_FROM_ADDRESS` cannot impersonate a different Gmail
account. Select `google_script` only after deploying and authorizing the relay.
In production, the application trusts Render's reverse-proxy headers so signed
verification URLs are validated using their public HTTPS scheme.

The Render Docker service starts Apache without a queue worker. Use `sync` on
that testing service so queued notifications, including low-stock alerts, send
when triggered. Keep the local queue configuration unchanged. A production
deployment can instead use a separately running queue worker.

The transport follows Google's HTTPS content redirect, rejects unacknowledged
delivery, and never silently marks a logged email as delivered. Signed requests
expire after five minutes. A script lock and short-lived nonce cache prevent
ordinary duplicate delivery; Google CacheService is best effort, so this is not
a durable exactly-once mail queue.

## Limits and verification

Consumer Gmail Apps Script accounts can send to 100 recipients per day. Quota
counts all To, Cc, and Bcc recipients and resets according to Google's quota
window. The adapter limits messages to 50 recipients, 200 KB of combined text
and HTML, 10 ordinary attachments, and a 4 MB request. Inline CID attachments are
not supported. These limits are suitable for a small exploration/demo site.

After deploying, verify a real activation email reaches an authorized inbox and
its signed link activates the Render account. Also verify a password-reset email
and an account notification. Mail tests with faked HTTP prove application
behavior, not actual Gmail delivery.

```bash
php artisan test --compact --filter=GoogleScriptTransportTest
node tests/JavaScript/google-mail-relay.test.cjs
```

References: [Render Free limits](https://render.com/docs/free),
[Google web app deployment](https://developers.google.com/apps-script/guides/web),
[MailApp](https://developers.google.com/apps-script/reference/mail/mail-app),
[Google quotas](https://developers.google.com/apps-script/guides/services/quotas),
[ContentService redirects](https://developers.google.com/apps-script/guides/content#redirects).
