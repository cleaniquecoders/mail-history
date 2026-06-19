<?php

namespace CleaniqueCoders\MailHistory\Concerns;

trait InteractsWithClickTracking
{
    use RewritesTrackingHtml;

    public function rewriteUrlsForClickTracking(string $html): string
    {
        if (! config('mailhistory.tracking.click.enabled', false)) {
            return $html;
        }

        return $this->rewriteClickLinks($html, (string) $this->getMetadataHash());
    }
}
