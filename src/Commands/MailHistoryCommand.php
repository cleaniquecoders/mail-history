<?php

namespace CleaniqueCoders\MailHistory\Commands;

use Illuminate\Console\Command;

class MailHistoryCommand extends Command
{
    public $signature = 'mailhistory:clear';

    public $description = 'Clear mail history records';

    public function handle(): int
    {
        $confirm = $this->ask('Are you sure want to clear the mail history records?', 'no');

        if (! $confirm) {
            return self::SUCCESS;
        }

        config('mailhistory.model')::truncate();

        $this->components->info('Mail history successfully cleared.');

        return self::SUCCESS;
    }
}
