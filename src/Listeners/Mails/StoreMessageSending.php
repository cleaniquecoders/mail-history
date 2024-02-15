<?php

namespace CleaniqueCoders\MailHistory\Listeners\Mails;

use CleaniqueCoders\MailHistory\MailHistory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Str;

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
        $headers = $event->message->getHeaders()->toArray();
        $hash = MailHistory::getHashFromHeader($headers);

        if (! $hash) {
            $hash = sha1(Str::orderedUuid());
        }

        // don't recreate record
        if (config('mailhistory.model')::where('hash', $hash)->exists()) {
            return;
        }

        $message = $event->message;

        config('mailhistory.model')::create([
            'uuid' => Str::orderedUuid(),
            'hash' => $hash,
            'status' => MailHistory::STATUS_SENDING,
            'headers' => $headers,
            'body' => $message->getBody()->bodyToString(),
            'content' => [
                'text' => $message->getTextBody(),
                'text-charset' => $message->getTextCharset(),
                'html' => $message->getHtmlBody(),
                'html-charset' => $message->getHtmlCharset(),
            ],
            'meta' => [
                'origin' => MailHistory::ORIGIN_MAIL,
            ],
        ]);
    }
}
