<?php

use CleaniqueCoders\MailHistory\Listeners\Mails\StoreMessageSending as StoreMailMessageSending;
use CleaniqueCoders\MailHistory\Listeners\Mails\StoreMessageSent as StoreMailMessageSent;
use CleaniqueCoders\MailHistory\Listeners\Notifications\StoreMailSending as StoreNotificationMessageSending;
use CleaniqueCoders\MailHistory\Listeners\Notifications\StoreMailSent as StoreNotificationMessageSent;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;

return [
    'enabled' => env('MAILHISTORY_ENABLED', true),

    'model' => MailHistory::class,

    'user-model' => '\App\Models\User',

    'events' => [
        MessageSending::class => [
            StoreMailMessageSending::class,
        ],
        MessageSent::class => [
            StoreMailMessageSent::class,
        ],
        NotificationSending::class => [
            StoreNotificationMessageSending::class,
        ],
        NotificationSent::class => [
            StoreNotificationMessageSent::class,
        ],
    ],
];
