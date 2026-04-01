<?php

namespace CleaniqueCoders\MailHistory\Webhooks\Contracts;

use Illuminate\Http\Request;

interface WebhookHandler
{
    /**
     * Verify the webhook signature/authenticity.
     */
    public function verify(Request $request): bool;

    /**
     * Parse the webhook payload and return normalized event data.
     *
     * Returns an array of event arrays, each with keys:
     * - type: string (delivered, opened, clicked, bounced, complained, failed)
     * - hash: string (the X-Metadata-hash value)
     * - payload: array (raw provider data)
     * - occurred_at: ?string (ISO 8601 timestamp)
     * - ip_address: ?string
     * - user_agent: ?string
     * - url: ?string (for click events)
     *
     * @return array<int, array<string, mixed>>
     */
    public function handle(Request $request): array;
}
