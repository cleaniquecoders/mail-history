<?php

namespace CleaniqueCoders\MailHistory\Concerns;

use Illuminate\Support\Str;

trait InteractsWithMailMetadata
{
    public function configureMetadataHash(): self
    {
        if (empty($this->getMetadataHash())) {
            $this->setMetadataHash();
        }

        $this->configureProviderHeaders();

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadataHash($value = null): self
    {
        $this->metadata('hash', sha1(
            empty($value) ? Str::orderedUuid() : $value
        ));

        return $this;
    }

    public function getMetadataHash(): string
    {
        return data_get($this->metadata, 'hash', '');
    }

    public function configureProviderHeaders(): self
    {
        $hash = $this->getMetadataHash();

        if (empty($hash)) {
            return $this;
        }

        $driver = config('mail.default');

        match ($driver) {
            'mailgun' => $this->withSymfonyMessage(function ($message) use ($hash) {
                $message->getHeaders()->addTextHeader('X-Mailgun-Variables', json_encode(['hash' => $hash]));
            }),
            'ses' => $this->withSymfonyMessage(function ($message) use ($hash) {
                $message->getHeaders()->addTextHeader('X-SES-MESSAGE-TAGS', "hash={$hash}");
            }),
            'postmark' => $this->withSymfonyMessage(function ($message) use ($hash) {
                $message->getHeaders()->addTextHeader('X-PM-Metadata-hash', $hash);
            }),
            'sendgrid', 'smtp' => $this->withSymfonyMessage(function ($message) use ($hash) {
                // SendGrid reads unique args from X-SMTPAPI header
                $existing = $message->getHeaders()->get('X-SMTPAPI');
                $smtpApi = $existing ? json_decode($existing->getBody(), true) : [];
                $smtpApi['unique_args']['hash'] = $hash;
                if ($existing) {
                    $message->getHeaders()->remove('X-SMTPAPI');
                }
                $message->getHeaders()->addTextHeader('X-SMTPAPI', json_encode($smtpApi));
            }),
            'resend' => $this->withSymfonyMessage(function ($message) use ($hash) {
                $message->getHeaders()->addTextHeader('X-Entity-Ref-ID', $hash);
            }),
            default => $this,
        };

        return $this;
    }
}
