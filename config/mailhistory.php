<?php

return [
    'enabled' => env('MAILHISTORY_ENABLED', true),

    'model' => \CleaniqueCoders\MailHistory\Models\MailHistory::class,

    'events' => [
        \Illuminate\Mail\Events\MessageSending::class => [
            \CleaniqueCoders\MailHistory\Listeners\StoreMessageSending::class,
        ],
        \Illuminate\Mail\Events\MessageSent::class => [
            \CleaniqueCoders\MailHistory\Listeners\StoreMessageSent::class,
        ],
    ],
];
