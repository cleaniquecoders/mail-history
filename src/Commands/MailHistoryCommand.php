<?php

namespace CleaniqueCoders\MailHistory\Commands;

use Illuminate\Console\Command;

class MailHistoryCommand extends Command
{
    public $signature = 'mailhistory';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
