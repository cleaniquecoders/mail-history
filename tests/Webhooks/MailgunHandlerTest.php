<?php

use CleaniqueCoders\MailHistory\Webhooks\MailgunHandler;
use Illuminate\Http\Request;

it('verifies valid mailgun signature', function () {
    config(['mailhistory.webhooks.providers.mailgun.signing_key' => 'test-key']);

    $handler = new MailgunHandler;
    $timestamp = time();
    $token = 'random-token';
    $signature = hash_hmac('sha256', $timestamp.$token, 'test-key');

    $request = Request::create('/webhook', 'POST', [
        'signature' => [
            'timestamp' => (string) $timestamp,
            'token' => $token,
            'signature' => $signature,
        ],
    ]);

    expect($handler->verify($request))->toBeTrue();
});

it('rejects invalid mailgun signature', function () {
    config(['mailhistory.webhooks.providers.mailgun.signing_key' => 'test-key']);

    $handler = new MailgunHandler;

    $request = Request::create('/webhook', 'POST', [
        'signature' => [
            'timestamp' => (string) time(),
            'token' => 'random-token',
            'signature' => 'bad-signature',
        ],
    ]);

    expect($handler->verify($request))->toBeFalse();
});

it('rejects when signing key is empty', function () {
    config(['mailhistory.webhooks.providers.mailgun.signing_key' => null]);

    $handler = new MailgunHandler;

    $request = Request::create('/webhook', 'POST', [
        'signature' => [
            'timestamp' => (string) time(),
            'token' => 'random-token',
            'signature' => 'some-sig',
        ],
    ]);

    expect($handler->verify($request))->toBeFalse();
});

it('normalizes mailgun delivered event', function () {
    $handler = new MailgunHandler;
    $hash = sha1('test');

    $request = Request::create('/webhook', 'POST', [
        'event-data' => [
            'event' => 'delivered',
            'timestamp' => 1700000000,
            'ip' => '1.2.3.4',
            'user-variables' => ['hash' => $hash],
            'message' => ['headers' => []],
            'client-info' => ['user-agent' => 'Mozilla/5.0'],
        ],
    ]);

    $result = $handler->handle($request);

    expect($result)->toHaveCount(1)
        ->and($result[0]['type'])->toBe('delivered')
        ->and($result[0]['hash'])->toBe($hash)
        ->and($result[0]['ip_address'])->toBe('1.2.3.4')
        ->and($result[0]['user_agent'])->toBe('Mozilla/5.0');
});

it('returns empty for unknown mailgun event type', function () {
    $handler = new MailgunHandler;

    $request = Request::create('/webhook', 'POST', [
        'event-data' => [
            'event' => 'unknown_event',
            'user-variables' => ['hash' => 'some-hash'],
            'message' => ['headers' => []],
        ],
    ]);

    expect($handler->handle($request))->toBeEmpty();
});
