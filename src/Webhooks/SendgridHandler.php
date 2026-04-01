<?php

namespace CleaniqueCoders\MailHistory\Webhooks;

use CleaniqueCoders\MailHistory\Webhooks\Contracts\WebhookHandler;
use Illuminate\Http\Request;

class SendgridHandler implements WebhookHandler
{
    protected array $eventMap = [
        'delivered' => 'delivered',
        'open' => 'opened',
        'click' => 'clicked',
        'bounce' => 'bounced',
        'spamreport' => 'complained',
        'dropped' => 'failed',
    ];

    public function verify(Request $request): bool
    {
        $verificationKey = config('mailhistory.webhooks.providers.sendgrid.verification_key');

        if (empty($verificationKey)) {
            return true;
        }

        $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
        $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');

        if (empty($signature) || empty($timestamp)) {
            return false;
        }

        $payload = $timestamp.$request->getContent();

        try {
            $publicKey = openssl_pkey_get_public(base64_decode($verificationKey));

            if (! $publicKey) {
                return false;
            }

            $decodedSignature = base64_decode($signature);

            return openssl_verify($payload, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
        } catch (\Throwable) {
            return false;
        }
    }

    public function handle(Request $request): array
    {
        $events = $request->all();

        if (! is_array($events)) {
            return [];
        }

        // SendGrid sends an array of events at the top level
        if (! isset($events[0])) {
            $events = [$events];
        }

        $results = [];

        foreach ($events as $eventData) {
            $event = $eventData['event'] ?? '';
            $type = $this->eventMap[$event] ?? null;

            if (! $type) {
                continue;
            }

            $hash = $eventData['hash']
                ?? $eventData['unique_args']['hash']
                ?? null;

            if (! $hash) {
                continue;
            }

            $results[] = [
                'type' => $type,
                'hash' => $hash,
                'payload' => $eventData,
                'occurred_at' => isset($eventData['timestamp'])
                    ? date('Y-m-d H:i:s', (int) $eventData['timestamp'])
                    : null,
                'ip_address' => $eventData['ip'] ?? null,
                'user_agent' => $eventData['useragent'] ?? null,
                'url' => $eventData['url'] ?? null,
            ];
        }

        return $results;
    }
}
