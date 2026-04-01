<?php

namespace CleaniqueCoders\MailHistory\Webhooks;

use CleaniqueCoders\MailHistory\Webhooks\Contracts\WebhookHandler;
use Illuminate\Http\Request;

class SesHandler implements WebhookHandler
{
    protected array $eventMap = [
        'Delivery' => 'delivered',
        'Bounce' => 'bounced',
        'Complaint' => 'complained',
        'Open' => 'opened',
        'Click' => 'clicked',
        'Reject' => 'failed',
    ];

    public function verify(Request $request): bool
    {
        $payload = $request->all();
        $type = $payload['Type'] ?? '';

        if ($type === 'SubscriptionConfirmation') {
            return true;
        }

        if ($type !== 'Notification') {
            return false;
        }

        $requiredKeys = ['Message', 'MessageId', 'Timestamp', 'TopicArn', 'Type', 'Signature', 'SigningCertURL'];

        foreach ($requiredKeys as $key) {
            if (! isset($payload[$key])) {
                return false;
            }
        }

        $certUrl = $payload['SigningCertURL'];

        if (! preg_match('/^https:\/\/sns\.[a-z0-9-]+\.amazonaws\.com\//', $certUrl)) {
            return false;
        }

        return true;
    }

    public function handle(Request $request): array
    {
        $payload = $request->all();

        if (($payload['Type'] ?? '') === 'SubscriptionConfirmation') {
            if (isset($payload['SubscribeURL'])) {
                file_get_contents($payload['SubscribeURL']);
            }

            return [];
        }

        $message = json_decode($payload['Message'] ?? '{}', true);
        $eventType = $message['eventType'] ?? $message['notificationType'] ?? '';
        $type = $this->eventMap[$eventType] ?? null;

        if (! $type) {
            return [];
        }

        $mail = $message['mail'] ?? [];
        $hash = $this->extractHash($mail);

        if (! $hash) {
            return [];
        }

        return [[
            'type' => $type,
            'hash' => $hash,
            'payload' => $message,
            'occurred_at' => $message['mail']['timestamp'] ?? null,
            'ip_address' => null,
            'user_agent' => null,
            'url' => $message['click']['link'] ?? null,
        ]];
    }

    protected function extractHash(array $mail): ?string
    {
        $headers = $mail['headers'] ?? [];

        foreach ($headers as $header) {
            if (($header['name'] ?? '') === 'X-Metadata-hash') {
                return $header['value'] ?? null;
            }
        }

        $tags = $mail['tags'] ?? [];

        return $tags['hash'][0] ?? null;
    }
}
