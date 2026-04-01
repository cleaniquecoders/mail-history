<?php

use CleaniqueCoders\MailHistory\Webhooks\SesHandler;
use Illuminate\Http\Request;

it('verifies SNS notification with required fields', function () {
    $handler = new SesHandler;

    $request = Request::create('/webhook', 'POST', [
        'Type' => 'Notification',
        'Message' => '{}',
        'MessageId' => 'test-id',
        'Timestamp' => '2024-01-01T00:00:00.000Z',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:test',
        'Signature' => 'base64signature',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
    ]);

    expect($handler->verify($request))->toBeTrue();
});

it('rejects SNS with invalid cert URL', function () {
    $handler = new SesHandler;

    $request = Request::create('/webhook', 'POST', [
        'Type' => 'Notification',
        'Message' => '{}',
        'MessageId' => 'test-id',
        'Timestamp' => '2024-01-01T00:00:00.000Z',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:test',
        'Signature' => 'base64signature',
        'SigningCertURL' => 'https://evil.com/cert.pem',
    ]);

    expect($handler->verify($request))->toBeFalse();
});

it('normalizes SES delivery event', function () {
    $handler = new SesHandler;
    $hash = sha1('ses-test');

    $message = json_encode([
        'eventType' => 'Delivery',
        'mail' => [
            'timestamp' => '2024-01-01T00:00:00.000Z',
            'headers' => [
                ['name' => 'X-Metadata-hash', 'value' => $hash],
            ],
        ],
    ]);

    $request = Request::create('/webhook', 'POST', [
        'Type' => 'Notification',
        'Message' => $message,
        'MessageId' => 'test-id',
        'Timestamp' => '2024-01-01T00:00:00.000Z',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:test',
        'Signature' => 'base64signature',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
    ]);

    $result = $handler->handle($request);

    expect($result)->toHaveCount(1)
        ->and($result[0]['type'])->toBe('delivered')
        ->and($result[0]['hash'])->toBe($hash);
});

it('handles SES click event with link', function () {
    $handler = new SesHandler;
    $hash = sha1('ses-click');

    $message = json_encode([
        'eventType' => 'Click',
        'mail' => [
            'headers' => [
                ['name' => 'X-Metadata-hash', 'value' => $hash],
            ],
        ],
        'click' => [
            'link' => 'https://example.com',
        ],
    ]);

    $request = Request::create('/webhook', 'POST', [
        'Type' => 'Notification',
        'Message' => $message,
        'MessageId' => 'test-id',
        'Timestamp' => '2024-01-01T00:00:00.000Z',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:test',
        'Signature' => 'sig',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
    ]);

    $result = $handler->handle($request);

    expect($result[0]['type'])->toBe('clicked')
        ->and($result[0]['url'])->toBe('https://example.com');
});
