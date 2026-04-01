<?php

namespace CleaniqueCoders\MailHistory\Concerns;

trait InteractsWithOpenTracking
{
    public function injectOpenTrackingPixel(string $html): string
    {
        if (! config('mailhistory.tracking.open.enabled', false)) {
            return $html;
        }

        $hash = $this->getMetadataHash();

        if (empty($hash)) {
            return $html;
        }

        $url = route('mailhistory.tracking.open', ['hash' => $hash]);
        $pixel = '<img src="'.htmlspecialchars($url).'" width="1" height="1" alt="" style="display:none" />';

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $pixel.'</body>', $html);
        }

        return $html.$pixel;
    }
}
