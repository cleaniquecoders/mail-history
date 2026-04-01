<?php

use CleaniqueCoders\MailHistory\Models\MailHistory;
use CleaniqueCoders\MailHistory\Models\MailHistoryEvent;
use Illuminate\Support\Str;

it('prunes old mail history records', function () {
    $old = MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('prune-old'),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Old',
        'content' => ['text' => null, 'html' => 'Old'],
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDays(100),
    ]);

    MailHistoryEvent::create([
        'uuid' => Str::orderedUuid(),
        'mail_history_id' => $old->id,
        'type' => 'delivered',
        'payload' => [],
        'occurred_at' => now()->subDays(100),
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDays(100),
    ]);

    $recent = MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('prune-recent'),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Recent',
        'content' => ['text' => null, 'html' => 'Recent'],
    ]);

    $this->artisan('mailhistory:prune', ['--days' => 90])
        ->assertExitCode(0);

    expect(MailHistory::count())->toBe(1)
        ->and(MailHistory::first()->hash)->toBe(sha1('prune-recent'))
        ->and(MailHistoryEvent::count())->toBe(0);
});

it('prunes only events with events-only flag', function () {
    $old = MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('prune-events-only'),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Old',
        'content' => ['text' => null, 'html' => 'Old'],
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDays(100),
    ]);

    MailHistoryEvent::create([
        'uuid' => Str::orderedUuid(),
        'mail_history_id' => $old->id,
        'type' => 'delivered',
        'payload' => [],
        'occurred_at' => now()->subDays(100),
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDays(100),
    ]);

    $this->artisan('mailhistory:prune', ['--days' => 90, '--events-only' => true])
        ->assertExitCode(0);

    expect(MailHistory::count())->toBe(1)
        ->and(MailHistoryEvent::count())->toBe(0);
});
