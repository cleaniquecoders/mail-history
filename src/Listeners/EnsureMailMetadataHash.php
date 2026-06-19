<?php

namespace CleaniqueCoders\MailHistory\Listeners;

use CleaniqueCoders\MailHistory\MailHistory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Str;

/**
 * Guarantees every outgoing message carries an `X-Metadata-hash` header so the
 * store listeners can correlate the later MessageSent event (advancing the
 * record past "Sending") and open/click tracking works — for ALL mail, not just
 * Mailables that opt in via InteractsWithMailMetadata.
 *
 * Registered FIRST on MessageSending (before StoreMessageSending), so the stored
 * row is keyed by the same hash the message header carries downstream. Without
 * it, StoreMessageSending invents a per-record hash that never reaches the
 * message, so MessageSent/tracking can never find the row back.
 */
class EnsureMailMetadataHash
{
    public function handle(MessageSending $event): void
    {
        $headers = $event->message->getHeaders();

        // Leave an existing hash (e.g. set by InteractsWithMailMetadata) intact.
        if (MailHistory::getHashFromHeader($headers->toArray()) !== false) {
            return;
        }

        $headers->addTextHeader('X-Metadata-hash', sha1(Str::orderedUuid()));
    }
}
