<?php

namespace CleaniqueCoders\MailHistory;

class MailHistory
{
    public const STATUS_SENDING = 'Sending';

    public const STATUS_SENT = 'Sent';

    public const ORIGIN_MAIL = 'Mail';

    public const ORIGIN_NOTIFICATION = 'Notification';

    public static function getHashFromHeader(array $headers): string|bool
    {
        $filteredArray = array_filter($headers, function ($var) {
            return preg_match('/^X-Metadata-hash:/', $var);
        });

        if (empty($filteredArray)) {
            return false;
        }

        // Getting the hash value
        $line = reset($filteredArray); // Getting the first matching line
        $hash = substr($line, strpos($line, ':') + 2); // Extracting the hash value

        if (empty($hash)) {
            return false;
        }

        return $hash;
    }
}
