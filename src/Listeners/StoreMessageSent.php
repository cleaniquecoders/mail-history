<?php

namespace CleaniqueCoders\MailHistory\Listeners;

use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;

class StoreMessageSent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        MailHistory::whereHash(
            MailHistory::generateHashValue(
                $event->message->getHeaders()->toArray()
            )
        )->update(['status' => 'Sent']);
    }
}
