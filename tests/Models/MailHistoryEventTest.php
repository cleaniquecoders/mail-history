<?php

use CleaniqueCoders\MailHistory\Models\MailHistory;
use CleaniqueCoders\MailHistory\Models\MailHistoryEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

it('can create a mail history event', function () {
    $mailHistory = MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('test-hash'),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test body',
        'content' => ['text' => null, 'html' => 'Test body'],
    ]);

    $event = MailHistoryEvent::create([
        'uuid' => Str::orderedUuid(),
        'mail_history_id' => $mailHistory->id,
        'type' => 'delivered',
        'payload' => ['test' => true],
        'occurred_at' => now(),
    ]);

    expect($event)->toBeInstanceOf(MailHistoryEvent::class)
        ->and($event->type)->toBe('delivered')
        ->and($event->payload)->toBe(['test' => true])
        ->and($event->occurred_at)->toBeInstanceOf(Carbon::class);
});

it('belongs to a mail history record', function () {
    $mailHistory = MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('test-relation'),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test body',
        'content' => ['text' => null, 'html' => 'Test body'],
    ]);

    $event = MailHistoryEvent::create([
        'uuid' => Str::orderedUuid(),
        'mail_history_id' => $mailHistory->id,
        'type' => 'delivered',
        'payload' => [],
        'occurred_at' => now(),
    ]);

    expect($event->mailHistory->id)->toBe($mailHistory->id);
});
