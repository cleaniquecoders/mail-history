<?php

namespace CleaniqueCoders\MailHistory\Actions;

use CleaniqueCoders\MailHistory\Contracts\HashContract;
use Symfony\Component\Mime\Email;

class HashGenerator implements HashContract
{
    /**
     * \Symfony\Component\Mime\Email $email
     */
    public static function generateHashValue(Email $email): string
    {
        return md5(
            implode('.', $email->getTo()).
            implode('.', $email->getFrom()).
            implode('.', $email->getSubject()).
            date('Y-m-d')
        );
    }
}
