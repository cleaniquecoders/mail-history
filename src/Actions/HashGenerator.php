<?php

namespace CleaniqueCoders\MailHistory\Actions;

use CleaniqueCoders\MailHistory\Contracts\HashContract;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

class HashGenerator implements HashContract
{
    /**
     * \Illuminate\Mail\Events\MessageSending|\Illuminate\Mail\Events\MessageSent $email
     */
    public static function generateHashValue(MessageSending|MessageSent $message): string
    {
        $values = [date('Y-m-d')];

        $email = $message->message;
        $to = $email->getTo();
        $from = $email->getFrom();
        foreach ($to as $key => $value) {
            $values[] = $value->getEncodedAddress();
        }
        foreach ($from as $key => $value) {
            $values[] = $value->getEncodedAddress();
        }
        $values[] = $email->getSubject();

        return sha1(implode('.', $values));
    }
}
