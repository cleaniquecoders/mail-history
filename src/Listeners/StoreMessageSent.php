<?php

namespace CleaniqueCoders\MailHistory\Listeners;

use CleaniqueCoders\MailHistory\Exceptions\MailHistoryException;
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
        MailHistoryException::throwIfHashContractMissing();

        config('mailhistory.model')::whereHash(
            config('mailhistory.model')::generateHashValue(
                $event->message->getHeaders()->toArray()
            )
        )->update(['status' => 'Sent']);
    }
}
