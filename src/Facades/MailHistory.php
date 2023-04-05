<?php

namespace CleaniqueCoders\MailHistory\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CleaniqueCoders\MailHistory\MailHistory
 */
class MailHistory extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \CleaniqueCoders\MailHistory\MailHistory::class;
    }
}
