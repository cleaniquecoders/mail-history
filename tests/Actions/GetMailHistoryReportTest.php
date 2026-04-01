<?php

use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;
use CleaniqueCoders\MailHistory\Actions\GetMailHistoryReport;
use CleaniqueCoders\MailHistory\MailHistory as MailHistoryConstants;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use CleaniqueCoders\MailHistory\Models\MailHistoryEvent;
use Illuminate\Support\Str;

function createRecord(string $status = 'Sent', ?string $hash = null, ?array $headers = []): MailHistory
{
    return MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => $hash ?? sha1(Str::random()),
        'status' => $status,
        'headers' => $headers,
        'body' => 'Test',
        'content' => ['text' => null, 'html' => 'Test'],
    ]);
}

function mailReport(): GetMailHistoryReport
{
    return app(MailHistoryReport::class);
}

// --- summary ---

it('returns summary with counts and rates', function () {
    createRecord(MailHistoryConstants::STATUS_DELIVERED);
    createRecord(MailHistoryConstants::STATUS_DELIVERED);
    createRecord(MailHistoryConstants::STATUS_BOUNCED);
    createRecord(MailHistoryConstants::STATUS_OPENED);

    $summary = mailReport()->summary();

    expect($summary['total'])->toBe(4)
        ->and($summary['statuses']['Delivered'])->toBe(2)
        ->and($summary['statuses']['Bounced'])->toBe(1)
        ->and($summary['statuses']['Opened'])->toBe(1)
        ->and($summary['rates']['Delivered'])->toBe(50.0)
        ->and($summary['rates']['Bounced'])->toBe(25.0);
});

it('returns summary filtered by date range', function () {
    createRecord(MailHistoryConstants::STATUS_DELIVERED);

    MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1(Str::random()),
        'status' => MailHistoryConstants::STATUS_DELIVERED,
        'headers' => [],
        'body' => 'Old',
        'content' => ['text' => null, 'html' => 'Old'],
        'created_at' => now()->subDays(60),
        'updated_at' => now()->subDays(60),
    ]);

    $summary = mailReport()->summary(from: now()->subDays(7));

    expect($summary['total'])->toBe(1);
});

it('returns empty summary when no records', function () {
    $summary = mailReport()->summary();

    expect($summary['total'])->toBe(0)
        ->and($summary['rates'])->toBe([]);
});

// --- trends ---

it('returns daily trends', function () {
    createRecord(MailHistoryConstants::STATUS_DELIVERED);
    createRecord(MailHistoryConstants::STATUS_BOUNCED);

    $trends = mailReport()->trends('daily', now()->subDay(), now()->addDay());

    expect($trends)->toHaveCount(1)
        ->and((int) $trends->first()->total)->toBe(2);
});

it('returns monthly trends', function () {
    createRecord(MailHistoryConstants::STATUS_SENT);

    $trends = mailReport()->trends('monthly', now()->startOfMonth(), now()->endOfMonth());

    expect($trends)->not->toBeEmpty()
        ->and((int) $trends->first()->total)->toBeGreaterThanOrEqual(1);
});

// --- byProvider ---

it('returns breakdown by provider', function () {
    $mail1 = createRecord(MailHistoryConstants::STATUS_DELIVERED);
    $mail2 = createRecord(MailHistoryConstants::STATUS_BOUNCED);

    MailHistoryEvent::create([
        'uuid' => Str::orderedUuid(),
        'mail_history_id' => $mail1->id,
        'type' => 'delivered',
        'provider' => 'mailgun',
        'payload' => [],
        'occurred_at' => now(),
    ]);

    MailHistoryEvent::create([
        'uuid' => Str::orderedUuid(),
        'mail_history_id' => $mail2->id,
        'type' => 'bounced',
        'provider' => 'mailgun',
        'payload' => [],
        'occurred_at' => now(),
    ]);

    MailHistoryEvent::create([
        'uuid' => Str::orderedUuid(),
        'mail_history_id' => $mail1->id,
        'type' => 'delivered',
        'provider' => 'ses',
        'payload' => [],
        'occurred_at' => now(),
    ]);

    $result = mailReport()->byProvider();

    expect($result)->toHaveCount(2);

    $mailgun = $result->firstWhere('provider', 'mailgun');
    expect((int) $mailgun->total)->toBe(2)
        ->and((int) $mailgun->delivered)->toBe(1)
        ->and((int) $mailgun->bounced)->toBe(1);
});

// --- timeline ---

it('returns timeline for a mail history record by hash', function () {
    $mail = createRecord(MailHistoryConstants::STATUS_SENT, 'timeline-hash');
    $mail->recordEvent('delivered', [], ['provider' => 'mailgun']);
    $mail->recordEvent('opened', [], ['ip_address' => '1.2.3.4']);

    $timeline = mailReport()->timeline('timeline-hash');

    expect($timeline)->toHaveCount(2)
        ->and($timeline->first()['type'])->toBe('delivered')
        ->and($timeline->last()['type'])->toBe('opened');
});

it('returns timeline for a mail history record by id', function () {
    $mail = createRecord(MailHistoryConstants::STATUS_SENT);
    $mail->recordEvent('delivered');

    $timeline = mailReport()->timeline($mail->id);

    expect($timeline)->toHaveCount(1);
});

// --- topRecipients ---

it('returns top recipients for a given status', function () {
    createRecord(MailHistoryConstants::STATUS_BOUNCED, null, ['To' => 'bad@example.com']);
    createRecord(MailHistoryConstants::STATUS_BOUNCED, null, ['To' => 'bad@example.com']);
    createRecord(MailHistoryConstants::STATUS_BOUNCED, null, ['To' => 'other@example.com']);

    $top = mailReport()->topRecipients(MailHistoryConstants::STATUS_BOUNCED);

    expect($top)->toHaveCount(2)
        ->and($top->first()['recipient'])->toBe('bad@example.com')
        ->and($top->first()['count'])->toBe(2);
});

// --- recentActivity ---

it('returns recent activity across all records', function () {
    $mail = createRecord(MailHistoryConstants::STATUS_SENT);
    $mail->recordEvent('delivered', [], ['provider' => 'postmark']);
    $mail->recordEvent('opened');

    $activity = mailReport()->recentActivity(10);

    // delivered + opened (no backfill since delivered already exists)
    expect($activity)->toHaveCount(2);

    $types = $activity->pluck('type')->toArray();
    expect($types)->toContain('delivered')
        ->and($types)->toContain('opened');
});

// --- stale ---

it('returns stale records stuck in a status', function () {
    $stale = MailHistory::create([
        'uuid' => Str::orderedUuid(),
        'hash' => sha1('stale'),
        'status' => 'Sending',
        'headers' => [],
        'body' => 'Stuck',
        'content' => ['text' => null, 'html' => 'Stuck'],
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);

    createRecord('Sending'); // recent, not stale

    $result = mailReport()->stale('Sending', 60);

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($stale->id);
});

// --- byHeader ---

it('returns breakdown by header key', function () {
    createRecord(MailHistoryConstants::STATUS_DELIVERED, null, ['Subject' => 'Welcome']);
    createRecord(MailHistoryConstants::STATUS_DELIVERED, null, ['Subject' => 'Welcome']);
    createRecord(MailHistoryConstants::STATUS_BOUNCED, null, ['Subject' => 'Invoice']);

    $result = mailReport()->byHeader('Subject');

    expect($result)->toHaveCount(2);

    $welcome = $result->firstWhere('Welcome', 'Welcome');
    expect($welcome['total'])->toBe(2);
});

// --- contract binding ---

it('resolves via container using the contract', function () {
    $report = app(MailHistoryReport::class);

    expect($report)->toBeInstanceOf(GetMailHistoryReport::class);
});
