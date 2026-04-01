<?php

use CleaniqueCoders\MailHistory\Events\MailBounced;
use CleaniqueCoders\MailHistory\Events\MailComplained;
use CleaniqueCoders\MailHistory\Events\MailDelivered;
use CleaniqueCoders\MailHistory\Events\MailHistoryEventReceived;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

it('dispatches MailHistoryEventReceived for every event type', function () {
    Event::fake();

    $mailHistory = MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('event-test'),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $mailHistory->recordEvent('delivered');
    $mailHistory->recordEvent('opened');
    $mailHistory->recordEvent('clicked', [], ['url' => 'https://example.com']);

    Event::assertDispatchedTimes(MailHistoryEventReceived::class, 3);
});

it('dispatches specific events for delivered, bounced, and complained', function () {
    Event::fake();

    $create = fn () => MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1(Str::random()),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $create()->recordEvent('delivered');
    $create()->recordEvent('bounced');
    $create()->recordEvent('complained');

    Event::assertDispatched(MailDelivered::class);
    Event::assertDispatched(MailBounced::class);
    Event::assertDispatched(MailComplained::class);
});

it('does not dispatch specific event for opened or clicked', function () {
    Event::fake();

    $mailHistory = MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('no-specific-event'),
        'status' => 'Sent',
        'headers' => [],
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);

    $mailHistory->recordEvent('opened');
    $mailHistory->recordEvent('clicked', [], ['url' => 'https://example.com']);

    Event::assertNotDispatched(MailDelivered::class);
    Event::assertNotDispatched(MailBounced::class);
    Event::assertNotDispatched(MailComplained::class);
});
