<?php

namespace CleaniqueCoders\MailHistory\Listeners;

use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Mail\Events\MessageSent;

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
        config('mailhistory.model')::whereHash(
            config('mailhistory.model')::generateHashValue(
                $event->message->getHeaders()->toArray()
            )
        )->update(['status' => 'Sent']);
    }
}
