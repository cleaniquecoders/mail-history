<?php

namespace CleaniqueCoders\MailHistory\Listeners\Notifications;

use CleaniqueCoders\MailHistory\MailHistory;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Str;

class StoreMailSending
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
    public function handle(NotificationSending $event): void
    {
        if ($event->channel != 'mail') {
            return;
        }

        if (! method_exists($event->notification, 'getMail')) {
            return;
        }

        /** \Illuminate\Mail\Mailable */
        $mail = $event->notification->getMail();

        $html = $mail->render();

        $to = $event->notifiable->email;

        $from = null;
        $subject = null;

        if (method_exists($mail, 'envelope')) {
            $envelop = $mail->envelope();

            $from = $envelop->from instanceof Address ? $envelop->from->address : $envelop->from;

            $subject = $envelop->subject;
        }

        if(empty($from)) {
            $from = config('mail.from.name') . ' <' . config('mail.from.address') . '>';
        }

        config('mailhistory.model')::create([
            'uuid' => Str::orderedUuid(),
            'hash' => $mail->getMetadataHash(),
            'status' => MailHistory::STATUS_SENDING,
            'headers' => [
                'From: '.$from,
                'To: '.$to,
                'Subject: '.$subject,
                'X-Metadata-hash: '.$mail->getMetadataHash(),
            ],
            'body' => $html,
            'content' => [
                'text' => null,
                'text-charset' => null,
                'html' => $html,
                'html-charset' => 'utf-8',
            ],
            'meta' => [
                'origin' => MailHistory::ORIGIN_NOTIFICATION,
            ],
        ]);
    }
}
