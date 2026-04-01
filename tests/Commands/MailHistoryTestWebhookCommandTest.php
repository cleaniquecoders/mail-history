<?php

use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Support\Str;

it('records a simulated webhook event', function () {
    $hash = sha1('cmd-test');

    MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => $hash,
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $this->artisan('mailhistory:test-webhook', [
        'provider' => 'mailgun',
        'type' => 'delivered',
        '--hash' => $hash,
    ])->assertExitCode(0);

    $this->assertDatabaseHas('mail_history_events', [
        'type' => 'delivered',
        'provider' => 'mailgun',
    ]);

    $this->assertDatabaseHas('mail_histories', [
        'hash' => $hash,
        'status' => 'Delivered',
    ]);
});

it('fails for unknown provider', function () {
    $this->artisan('mailhistory:test-webhook', [
        'provider' => 'unknown',
        'type' => 'delivered',
        '--hash' => 'some-hash',
    ])->assertExitCode(1);
});

it('fails when no mail history records exist and no hash provided', function () {
    $this->artisan('mailhistory:test-webhook', [
        'provider' => 'mailgun',
    ])->assertExitCode(1);
});
