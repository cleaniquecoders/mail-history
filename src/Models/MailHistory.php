<?php

namespace CleaniqueCoders\MailHistory\Models;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithHash;
use CleaniqueCoders\MailHistory\Events\MailBounced;
use CleaniqueCoders\MailHistory\Events\MailComplained;
use CleaniqueCoders\MailHistory\Events\MailDelivered;
use CleaniqueCoders\MailHistory\Events\MailHistoryEventReceived;
use CleaniqueCoders\MailHistory\MailHistory as MailHistoryConstants;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $status
 * @property int $id
 * @property string $hash
 */
class MailHistory extends Model
{
    use HasFactory;
    use InteractsWithHash;
    use InteractsWithUuid;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'headers' => 'array',
        'content' => 'array',
        'meta' => 'array',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(config('mailhistory.event-model', MailHistoryEvent::class));
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', MailHistoryConstants::STATUS_DELIVERED);
    }

    public function scopeBounced(Builder $query): Builder
    {
        return $query->where('status', MailHistoryConstants::STATUS_BOUNCED);
    }

    public function scopeOpened(Builder $query): Builder
    {
        return $query->where('status', MailHistoryConstants::STATUS_OPENED);
    }

    public function scopeClicked(Builder $query): Builder
    {
        return $query->where('status', MailHistoryConstants::STATUS_CLICKED);
    }

    public function scopeComplained(Builder $query): Builder
    {
        return $query->where('status', MailHistoryConstants::STATUS_COMPLAINED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', MailHistoryConstants::STATUS_FAILED);
    }

    public function getIsDeliveredAttribute(): bool
    {
        return $this->status === MailHistoryConstants::STATUS_DELIVERED;
    }

    public function getIsOpenedAttribute(): bool
    {
        return $this->status === MailHistoryConstants::STATUS_OPENED;
    }

    public function getIsBouncedAttribute(): bool
    {
        return $this->status === MailHistoryConstants::STATUS_BOUNCED;
    }

    public function recordEvent(string $type, array $payload = [], array $metadata = []): MailHistoryEvent
    {
        $eventModel = config('mailhistory.event-model', MailHistoryEvent::class);

        $event = $eventModel::create(array_merge([
            'uuid' => Str::orderedUuid(),
            'mail_history_id' => $this->id,
            'type' => $type,
            'payload' => $payload,
            'occurred_at' => $metadata['occurred_at'] ?? now(),
            'provider' => $metadata['provider'] ?? null,
            'ip_address' => $metadata['ip_address'] ?? null,
            'user_agent' => $metadata['user_agent'] ?? null,
            'url' => $metadata['url'] ?? null,
        ]));

        $statusMap = [
            'delivered' => MailHistoryConstants::STATUS_DELIVERED,
            'opened' => MailHistoryConstants::STATUS_OPENED,
            'clicked' => MailHistoryConstants::STATUS_CLICKED,
            'bounced' => MailHistoryConstants::STATUS_BOUNCED,
            'complained' => MailHistoryConstants::STATUS_COMPLAINED,
            'failed' => MailHistoryConstants::STATUS_FAILED,
        ];

        // An open implies delivery; a click implies both delivery and open.
        // Backfill implied statuses so the timeline stays complete even
        // when provider webhooks are not configured.
        $implies = [
            'clicked' => ['delivered', 'opened'],
            'opened' => ['delivered'],
        ];

        if (isset($implies[$type])) {
            $occurredAt = $metadata['occurred_at'] ?? now();
            $offset = count($implies[$type]);

            foreach ($implies[$type] as $impliedType) {
                $alreadyRecorded = $this->events()
                    ->where('type', $impliedType)
                    ->exists();

                if (! $alreadyRecorded) {
                    $impliedAt = $occurredAt instanceof \DateTimeInterface
                        ? (clone $occurredAt)->modify("-{$offset} seconds")
                        : now()->modify("-{$offset} seconds");

                    $eventModel::create([
                        'uuid' => Str::orderedUuid(),
                        'mail_history_id' => $this->id,
                        'type' => $impliedType,
                        'payload' => [],
                        'occurred_at' => $impliedAt,
                        'provider' => $metadata['provider'] ?? null,
                        'ip_address' => $metadata['ip_address'] ?? null,
                        'user_agent' => $metadata['user_agent'] ?? null,
                    ]);
                }

                $offset--;
            }
        }

        if (isset($statusMap[$type])) {
            $this->update(['status' => $statusMap[$type]]);
        }

        MailHistoryEventReceived::dispatch($this, $event, $type);

        $specificEvents = [
            'delivered' => MailDelivered::class,
            'bounced' => MailBounced::class,
            'complained' => MailComplained::class,
        ];

        if (isset($specificEvents[$type])) {
            $specificEvents[$type]::dispatch($this, $event);
        }

        return $event;
    }

    public function getTimeline(): Collection
    {
        return $this->events()->orderBy('occurred_at')->get();
    }
}
