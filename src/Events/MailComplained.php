<?php

namespace CleaniqueCoders\MailHistory\Events;

use CleaniqueCoders\MailHistory\Models\MailHistory;
use CleaniqueCoders\MailHistory\Models\MailHistoryEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailComplained
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MailHistory $mailHistory,
        public readonly MailHistoryEvent $event,
    ) {}
}
