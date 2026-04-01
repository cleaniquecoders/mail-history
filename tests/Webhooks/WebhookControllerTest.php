<?php

use CleaniqueCoders\MailHistory\Http\Controllers\WebhookController;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

beforeEach(function () {
    Route::prefix(config('mailhistory.webhooks.path', 'mailhistory/webhooks'))
        ->group(function () {
            Route::post('{provider}', WebhookController::class)->name('mailhistory.webhooks');
        });
});

it('returns 404 for unknown provider', function () {
    $this->post('/mailhistory/webhooks/unknown')
        ->assertStatus(404);
});

it('returns 403 when signature verification fails', function () {
    config([
        'mailhistory.webhooks.providers.mailgun.signing_key' => 'test-secret',
    ]);

    $this->post('/mailhistory/webhooks/mailgun', [
        'signature' => [
            'timestamp' => '1234567890',
            'token' => 'test-token',
            'signature' => 'invalid-signature',
        ],
    ])->assertStatus(403);
});

it('processes valid webhook and creates event', function () {
    config([
        'mailhistory.webhooks.providers.mailgun.signing_key' => 'test-secret',
    ]);

    $hash = sha1('webhook-test');

    MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => $hash,
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $timestamp = time();
    $token = 'test-token-'.Str::random(32);
    $signature = hash_hmac('sha256', $timestamp.$token, 'test-secret');

    $this->post('/mailhistory/webhooks/mailgun', [
        'signature' => [
            'timestamp' => (string) $timestamp,
            'token' => $token,
            'signature' => $signature,
        ],
        'event-data' => [
            'event' => 'delivered',
            'timestamp' => $timestamp,
            'user-variables' => ['hash' => $hash],
            'message' => ['headers' => []],
        ],
    ])->assertStatus(200);

    $this->assertDatabaseHas('mail_histories', [
        'hash' => $hash,
        'status' => 'Delivered',
    ]);

    $this->assertDatabaseHas('mail_history_events', [
        'type' => 'delivered',
        'provider' => 'mailgun',
    ]);
});
