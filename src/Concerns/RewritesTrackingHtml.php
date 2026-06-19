<?php

namespace CleaniqueCoders\MailHistory\Concerns;

use Illuminate\Support\Facades\Crypt;

/**
 * Pure HTML transforms for self-hosted open/click tracking, keyed by an explicit
 * metadata hash.
 *
 * Shared by the Mailable concerns (InteractsWithOpenTracking /
 * InteractsWithClickTracking — hash from the Mailable's metadata) and the
 * InjectMailTracking listener (hash from the X-Metadata-hash header), so the
 * injection logic lives in exactly one place.
 */
trait RewritesTrackingHtml
{
    protected function injectOpenPixel(string $html, string $hash): string
    {
        if ($hash === '' || $html === '') {
            return $html;
        }

        $url = route('mailhistory.tracking.open', ['hash' => $hash]);
        $pixel = '<img src="'.htmlspecialchars($url).'" width="1" height="1" alt="" style="display:none" />';

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $pixel.'</body>', $html);
        }

        return $html.$pixel;
    }

    protected function rewriteClickLinks(string $html, string $hash): string
    {
        if ($hash === '' || $html === '') {
            return $html;
        }

        $excludePatterns = (array) config('mailhistory.tracking.click.exclude_patterns', ['*unsubscribe*']);

        return (string) preg_replace_callback(
            '/<a\s([^>]*?)href=["\']([^"\']+)["\']/i',
            function ($matches) use ($hash, $excludePatterns) {
                $attributes = $matches[1];
                $originalUrl = $matches[2];

                // Skip non-http links.
                if (preg_match('/^(mailto:|tel:|#|javascript:)/i', $originalUrl)) {
                    return $matches[0];
                }

                // Skip excluded patterns (e.g. unsubscribe links).
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
