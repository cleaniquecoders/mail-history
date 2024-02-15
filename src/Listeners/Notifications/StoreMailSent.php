<?php

namespace CleaniqueCoders\MailHistory\Listeners\Notifications;

use Illuminate\Notifications\Events\NotificationSent;

class StoreMailSent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationSent $event): void
    {
        if ($event->channel != 'mail') {
            return;
        }

        if (! method_exists($event->notification, 'getMail')) {
            return;
        }

        /** \Illuminate\Mail\Mailable */
        $mail = $event->notification->getMail();

        config('mailhistory.model')::whereHash(
            $mail->getMetadataHash()
        )->update(['status' => 'Sent']);
    }
}
