<?php

namespace CleaniqueCoders\MailHistory\Commands;

use CleaniqueCoders\MailHistory\Mail\WelcomeMail;
use CleaniqueCoders\MailHistory\Notifications\WelcomeNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailHistoryTestCommand extends Command
{
    public $signature = 'mailhistory:test {email}
        {type : Test mail or notification}
        {--queue= : Type of queue - default, mail, notification, etc}';

    public $description = 'Test mail history';

    public function handle(): int
    {
        $type = $this->argument('type');
        if (! in_array($type, ['mail', 'notification'])) {
            $this->components->alert('Only mail or notification type are allowed.');

            return self::FAILURE;
        }

        $type = 'send'.ucfirst(strtolower($type));

        if (! method_exists($this, $type)) {
            $this->components->alert('Unknown '.$type.' method.');

            return self::FAILURE;
        }

        $this->{$type}();

        return self::SUCCESS;
    }

    private function sendMail()
    {
        $email = new WelcomeMail;

        if ($this->option('queue')) {
            Mail::to($this->argument('email'))
                ->queue(
                    $email->onQueue($this->option('queue'))
                );
        } else {
            Mail::to($this->argument('email'))->send($email);
        }

        $this->components->info('Mail successfully sent.');
    }

    private function sendNotification()
    {
        $email = $this->argument('email');

        $user = config('mailhistory.user-model')::where('email', $email)->firstOrFail();

        $notification = new WelcomeNotification;

        $user->notify(
            $this->option('queue') ? $notification->onQueue($this->option('queue')) : $notification
        );

        $this->components->info('Notification successfully sent.');
    }
}
