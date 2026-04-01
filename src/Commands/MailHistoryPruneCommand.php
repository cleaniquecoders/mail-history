<?php

namespace CleaniqueCoders\MailHistory\Commands;

use CleaniqueCoders\MailHistory\Models\MailHistoryEvent;
use Illuminate\Console\Command;

class MailHistoryPruneCommand extends Command
{
    public $signature = 'mailhistory:prune
        {--days= : Number of days to retain (defaults to config value)}
        {--events-only : Only prune events, keep mail history records}';

    public $description = 'Delete old mail history records based on retention policy';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('mailhistory.retention.days', 90));
        $eventsOnly = $this->option('events-only');
        $cutoff = now()->subDays($days);

        $eventModel = config('mailhistory.event-model', MailHistoryEvent::class);
        $eventsDeleted = $eventModel::where('created_at', '<', $cutoff)->delete();

        $this->components->info("Deleted {$eventsDeleted} event(s) older than {$days} days.");

        if (! $eventsOnly) {
            $model = config('mailhistory.model');
            $mailsDeleted = $model::where('created_at', '<', $cutoff)->delete();

            $this->components->info("Deleted {$mailsDeleted} mail history record(s) older than {$days} days.");
        }

        return self::SUCCESS;
    }
}
