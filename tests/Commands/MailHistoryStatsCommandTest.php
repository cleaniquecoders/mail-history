<?php

use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Support\Str;

it('displays stats table', function () {
    MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('stats-1'),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('stats-2'),
        'status' => 'Delivered',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $this->artisan('mailhistory:stats')
        ->assertExitCode(0);
});

it('accepts days option', function () {
    $this->artisan('mailhistory:stats', ['--days' => 7])
        ->assertExitCode(0);
});
