<?php

namespace CleaniqueCoders\MailHistory\Concerns;

use Illuminate\Support\Facades\Crypt;

trait InteractsWithClickTracking
{
    public function rewriteUrlsForClickTracking(string $html): string
    {
        if (! config('mailhistory.tracking.click.enabled', false)) {
            return $html;
        }

        $hash = $this->getMetadataHash();

        if (empty($hash)) {
            return $html;
        }

        $excludePatterns = config('mailhistory.tracking.click.exclude_patterns', ['*unsubscribe*']);

        return preg_replace_callback(
            '/<a\s([^>]*?)href=["\']([^"\']+)["\']/i',
            function ($matches) use ($hash, $excludePatterns) {
                $attributes = $matches[1];
                $originalUrl = $matches[2];

                // Skip non-http links
                if (preg_match('/^(mailto:|tel:|#|javascript:)/i', $originalUrl)) {
                    return $matches[0];
                }

                // Skip excluded patterns
                foreach ($excludePatterns as $pattern) {
                    if (fnmatch($pattern, $originalUrl, FNM_CASEFOLD)) {
                        return $matches[0];
                    }
                }

                $trackingUrl = route('mailhistory.tracking.click', [
                    'hash' => $hash,
                    'url' => Crypt::encryptString($originalUrl),
                ]);

                return '<a '.$attributes.'href="'.htmlspecialchars($trackingUrl).'"';
            },
            $html
        );
    }
}
