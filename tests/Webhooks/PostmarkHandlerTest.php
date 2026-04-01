<?php

use CleaniqueCoders\MailHistory\Webhooks\PostmarkHandler;
use Illuminate\Http\Request;

it('verifies postmark token', function () {
    config(['mailhistory.webhooks.providers.postmark.token' => 'my-token']);

    $handler = new PostmarkHandler;

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_POSTMARK_TOKEN' => 'my-token',
    ]);

    expect($handler->verify($request))->toBeTrue();
});

it('rejects invalid postmark token', function () {
    config(['mailhistory.webhooks.providers.postmark.token' => 'my-token']);

    $handler = new PostmarkHandler;

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_POSTMARK_TOKEN' => 'wrong-token',
    ]);

    expect($handler->verify($request))->toBeFalse();
});

it('allows requests when no token is configured', function () {
    config(['mailhistory.webhooks.providers.postmark.token' => null]);

    $handler = new PostmarkHandler;

    $request = Request::create('/webhook', 'POST');

    expect($handler->verify($request))->toBeTrue();
});

it('normalizes postmark delivery event', function () {
    $handler = new PostmarkHandler;
    $hash = sha1('postmark-test');

    $request = Request::create('/webhook', 'POST', [
        'RecordType' => 'Delivery',
        'DeliveredAt' => '2024-01-01T00:00:00.000Z',
        'Metadata' => ['hash' => $hash],
    ]);

    $result = $handler->handle($request);

    expect($result)->toHaveCount(1)
        ->and($result[0]['type'])->toBe('delivered')
        ->and($result[0]['hash'])->toBe($hash);
});

it('extracts hash from postmark headers', function () {
    $handler = new PostmarkHandler;
    $hash = sha1('postmark-header');

    $request = Request::create('/webhook', 'POST', [
        'RecordType' => 'Open',
        'ReceivedAt' => '2024-01-01T00:00:00.000Z',
        'Headers' => [
            ['Name' => 'X-Metadata-hash', 'Value' => $hash],
        ],
        'Geo' => ['IP' => '1.2.3.4'],
        'UserAgent' => 'Mail/1.0',
    ]);

    $result = $handler->handle($request);

    expect($result[0]['type'])->toBe('opened')
        ->and($result[0]['hash'])->toBe($hash)
        ->and($result[0]['ip_address'])->toBe('1.2.3.4')
        ->and($result[0]['user_agent'])->toBe('Mail/1.0');
});
