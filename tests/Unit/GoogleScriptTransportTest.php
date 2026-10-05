<?php

namespace Tests\Unit;

use App\Mail\GoogleScriptTransport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class GoogleScriptTransportTest extends TestCase
{
    private const ENDPOINT = 'https://script.google.com/macros/s/testDeployment_123/exec';

    private const SECRET = 'test-relay-secret-that-is-at-least-32-characters';

    public function test_it_signs_the_complete_message_and_preserves_visible_and_hidden_recipients(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['ok' => true])]);
        $email = $this->email()
            ->cc('copy@example.com')
            ->bcc('hidden@example.com')
            ->replyTo('reply@example.com')
            ->html('<p>Verify your email.</p>')
            ->attach('example attachment', 'instructions.txt', 'text/plain');

        $sent = $this->transport()->send($email);

        $this->assertNotNull($sent);
        $this->assertSame('google_script', (string) $this->transport());
        Http::assertSent(function (Request $request): bool {
            $envelope = $request->data();
            $this->assertSame('POST', $request->method());
            $this->assertSame(self::ENDPOINT, $request->url());
            $this->assertIsInt($envelope['timestamp']);
            $this->assertLessThanOrEqual(2, abs(time() - $envelope['timestamp']));
            $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $envelope['nonce']);
            $this->assertSame(hash_hmac('sha256', $envelope['timestamp']."\n".$envelope['nonce']."\n".$envelope['payload'], self::SECRET), $envelope['signature']);
            $this->assertStringNotContainsString(self::SECRET, $request->body());
            $payload = json_decode(base64_decode($envelope['payload'], true), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(['recipient@example.com'], $payload['to']);
            $this->assertSame(['copy@example.com'], $payload['cc']);
            $this->assertSame(['hidden@example.com'], $payload['bcc']);
            $this->assertSame('CBIS Notifications', $payload['name']);
            $this->assertSame('Verification', $payload['subject']);
            $this->assertSame('Verify your email.', $payload['text']);
            $this->assertSame('<p>Verify your email.</p>', $payload['html']);
            $this->assertSame('reply@example.com', $payload['replyTo']);
            $this->assertSame([['name' => 'instructions.txt', 'type' => 'text/plain', 'base64' => base64_encode('example attachment')]], $payload['attachments']);

            return true;
        });
        Http::assertSentCount(1);
    }

    public function test_it_honors_envelope_routing_instead_of_delivering_to_original_headers(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['ok' => true])]);
        $email = $this->email()->cc('original-copy@example.com')->bcc('original-hidden@example.com');
        $envelope = new Envelope(new Address('sender@example.com'), [new Address('override@example.com')]);

        $this->transport()->send($email, $envelope);

        Http::assertSent(function (Request $request): bool {
            $payload = $this->payload($request);
            $this->assertSame(['override@example.com'], $payload['to']);
            $this->assertSame([], $payload['cc']);
            $this->assertSame([], $payload['bcc']);

            return true;
        });
    }

    public function test_it_keeps_additional_envelope_recipients_hidden(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['ok' => true])]);
        $envelope = new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com'), new Address('hidden-routing@example.com')]);

        $this->transport()->send($this->email(), $envelope);

        Http::assertSent(function (Request $request): bool {
            $payload = $this->payload($request);
            $this->assertSame(['recipient@example.com'], $payload['to']);
            $this->assertSame(['hidden-routing@example.com'], $payload['bcc']);

            return true;
        });
    }

    public function test_it_keeps_bcc_only_recipients_out_of_visible_headers(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['ok' => true])]);
        $email = (new Email)->from('sender@example.com')->bcc('hidden@example.com')->subject('Private')->text('Private notice');

        $this->transport()->send($email);

        Http::assertSent(function (Request $request): bool {
            $payload = $this->payload($request);
            $this->assertSame([], $payload['to']);
            $this->assertSame([], $payload['cc']);
            $this->assertSame(['hidden@example.com'], $payload['bcc']);

            return true;
        });
    }

    #[DataProvider('invalidConfigurations')]
    public function test_it_rejects_missing_secrets_and_untrusted_endpoints(?string $endpoint, ?string $secret): void
    {
        Http::fake();
        $this->expectException(TransportException::class);

        try {
            new GoogleScriptTransport($endpoint, $secret);
        } finally {
            Http::assertNothingSent();
        }
    }

    public static function invalidConfigurations(): array
    {
        return [
            'missing endpoint' => [null, self::SECRET],
            'missing secret' => [self::ENDPOINT, null],
            'short secret' => [self::ENDPOINT, 'short'],
            'http endpoint' => ['http://script.google.com/macros/s/example/exec', self::SECRET],
            'untrusted host' => ['https://script.google.com.evil.example/macros/s/example/exec', self::SECRET],
            'embedded credentials' => ['https://user@script.google.com/macros/s/example/exec', self::SECRET],
            'dev endpoint' => ['https://script.google.com/macros/s/example/dev', self::SECRET],
            'url secret' => [self::ENDPOINT.'?secret=should-not-be-in-url', self::SECRET],
        ];
    }

    #[DataProvider('failedRelayResponses')]
    public function test_it_requires_delivery_acknowledgment_and_reports_known_failures_safely(mixed $body, int $status, string $expected): void
    {
        Http::fake([self::ENDPOINT => Http::response($body, $status)]);
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage($expected);

        try {
            $this->transport()->send($this->email());
        } catch (TransportException $exception) {
            $this->assertStringNotContainsString('sensitive-response-detail', $exception->getMessage());
            $this->assertStringNotContainsString(self::SECRET, $exception->getMessage());
            throw $exception;
        }
    }

    public static function failedRelayResponses(): array
    {
        return [
            'quota' => [['ok' => false, 'error' => 'quota_exceeded', 'detail' => 'sensitive-response-detail'], 200, 'daily email quota'],
            'authentication' => [['ok' => false, 'error' => 'unauthorized', 'detail' => 'sensitive-response-detail'], 200, 'authentication failed'],
            'missing configuration' => [['ok' => false, 'error' => 'not_configured'], 200, 'not configured'],
            'busy relay' => [['ok' => false, 'error' => 'busy'], 200, 'relay is busy'],
            'invalid format' => [['ok' => false, 'error' => 'invalid_message'], 200, 'format or size'],
            'delivery failure' => [['ok' => false, 'error' => 'delivery_failed'], 200, 'could not send'],
            'missing acknowledgment' => [['status' => 'sent'], 200, 'did not acknowledge'],
            'nonboolean acknowledgment' => [['ok' => 'true'], 200, 'did not acknowledge'],
            'html error' => ['<html>sensitive-response-detail</html>', 200, 'did not acknowledge'],
            'unknown relay error' => [['ok' => false, 'code' => 'unknown', 'error' => 'sensitive-response-detail'], 200, 'did not acknowledge'],
            'http error' => [['error' => 'sensitive-response-detail'], 503, 'HTTP 503'],
        ];
    }

    public function test_connection_failures_do_not_leak_the_http_exception(): void
    {
        Http::fake(static function () {
            throw new ConnectionException('sensitive-response-detail '.self::SECRET);
        });

        try {
            $this->transport()->send($this->email());
            $this->fail('A transport exception should be raised.');
        } catch (TransportException $exception) {
            $this->assertStringContainsString('Unable to connect', $exception->getMessage());
            $this->assertStringNotContainsString(self::SECRET, $exception->getMessage());
            $this->assertStringNotContainsString('sensitive-response-detail', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_oversized_bodies_are_rejected_before_any_http_request(): void
    {
        Http::fake();
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('200 KB');

        try {
            $this->transport()->send($this->email()->text(str_repeat('x', 200 * 1024 + 1)));
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_rejects_more_than_fifty_envelope_recipients(): void
    {
        Http::fake();
        $recipients = array_map(static fn (int $number): Address => new Address('recipient'.$number.'@example.com'), range(1, 51));
        $envelope = new Envelope(new Address('sender@example.com'), $recipients);
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('at most 50 recipients');

        try {
            $this->transport()->send($this->email(), $envelope);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_rejects_oversized_encoded_attachments_before_any_http_request(): void
    {
        Http::fake();
        $email = $this->email()->attach(str_repeat('x', 3 * 1024 * 1024), 'large.bin');
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('4 MB');

        try {
            $this->transport()->send($email);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_each_send_has_a_new_nonce(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['ok' => true])]);
        $transport = $this->transport();
        $transport->send($this->email());
        $transport->send($this->email());
        $requests = Http::recorded();

        $this->assertCount(2, $requests);
        $this->assertNotSame($requests[0][0]['nonce'], $requests[1][0]['nonce']);
    }

    public function test_it_rejects_more_than_ten_attachments_before_any_http_request(): void
    {
        Http::fake();
        $email = $this->email();

        foreach (range(1, 11) as $number) {
            $email->attach('contents', 'file'.$number.'.txt', 'text/plain');
        }

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('at most 10 attachments');

        try {
            $this->transport()->send($email);
        } finally {
            Http::assertNothingSent();
        }
    }

    private function email(): Email
    {
        return (new Email)
            ->from(new Address('sender@example.com', 'CBIS Notifications'))
            ->to('recipient@example.com')
            ->subject('Verification')
            ->text('Verify your email.');
    }

    private function transport(): GoogleScriptTransport
    {
        return new GoogleScriptTransport(self::ENDPOINT, self::SECRET);
    }

    private function payload(Request $request): array
    {
        return json_decode(base64_decode($request['payload'], true), true, flags: JSON_THROW_ON_ERROR);
    }
}
