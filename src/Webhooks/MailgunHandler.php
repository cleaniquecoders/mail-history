<?php

namespace CleaniqueCoders\MailHistory\Webhooks;

use CleaniqueCoders\MailHistory\Webhooks\Contracts\WebhookHandler;
use Illuminate\Http\Request;

class MailgunHandler implements WebhookHandler
{
    protected array $eventMap = [
        'delivered' => 'delivered',
        'opened' => 'opened',
        'clicked' => 'clicked',
        'failed' => 'failed',
        'bounced' => 'bounced',
        'complained' => 'complained',
    ];

    public function verify(Request $request): bool
    {
        $signingKey = config('mailhistory.webhooks.providers.mailgun.signing_key');

        if (empty($signingKey)) {
            return false;
        }

        $signature = $request->input('signature', []);
        $timestamp = $signature['timestamp'] ?? '';
        $token = $signature['token'] ?? '';
        $sig = $signature['signature'] ?? '';

        if (empty($timestamp) || empty($token) || empty($sig)) {
            return false;
        }

        $computed = hash_hmac('sha256', $timestamp.$token, $signingKey);

        return hash_equals($computed, $sig);
    }

    public function handle(Request $request): array
    {
        $eventData = $request->input('event-data', []);
        $event = $eventData['event'] ?? '';
        $type = $this->eventMap[$event] ?? null;

        if (! $type) {
            return [];
        }

        $headers = $eventData['message']['headers'] ?? [];
        $userVariables = $eventData['user-variables'] ?? [];

        $hash = $userVariables['hash']
            ?? $headers['X-Metadata-hash']
            ?? $headers['x-metadata-hash']
            ?? null;

        if (! $hash) {
            return [];
        }

        return [[
            'type' => $type,
            'hash' => $hash,
            'payload' => $eventData,
            'occurred_at' => isset($eventData['timestamp'])
                ? date('Y-m-d H:i:s', (int) $eventData['timestamp'])
                : null,
            'ip_address' => $eventData['ip'] ?? null,
            'user_agent' => $eventData['client-info']['user-agent'] ?? null,
            'url' => $eventData['url'] ?? null,
        ]];
    }
}
