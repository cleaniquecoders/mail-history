<?php

namespace CleaniqueCoders\MailHistory\Commands;

use CleaniqueCoders\MailHistory\MailHistory;
use Illuminate\Console\Command;

class MailHistoryStatsCommand extends Command
{
    public $signature = 'mailhistory:stats {--days=30 : Number of days to show stats for}';

    public $description = 'Show email delivery statistics';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $model = config('mailhistory.model');
        $since = now()->subDays($days);

        $statuses = [
            MailHistory::STATUS_SENDING,
            MailHistory::STATUS_SENT,
            MailHistory::STATUS_DELIVERED,
            MailHistory::STATUS_OPENED,
            MailHistory::STATUS_CLICKED,
            MailHistory::STATUS_BOUNCED,
            MailHistory::STATUS_COMPLAINED,
            MailHistory::STATUS_FAILED,
        ];

        $rows = [];
        $total = 0;

        foreach ($statuses as $status) {
            $count = $model::where('status', $status)
                ->where('created_at', '>=', $since)
                ->count();
            $rows[] = [$status, $count];
            $total += $count;
        }

        $rows[] = ['<info>Total</info>', "<info>{$total}</info>"];

        $this->components->info("Mail History Stats (last {$days} days)");

        $this->table(['Status', 'Count'], $rows);

        return self::SUCCESS;
    }
}
