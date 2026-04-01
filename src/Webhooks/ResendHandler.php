<?php

namespace CleaniqueCoders\MailHistory\Webhooks;

use CleaniqueCoders\MailHistory\Webhooks\Contracts\WebhookHandler;
use Illuminate\Http\Request;

class ResendHandler implements WebhookHandler
{
    protected array $eventMap = [
        'email.delivered' => 'delivered',
        'email.opened' => 'opened',
        'email.clicked' => 'clicked',
        'email.bounced' => 'bounced',
        'email.complained' => 'complained',
    ];

    public function verify(Request $request): bool
    {
        $secret = config('mailhistory.webhooks.providers.resend.signing_secret');

        if (empty($secret)) {
            return true;
        }

        $svixId = $request->header('svix-id');
        $svixTimestamp = $request->header('svix-timestamp');
        $svixSignature = $request->header('svix-signature');

        if (empty($svixId) || empty($svixTimestamp) || empty($svixSignature)) {
            return false;
        }

        $body = $request->getContent();
        $signedContent = "{$svixId}.{$svixTimestamp}.{$body}";

        $secretBytes = base64_decode(str_replace('whsec_', '', $secret));
        $computed = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

        $signatures = explode(' ', $svixSignature);

        foreach ($signatures as $sig) {
            $parts = explode(',', $sig, 2);
            $sigValue = $parts[1] ?? $parts[0];

            if (hash_equals($computed, $sigValue)) {
                return true;
            }
        }

        return false;
    }

    public function handle(Request $request): array
    {
        $payload = $request->all();
        $type = $this->eventMap[$payload['type'] ?? ''] ?? null;

        if (! $type) {
            return [];
        }

        $data = $payload['data'] ?? [];
        $headers = $data['headers'] ?? [];

        $hash = $headers['X-Metadata-hash']
            ?? $headers['x-metadata-hash']
            ?? null;

        if (! $hash && isset($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                if (($tag['name'] ?? '') === 'hash') {
                    $hash = $tag['value'] ?? null;
                    break;
                }
            }
        }

        if (! $hash) {
            return [];
        }

        return [[
            'type' => $type,
            'hash' => $hash,
            'payload' => $payload,
            'occurred_at' => $data['created_at'] ?? $payload['created_at'] ?? null,
            'ip_address' => null,
            'user_agent' => null,
            'url' => $data['click']['link'] ?? null,
        ]];
    }
}
