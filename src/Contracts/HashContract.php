<?php

namespace CleaniqueCoders\MailHistory\Contracts;

interface HashContract
{
    public static function generateHashValue(array $value = []): string;
}
