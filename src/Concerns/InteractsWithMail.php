<?php

namespace CleaniqueCoders\MailHistory\Concerns;

use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;

trait InteractsWithMail
{
    protected $mail;

    public function getMail(): Mailable|MailMessage
    {
        return $this->mail;
    }

    public function setMail(Mailable|MailMessage $mail): self
    {
        $this->mail = $mail;

        return $this;
    }
}
