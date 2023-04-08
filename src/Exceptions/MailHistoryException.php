<?php

namespace CleaniqueCoders\MailHistory\Exceptions;

use Exception;

class MailHistoryException extends Exception
{
    public static function throwIfHashContractMissing()
    {
        if(! in_array(HashContract::class, class_implements(config('mailhistory.model')))) {
            throw new self(
                config('mailhistory.model') . " must implements the \CleaniqueCoders\MailHistory\Contracts\HashContract contract"
            );
        }
    }
}
