<?php

namespace CleaniqueCoders\MailHistory\Listeners;

use CleaniqueCoders\MailHistory\Concerns\RewritesTrackingHtml;
use CleaniqueCoders\MailHistory\MailHistory;
use Illuminate\Mail\Events\MessageSending;

/**
 * Injects the open-tracking pixel and rewrites links for click tracking into
 * outgoing HTML mail, keyed by the message's `X-Metadata-hash` header.
 *
 * Runs at MessageSending, AFTER StoreMessageSending has recorded the row, so the
 * stored body stays the original and tracking is applied only to what the
 * transport actually sends. Self-hosted — works with any transport (including
 * sendmail). Delivered/bounced/complained still require a webhook-capable
 * provider.
 *
 * Auto-registered by the service provider when open or click tracking is
 * enabled, so apps no longer need to hand-roll this listener.
 */
class InjectMailTracking
{
    use RewritesTrackingHtml;

    public function handle(MessageSending $event): void
    {
        $openEnabled = (bool) config('mailhistory.tracking.open.enabled', false);
        $clickEnabled = (bool) config('mailhistory.tracking.click.enabled', false);

        if (! $openEnabled && ! $clickEnabled) {
            return;
        }

        $email = $event->message;
        $html = $email->getHtmlBody();

        if (! is_string($html) || $html === '') {
            return;
        }

        $hash = MailHistory::getHashFromHeader($email->getHeaders()->toArray());

        if (! $hash) {
            return;
        }

        // Rewrite links first, then append the pixel (so the pixel <img> is
        // never itself rewritten as a tracked link).
        if ($clickEnabled) {
            $html = $this->rewriteClickLinks($html, $hash);
        }

        if ($openEnabled) {
            $html = $this->injectOpenPixel($html, $hash);
        }

        $email->html($html);
    }
}
