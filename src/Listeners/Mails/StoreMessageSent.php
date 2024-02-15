<?php

namespace CleaniqueCoders\MailHistory\Listeners\Mails;

use CleaniqueCoders\MailHistory\MailHistory;
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
        $headers = $event->message->getHeaders()->toArray();
        $hash = MailHistory::getHashFromHeader($headers);

        if (! $hash) {
            return;
        }

        if (! config('mailhistory.model')::where('hash', $hash)->where('status', 'Sending')->exists()) {
            return;
        }

        config('mailhistory.model')::whereHash(
            $hash
        )->update(['status' => MailHistory::STATUS_SENT]);
    }
}
