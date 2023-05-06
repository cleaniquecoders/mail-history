<?php

namespace CleaniqueCoders\MailHistory\Contracts;

use Symfony\Component\Mime\Email;

interface HashContract
{
    /**
     * \Symfony\Component\Mime\Email $email
     */
    public static function generateHashValue(Email $email): string;
}
