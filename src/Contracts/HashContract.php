<?php

namespace CleaniqueCoders\MailHistory\Contracts;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

interface HashContract
{
    /**
     * \Illuminate\Mail\Events\MessageSending|\Illuminate\Mail\Events\MessageSent $email
     */
    public static function generateHashValue(MessageSending|MessageSent $message): string;
}
