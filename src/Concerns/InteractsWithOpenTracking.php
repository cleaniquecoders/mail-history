<?php

namespace CleaniqueCoders\MailHistory\Concerns;

trait InteractsWithOpenTracking
{
    use RewritesTrackingHtml;

    public function injectOpenTrackingPixel(string $html): string
    {
        if (! config('mailhistory.tracking.open.enabled', false)) {
            return $html;
        }

        return $this->injectOpenPixel($html, (string) $this->getMetadataHash());
    }
}
