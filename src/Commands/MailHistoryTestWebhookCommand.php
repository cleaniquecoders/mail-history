<?php

namespace CleaniqueCoders\MailHistory\Commands;

use Illuminate\Console\Command;

class MailHistoryTestWebhookCommand extends Command
{
    public $signature = 'mailhistory:test-webhook
        {provider : The webhook provider (mailgun, ses, postmark, sendgrid, resend)}
        {type=delivered : The event type (delivered, opened, clicked, bounced, complained, failed)}
        {--hash= : The mail history hash to use (uses latest record if not provided)}';

    public $description = 'Send a simulated webhook event for testing';

    public function handle(): int
    {
        $provider = $this->argument('provider');
        $type = $this->argument('type');
        $hash = $this->option('hash');

        $providerConfig = config("mailhistory.webhooks.providers.{$provider}");

        if (! $providerConfig) {
            $this->components->error("Unknown provider: {$provider}");

            return self::FAILURE;
        }

        if (! $hash) {
            $model = config('mailhistory.model');
            $latest = $model::latest()->first();

            if (! $latest) {
                $this->components->error('No mail history records found. Provide a --hash option.');

                return self::FAILURE;
            }

            $hash = $latest->hash;
        }

        $model = config('mailhistory.model');
        $mailHistory = $model::where('hash', $hash)->first();

        if (! $mailHistory) {
            $this->components->error("No mail history record found with hash: {$hash}");

            return self::FAILURE;
        }

        $mailHistory->recordEvent($type, [
            'simulated' => true,
            'provider' => $provider,
        ], [
            'provider' => $provider,
        ]);

        $this->components->info("Simulated '{$type}' event recorded for hash: {$hash} (provider: {$provider})");

        return self::SUCCESS;
    }
}
