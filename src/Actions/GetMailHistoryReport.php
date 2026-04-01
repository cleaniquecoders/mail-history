<?php

namespace CleaniqueCoders\MailHistory\Actions;

use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;
use CleaniqueCoders\MailHistory\MailHistory as MailHistoryConstants;
use CleaniqueCoders\MailHistory\Models\MailHistoryEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetMailHistoryReport implements MailHistoryReport
{
    protected function model(): string
    {
        return config('mailhistory.model');
    }

    protected function eventModel(): string
    {
        return config('mailhistory.event-model', MailHistoryEvent::class);
    }

    protected function statuses(): array
    {
        return [
            MailHistoryConstants::STATUS_SENDING,
            MailHistoryConstants::STATUS_SENT,
            MailHistoryConstants::STATUS_DELIVERED,
            MailHistoryConstants::STATUS_OPENED,
            MailHistoryConstants::STATUS_CLICKED,
            MailHistoryConstants::STATUS_BOUNCED,
            MailHistoryConstants::STATUS_COMPLAINED,
            MailHistoryConstants::STATUS_FAILED,
        ];
    }

    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = $this->model()::query();
        $query = $this->applyDateRange($query, $from, $to);

        $total = $query->count();
        $statuses = [];

        foreach ($this->statuses() as $status) {
            $q = $this->model()::query()->where('status', $status);
            $q = $this->applyDateRange($q, $from, $to);
            $statuses[$status] = $q->count();
        }

        $rates = [];
        if ($total > 0) {
            foreach ($statuses as $status => $count) {
                $rates[$status] = round(($count / $total) * 100, 2);
            }
        }

        return [
            'statuses' => $statuses,
            'total' => $total,
            'rates' => $rates,
        ];
    }

    public function trends(string $interval = 'daily', ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();

        $driver = DB::connection()->getDriverName();

        $periodExpression = match ($driver) {
            'sqlite' => match ($interval) {
                'weekly' => "strftime('%Y-W%W', created_at)",
                'monthly' => "strftime('%Y-%m', created_at)",
                default => "strftime('%Y-%m-%d', created_at)",
            },
            default => match ($interval) {
                'weekly' => "DATE_FORMAT(created_at, '%x-W%v')",
                'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
            },
        };

        $query = $this->model()::query()
            ->select(
                DB::raw("{$periodExpression} as period"),
                DB::raw('COUNT(*) as total'),
            )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('period')
            ->orderBy('period');

        foreach ($this->statuses() as $status) {
            $query->addSelect(
                DB::raw("SUM(CASE WHEN status = '{$status}' THEN 1 ELSE 0 END) as `".strtolower($status).'`')
            );
        }

        return $query->get();
    }

    public function byProvider(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = $this->eventModel()::query()
            ->select(
                'provider',
                DB::raw('COUNT(*) as total'),
            )
            ->whereNotNull('provider');

        $query = $this->applyDateRange($query, $from, $to);

        $eventTypes = ['delivered', 'opened', 'clicked', 'bounced', 'complained', 'failed'];

        foreach ($eventTypes as $type) {
            $query->addSelect(
                DB::raw("SUM(CASE WHEN type = '{$type}' THEN 1 ELSE 0 END) as `{$type}`")
            );
        }

        return $query->groupBy('provider')->orderByDesc('total')->get();
    }

    public function timeline(int|string $hashOrId): Collection
    {
        $mailHistory = is_numeric($hashOrId)
            ? $this->model()::findOrFail($hashOrId)
            : $this->model()::where('hash', $hashOrId)->firstOrFail();

        return $mailHistory->events()
            ->orderBy('occurred_at')
            ->get()
            ->map(fn ($event) => [
                'type' => $event->type,
                'provider' => $event->provider,
                'occurred_at' => $event->occurred_at,
                'ip_address' => $event->ip_address,
                'user_agent' => $event->user_agent,
                'url' => $event->url,
            ]);
    }

    public function topRecipients(string $status, int $limit = 10, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = $this->model()::query()
            ->where('status', $status);

        $query = $this->applyDateRange($query, $from, $to);

        return $query->get()
            ->map(function ($record) {
                $headers = $record->headers ?? [];

                // Headers can be an array of "Key: Value" strings or an associative array
                $to = null;
                if (is_array($headers)) {
                    foreach ($headers as $key => $value) {
                        if ($key === 'To' || (is_string($value) && str_starts_with($value, 'To:'))) {
                            $to = is_string($key) ? $value : trim(substr($value, 3));
                            break;
                        }
                    }
                }

                return $to;
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->map(fn ($count, $email) => [
                'recipient' => $email,
                'count' => $count,
            ])
            ->values();
    }

    public function recentActivity(int $limit = 50): Collection
    {
        return $this->eventModel()::query()
            ->with('mailHistory')
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn ($event) => [
                'mail_history_id' => $event->mail_history_id,
                'hash' => $event->mailHistory?->hash,
                'type' => $event->type,
                'provider' => $event->provider,
                'occurred_at' => $event->occurred_at,
                'ip_address' => $event->ip_address,
                'user_agent' => $event->user_agent,
                'url' => $event->url,
            ]);
    }

    public function stale(string $status = 'Sending', int $olderThanMinutes = 60): Collection
    {
        return $this->model()::query()
            ->where('status', $status)
            ->where('updated_at', '<', now()->subMinutes($olderThanMinutes))
            ->orderBy('updated_at')
            ->get();
    }

    public function byHeader(string $headerKey, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = $this->model()::query();
        $query = $this->applyDateRange($query, $from, $to);

        return $query->get()
            ->groupBy(function ($record) use ($headerKey) {
                $headers = $record->headers ?? [];

                // Support both associative and "Key: Value" string arrays
                if (isset($headers[$headerKey])) {
                    return $headers[$headerKey];
                }

                foreach ($headers as $value) {
                    if (is_string($value) && str_starts_with($value, "{$headerKey}:")) {
                        return trim(substr($value, strlen($headerKey) + 1));
                    }
                }

                return 'Unknown';
            })
            ->map(function (Collection $records, string $groupKey) {
                $statusCounts = $records->countBy('status')->toArray();

                return array_merge(
                    [$groupKey => $groupKey],
                    ['total' => $records->count()],
                    $statusCounts,
                );
            })
            ->sortByDesc('total')
            ->values();
    }

    protected function applyDateRange($query, ?Carbon $from, ?Carbon $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }
}
