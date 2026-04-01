<?php

use CleaniqueCoders\MailHistory\Webhooks\ResendHandler;
use Illuminate\Http\Request;

it('allows requests when no signing secret is configured', function () {
    config(['mailhistory.webhooks.providers.resend.signing_secret' => null]);

    $handler = new ResendHandler;
    $request = Request::create('/webhook', 'POST');

    expect($handler->verify($request))->toBeTrue();
});

it('rejects when signing secret is set but headers are missing', function () {
    config(['mailhistory.webhooks.providers.resend.signing_secret' => 'whsec_test123']);

    $handler = new ResendHandler;
    $request = Request::create('/webhook', 'POST');

    expect($handler->verify($request))->toBeFalse();
});

it('verifies valid svix signature', function () {
    $secretRaw = random_bytes(24);
    $secret = 'whsec_'.base64_encode($secretRaw);
    config(['mailhistory.webhooks.providers.resend.signing_secret' => $secret]);

    $handler = new ResendHandler;

    $body = '{"type":"email.delivered","data":{}}';
    $svixId = 'msg_test123';
    $svixTimestamp = (string) time();

    $signedContent = "{$svixId}.{$svixTimestamp}.{$body}";
    $computed = base64_encode(hash_hmac('sha256', $signedContent, $secretRaw, true));

    $request = Request::create('/webhook', 'POST', content: $body);
    $request->headers->set('svix-id', $svixId);
    $request->headers->set('svix-timestamp', $svixTimestamp);
    $request->headers->set('svix-signature', "v1,{$computed}");

    expect($handler->verify($request))->toBeTrue();
});

it('normalizes resend delivered event', function () {
    $handler = new ResendHandler;
    $hash = sha1('resend-test');

    $request = Request::create('/webhook', 'POST', [
        'type' => 'email.delivered',
        'data' => [
            'created_at' => '2024-01-01T00:00:00.000Z',
            'headers' => [
                'X-Metadata-hash' => $hash,
            ],
        ],
    ]);

    $result = $handler->handle($request);

    expect($result)->toHaveCount(1)
        ->and($result[0]['type'])->toBe('delivered')
        ->and($result[0]['hash'])->toBe($hash);
});

it('extracts hash from resend tags', function () {
    $handler = new ResendHandler;
    $hash = sha1('resend-tag');

    $request = Request::create('/webhook', 'POST', [
        'type' => 'email.opened',
        'data' => [
            'headers' => [],
            'tags' => [
                ['name' => 'hash', 'value' => $hash],
            ],
        ],
    ]);

    $result = $handler->handle($request);

    expect($result[0]['type'])->toBe('opened')
        ->and($result[0]['hash'])->toBe($hash);
});
