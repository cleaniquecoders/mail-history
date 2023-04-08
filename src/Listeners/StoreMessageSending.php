<?php

namespace CleaniqueCoders\MailHistory\Listeners;

use CleaniqueCoders\MailHistory\Exceptions\MailHistoryException;
use Illuminate\Mail\Events\MessageSending;

class StoreMessageSending
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
    public function handle(MessageSending $event): void
    {
        MailHistoryException::throwIfHashContractMissing();

        config('mailhistory.model')::create([
            'hash' => config('mailhistory.model')::generateHashValue(
                $event->message->getHeaders()->toArray()
            ),
            'status' => 'Sending',
            'headers' => $event->message->getHeaders()->toArray(),
            'body' => $event->message->getBody()->bodyToString(),
            'content' => [
                'text' => $event->message->getTextBody(),
                'text-charset' => $event->message->getTextCharset(),
                'html' => $event->message->getHtmlBody(),
                'html-charset' => $event->message->getHtmlCharset(),
            ],
        ]);
    }
}
