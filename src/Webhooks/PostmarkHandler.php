<?php

namespace CleaniqueCoders\MailHistory\Webhooks;

use CleaniqueCoders\MailHistory\Webhooks\Contracts\WebhookHandler;
use Illuminate\Http\Request;

class PostmarkHandler implements WebhookHandler
{
    protected array $eventMap = [
        'Delivery' => 'delivered',
        'Bounce' => 'bounced',
        'SpamComplaint' => 'complained',
        'Open' => 'opened',
        'Click' => 'clicked',
    ];

    public function verify(Request $request): bool
    {
        $token = config('mailhistory.webhooks.providers.postmark.token');

        if (empty($token)) {
            return true;
        }

        $headerToken = $request->header('X-Postmark-Token');

        if (empty($headerToken)) {
            return false;
        }

        return hash_equals($token, $headerToken);
    }

    public function handle(Request $request): array
    {
        $payload = $request->all();
        $recordType = $payload['RecordType'] ?? '';
        $type = $this->eventMap[$recordType] ?? null;

        if (! $type) {
            return [];
        }

        $hash = $payload['Metadata']['hash']
            ?? $this->extractHashFromHeaders($payload['Headers'] ?? [])
            ?? null;

        if (! $hash) {
            return [];
        }

        return [[
            'type' => $type,
            'hash' => $hash,
            'payload' => $payload,
            'occurred_at' => $payload['DeliveredAt']
                ?? $payload['BouncedAt']
                ?? $payload['ReceivedAt']
                ?? null,
            'ip_address' => $payload['Geo']['IP'] ?? null,
            'user_agent' => $payload['UserAgent'] ?? null,
            'url' => $payload['OriginalLink'] ?? null,
        ]];
    }

    protected function extractHashFromHeaders(array $headers): ?string
    {
        foreach ($headers as $header) {
            if (($header['Name'] ?? '') === 'X-Metadata-hash') {
                return $header['Value'] ?? null;
            }
        }

        return null;
    }
}
