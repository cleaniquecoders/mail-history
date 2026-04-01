<?php

use CleaniqueCoders\MailHistory\Events\MailBounced;
use CleaniqueCoders\MailHistory\Events\MailComplained;
use CleaniqueCoders\MailHistory\Events\MailDelivered;
use CleaniqueCoders\MailHistory\Events\MailHistoryEventReceived;
use CleaniqueCoders\MailHistory\MailHistory as MailHistoryConstants;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use CleaniqueCoders\MailHistory\Models\MailHistoryEvent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

function createMailHistoryRecord(string $status = 'Sent', ?string $hash = null): MailHistory
{
    return MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => $hash ?? sha1(Str::orderedUuid()),
        'status' => $status,
        'headers' => [],
        'body' => 'Test body',
        'content' => ['text' => null, 'html' => '<p>Test body</p>'],
    ]);
}

it('has events relationship', function () {
    $mailHistory = createMailHistoryRecord();

    expect($mailHistory->events())->toBeInstanceOf(HasMany::class)
        ->and($mailHistory->events)->toHaveCount(0);
});

it('can record an event and update status', function () {
    Event::fake();

    $mailHistory = createMailHistoryRecord();
    $event = $mailHistory->recordEvent('delivered', ['test' => true], [
        'provider' => 'mailgun',
    ]);

    expect($event)->toBeInstanceOf(MailHistoryEvent::class)
        ->and($event->type)->toBe('delivered')
        ->and($event->provider)->toBe('mailgun')
        ->and($mailHistory->fresh()->status)->toBe(MailHistoryConstants::STATUS_DELIVERED);

    Event::assertDispatched(MailHistoryEventReceived::class);
    Event::assertDispatched(MailDelivered::class);
});

it('dispatches MailBounced event for bounced type', function () {
    Event::fake();

    $mailHistory = createMailHistoryRecord();
    $mailHistory->recordEvent('bounced', ['reason' => 'invalid address']);

    expect($mailHistory->fresh()->status)->toBe(MailHistoryConstants::STATUS_BOUNCED);

    Event::assertDispatched(MailBounced::class);
});

it('dispatches MailComplained event for complained type', function () {
    Event::fake();

    $mailHistory = createMailHistoryRecord();
    $mailHistory->recordEvent('complained');

    expect($mailHistory->fresh()->status)->toBe(MailHistoryConstants::STATUS_COMPLAINED);

    Event::assertDispatched(MailComplained::class);
});

it('can get timeline ordered by occurred_at', function () {
    $mailHistory = createMailHistoryRecord();

    $mailHistory->recordEvent('delivered', [], ['occurred_at' => now()->subMinutes(2)]);
    $mailHistory->recordEvent('opened', [], ['occurred_at' => now()->subMinute()]);
    $mailHistory->recordEvent('clicked', [], ['occurred_at' => now(), 'url' => 'https://example.com']);

    $timeline = $mailHistory->getTimeline();

    expect($timeline)->toHaveCount(3)
        ->and($timeline->first()->type)->toBe('delivered')
        ->and($timeline->last()->type)->toBe('clicked');
});

it('has status scopes', function () {
    createMailHistoryRecord(MailHistoryConstants::STATUS_DELIVERED);
    createMailHistoryRecord(MailHistoryConstants::STATUS_BOUNCED);
    createMailHistoryRecord(MailHistoryConstants::STATUS_OPENED);

    expect(MailHistory::delivered()->count())->toBe(1)
        ->and(MailHistory::bounced()->count())->toBe(1)
        ->and(MailHistory::opened()->count())->toBe(1)
        ->and(MailHistory::clicked()->count())->toBe(0);
});

it('has status accessor attributes', function () {
    $delivered = createMailHistoryRecord(MailHistoryConstants::STATUS_DELIVERED);
    $bounced = createMailHistoryRecord(MailHistoryConstants::STATUS_BOUNCED);
    $opened = createMailHistoryRecord(MailHistoryConstants::STATUS_OPENED);

    expect($delivered->is_delivered)->toBeTrue()
        ->and($delivered->is_bounced)->toBeFalse()
        ->and($bounced->is_bounced)->toBeTrue()
        ->and($opened->is_opened)->toBeTrue();
});

it('can scope by arbitrary status', function () {
    createMailHistoryRecord(MailHistoryConstants::STATUS_FAILED);

    expect(MailHistory::status(MailHistoryConstants::STATUS_FAILED)->count())->toBe(1);
});

it('backfills delivered event when opened is recorded without prior delivery', function () {
    $mailHistory = createMailHistoryRecord('Sent');

    $mailHistory->recordEvent('opened', [], [
        'ip_address' => '1.2.3.4',
    ]);

    $timeline = $mailHistory->getTimeline();

    expect($timeline)->toHaveCount(2)
        ->and($timeline->first()->type)->toBe('delivered')
        ->and($timeline->last()->type)->toBe('opened')
        ->and($mailHistory->fresh()->status)->toBe(MailHistoryConstants::STATUS_OPENED);
});

it('backfills delivered and opened events when clicked is recorded directly', function () {
    $mailHistory = createMailHistoryRecord('Sent');

    $mailHistory->recordEvent('clicked', [], [
        'url' => 'https://example.com',
    ]);

    $timeline = $mailHistory->getTimeline();

    expect($timeline)->toHaveCount(3)
        ->and($timeline->pluck('type')->toArray())->toBe(['delivered', 'opened', 'clicked'])
        ->and($mailHistory->fresh()->status)->toBe(MailHistoryConstants::STATUS_CLICKED);
});

it('does not duplicate implied events if they already exist', function () {
    $mailHistory = createMailHistoryRecord('Sent');

    $mailHistory->recordEvent('delivered');
    $mailHistory->recordEvent('opened');
    $mailHistory->recordEvent('clicked', [], ['url' => 'https://example.com']);

    $timeline = $mailHistory->getTimeline();

    expect($timeline)->toHaveCount(3)
        ->and($timeline->where('type', 'delivered')->count())->toBe(1)
        ->and($timeline->where('type', 'opened')->count())->toBe(1);
});
