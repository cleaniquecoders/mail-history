<?php

namespace CleaniqueCoders\MailHistory\Exceptions;

use CleaniqueCoders\MailHistory\Contracts\HashContract;
use Exception;

class MailHistoryException extends Exception
{
    public static function throwIfHashContractMissing()
    {
        if (! in_array(HashContract::class, class_implements(config('mailhistory.hash-generator')))) {
            throw new self(
                config('mailhistory.hash-generator')." must implements the \CleaniqueCoders\MailHistory\Contracts\HashContract contract"
            );
        }
    }
}
