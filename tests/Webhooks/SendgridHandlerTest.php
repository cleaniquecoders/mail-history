<?php

use CleaniqueCoders\MailHistory\Webhooks\SendgridHandler;
use Illuminate\Http\Request;

it('allows requests when no verification key is configured', function () {
    config(['mailhistory.webhooks.providers.sendgrid.verification_key' => null]);

    $handler = new SendgridHandler;
    $request = Request::create('/webhook', 'POST');

    expect($handler->verify($request))->toBeTrue();
});

it('rejects when verification key is set but headers are missing', function () {
    config(['mailhistory.webhooks.providers.sendgrid.verification_key' => 'some-key']);

    $handler = new SendgridHandler;
    $request = Request::create('/webhook', 'POST');

    expect($handler->verify($request))->toBeFalse();
});

it('normalizes sendgrid delivered event', function () {
    $handler = new SendgridHandler;
    $hash = sha1('sg-test');

    $request = Request::create('/webhook', 'POST', [
        [
            'event' => 'delivered',
            'timestamp' => 1700000000,
            'hash' => $hash,
            'ip' => '5.6.7.8',
        ],
    ]);

    $result = $handler->handle($request);

    expect($result)->toHaveCount(1)
        ->and($result[0]['type'])->toBe('delivered')
        ->and($result[0]['hash'])->toBe($hash)
        ->and($result[0]['ip_address'])->toBe('5.6.7.8');
});

it('normalizes sendgrid click event with URL', function () {
    $handler = new SendgridHandler;
    $hash = sha1('sg-click');

    $request = Request::create('/webhook', 'POST', [
        [
            'event' => 'click',
            'timestamp' => 1700000000,
            'hash' => $hash,
            'url' => 'https://example.com/page',
            'useragent' => 'Mozilla/5.0',
        ],
    ]);

    $result = $handler->handle($request);

    expect($result[0]['type'])->toBe('clicked')
        ->and($result[0]['url'])->toBe('https://example.com/page')
        ->and($result[0]['user_agent'])->toBe('Mozilla/5.0');
});

it('handles multiple sendgrid events in one request', function () {
    $handler = new SendgridHandler;
    $hash = sha1('sg-multi');

    $request = Request::create('/webhook', 'POST', [
        ['event' => 'delivered', 'hash' => $hash, 'timestamp' => 1700000000],
        ['event' => 'open', 'hash' => $hash, 'timestamp' => 1700000100],
    ]);

    $result = $handler->handle($request);

    expect($result)->toHaveCount(2)
        ->and($result[0]['type'])->toBe('delivered')
        ->and($result[1]['type'])->toBe('opened');
});

it('extracts hash from unique_args', function () {
    $handler = new SendgridHandler;
    $hash = sha1('sg-unique');

    $request = Request::create('/webhook', 'POST', [
        [
            'event' => 'delivered',
            'timestamp' => 1700000000,
            'unique_args' => ['hash' => $hash],
        ],
    ]);

    $result = $handler->handle($request);

    expect($result[0]['hash'])->toBe($hash);
});
