<?php

use CleaniqueCoders\MailHistory\Actions\HashGenerator;
use CleaniqueCoders\MailHistory\Listeners\StoreMessageSending;
use CleaniqueCoders\MailHistory\Listeners\StoreMessageSent;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

return [
    'enabled' => env('MAILHISTORY_ENABLED', true),

    'model' => MailHistory::class,

    'hash-generator' => HashGenerator::class,

    'events' => [
        MessageSending::class => [
            StoreMessageSending::class,
        ],
        MessageSent::class => [
            StoreMessageSent::class,
        ],
    ],
];
