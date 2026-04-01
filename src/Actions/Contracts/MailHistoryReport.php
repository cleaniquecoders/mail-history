<?php

namespace CleaniqueCoders\MailHistory\Actions\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface MailHistoryReport
{
    /**
     * Get summary counts and rates for all statuses.
     *
     * Returns: ['statuses' => [...], 'total' => int, 'rates' => [...]]
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array;

    /**
     * Get daily/weekly/monthly trend data.
     *
     * Returns: Collection of ['date' => string, 'total' => int, 'delivered' => int, ...]
     */
    public function trends(string $interval = 'daily', ?Carbon $from = null, ?Carbon $to = null): Collection;

    /**
     * Get breakdown by provider.
     *
     * Returns: Collection of ['provider' => string, 'total' => int, 'delivered' => int, ...]
     */
    public function byProvider(?Carbon $from = null, ?Carbon $to = null): Collection;

    /**
     * Get the full event timeline for a specific mail history record.
     */
    public function timeline(int|string $hashOrId): Collection;

    /**
     * Get top recipients by a specific event type (e.g., bounced, complained).
     */
    public function topRecipients(string $status, int $limit = 10, ?Carbon $from = null, ?Carbon $to = null): Collection;

    /**
     * Get recent activity (latest events across all records).
     */
    public function recentActivity(int $limit = 50): Collection;

    /**
     * Get records stuck in a status for longer than expected.
     */
    public function stale(string $status = 'Sending', int $olderThanMinutes = 60): Collection;

    /**
     * Get per-status counts grouped by a mail attribute extracted from headers.
     *
     * Useful for per-mailable or per-subject breakdowns.
     */
    public function byHeader(string $headerKey, ?Carbon $from = null, ?Carbon $to = null): Collection;
}
