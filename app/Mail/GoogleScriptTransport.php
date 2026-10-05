<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use JsonException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class GoogleScriptTransport extends AbstractTransport
{
    private const MAX_RECIPIENTS = 50;

    private const MAX_BODY_BYTES = 200 * 1024;

    private const MAX_REQUEST_BYTES = 4 * 1024 * 1024;

    public function __construct(private readonly ?string $endpoint, private readonly ?string $secret)
    {
        parent::__construct();

        if (! is_string($endpoint) || ! preg_match('~\Ahttps://script\.google\.com/macros/s/[A-Za-z0-9_-]+/exec\z~', $endpoint)) {
            throw new TransportException('Google mail relay requires an HTTPS script.google.com web app deployment URL ending in /exec.');
        }

        if (! is_string($secret) || strlen(trim($secret)) < 32) {
            throw new TransportException('Google mail relay requires a secret of at least 32 characters.');
        }
    }

    public function __toString(): string
    {
        return 'google_script';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new TransportException('Google mail relay supports Symfony email messages only.');
        }

        $recipients = $this->recipients($message, $email);
        $text = $this->readBody($email->getTextBody());
        $html = $this->readBody($email->getHtmlBody());

        if (strlen($text) + strlen($html) > self::MAX_BODY_BYTES) {
            throw new TransportException('Google mail relay supports email bodies up to 200 KB.');
        }

        $replyTo = $email->getReplyTo();

        if (count($replyTo) > 1) {
            throw new TransportException('Google mail relay supports one reply-to address per email.');
        }

        $payload = [
            ...$recipients,
            'subject' => $email->getSubject() ?? '',
            'text' => $text,
            'html' => $html,
            'name' => ($email->getFrom()[0] ?? $message->getEnvelope()->getSender())->getName() ?: 'CBIS',
            'attachments' => $this->attachments($email),
        ];

        if ($replyTo !== []) {
            $payload['replyTo'] = $replyTo[0]->getAddress();
        }

        try {
            $encodedPayload = base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
            $timestamp = time();
            $nonce = bin2hex(random_bytes(16));
            $request = [
                'timestamp' => $timestamp,
                'nonce' => $nonce,
                'payload' => $encodedPayload,
                'signature' => hash_hmac('sha256', $timestamp."\n".$nonce."\n".$encodedPayload, $this->secret),
            ];

            if (strlen(json_encode($request, JSON_THROW_ON_ERROR)) > self::MAX_REQUEST_BYTES) {
                throw new TransportException('Google mail relay supports requests up to 4 MB, including attachments.');
            }
        } catch (JsonException) {
            throw new TransportException('Google mail relay email content must use valid UTF-8 encoding.');
        }

        try {
            // ContentService redirects its response to script.googleusercontent.com.
            // Normal 302 handling changes the redirected request to GET, without replaying the signed POST.
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->withOptions(['allow_redirects' => ['max' => 5, 'protocols' => ['https'], 'strict' => false]])
                ->post($this->endpoint, $request);
        } catch (Throwable) {
            // Do not attach the original HTTP exception: it may contain message data or credentials.
            throw new TransportException('Unable to connect to the Google mail relay. Try again later.');
        }

        if (! $response->successful()) {
            throw new TransportException('Google mail relay request failed with HTTP '.$response->status().'.');
        }

        try {
            $result = $response->json();
        } catch (Throwable) {
            $result = null;
        }

        if (! is_array($result) || ($result['ok'] ?? null) !== true) {
            $code = is_array($result) ? ($result['error'] ?? $result['code'] ?? null) : null;

            throw new TransportException(match ($code) {
                'quota', 'quota_exceeded' => 'Google mail relay daily email quota has been reached. Try again after the quota resets.',
                'unauthorized', 'auth_failed', 'invalid_signature' => 'Google mail relay authentication failed. Check the relay configuration.',
                'not_configured' => 'Google mail relay is not configured. Check the relay secret.',
                'busy' => 'Google mail relay is busy. Try again later.',
                'invalid_request', 'invalid_message' => 'Google mail relay rejected the email format or size.',
                'delivery_failed' => 'Google mail relay could not send the email. Try again later.',
                'expired_request', 'replayed_request' => 'Google mail relay rejected the request freshness check. Try again.',
                default => 'Google mail relay did not acknowledge email delivery.',
            });
        }
    }

    /** @return array{to: list<string>, cc: list<string>, bcc: list<string>} */
    private function recipients(SentMessage $message, Email $email): array
    {
        $envelope = array_values(array_unique(array_map(
            static fn (Address $address): string => $address->getAddress(),
            $message->getEnvelope()->getRecipients(),
        )));

        if (count($envelope) > self::MAX_RECIPIENTS) {
            throw new TransportException('Google mail relay supports at most 50 recipients per email.');
        }

        $remaining = $envelope;
        $result = ['to' => [], 'cc' => [], 'bcc' => []];

        // Only envelope recipients receive mail. Preserve explicit Bcc before any visible recipient list.
        foreach (['bcc' => $email->getBcc(), 'to' => $email->getTo(), 'cc' => $email->getCc()] as $group => $addresses) {
            foreach ($addresses as $address) {
                $value = $address->getAddress();

                if (in_array($value, $remaining, true)) {
                    $result[$group][] = $value;
                    $remaining = array_values(array_diff($remaining, [$value]));
                }
            }
        }

        // Full routing overrides become the new To list. Additional envelope recipients remain hidden.
        $group = $result['to'] === [] && $result['cc'] === [] && $result['bcc'] === [] ? 'to' : 'bcc';
        $result[$group] = [...$result[$group], ...$remaining];

        return $result;
    }

    /** @param resource|string|null $body */
    private function readBody($body): string
    {
        if (! is_resource($body)) {
            return $body ?? '';
        }

        $metadata = stream_get_meta_data($body);
        $position = ftell($body);

        if ($metadata['seekable']) {
            rewind($body);
        }

        $value = stream_get_contents($body);

        if ($metadata['seekable'] && $position !== false) {
            fseek($body, $position);
        }

        if ($value === false) {
            throw new TransportException('Google mail relay could not read the email body.');
        }

        return $value;
    }

    /** @return list<array{name: string, type: string, base64: string}> */
    private function attachments(Email $email): array
    {
        if (count($email->getAttachments()) > 10) {
            throw new TransportException('Google mail relay supports at most 10 attachments per email.');
        }

        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            if ($attachment->getDisposition() === 'inline') {
                throw new TransportException('Google mail relay does not support inline email attachments.');
            }

            try {
                $body = $attachment->getBody();
            } catch (Throwable) {
                throw new TransportException('Google mail relay could not read an email attachment.');
            }

            if (strlen($body) > self::MAX_REQUEST_BYTES) {
                throw new TransportException('Google mail relay attachment exceeds the request size limit.');
            }

            $attachments[] = [
                'name' => $attachment->getFilename() ?? 'attachment',
                'type' => $attachment->getContentType(),
                'base64' => base64_encode($body),
            ];
        }

        return $attachments;
    }
}
