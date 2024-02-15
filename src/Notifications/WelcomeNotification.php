<?php

namespace CleaniqueCoders\MailHistory\Notifications;

use App\Mail\WelcomeMail;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use InteractsWithMail, Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->setMail(
            (new WelcomeMail)
        );
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable
    {
        return $this->getMail()->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
