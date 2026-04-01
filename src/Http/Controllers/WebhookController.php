<?php

namespace CleaniqueCoders\MailHistory\Http\Controllers;

use CleaniqueCoders\MailHistory\Webhooks\Contracts\WebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $provider): Response
    {
        $providerConfig = config("mailhistory.webhooks.providers.{$provider}");

        if (! $providerConfig || ! isset($providerConfig['handler'])) {
            abort(404);
        }

        /** @var WebhookHandler $handler */
        $handler = app($providerConfig['handler']);

        if (! $handler->verify($request)) {
            abort(403, 'Invalid webhook signature.');
        }

        $events = $handler->handle($request);

        $model = config('mailhistory.model');

        foreach ($events as $eventData) {
            $mailHistory = $model::where('hash', $eventData['hash'])->first();

            if (! $mailHistory) {
                continue;
            }

            $mailHistory->recordEvent($eventData['type'], $eventData['payload'] ?? [], [
                'occurred_at' => $eventData['occurred_at'] ?? null,
                'provider' => $provider,
                'ip_address' => $eventData['ip_address'] ?? null,
                'user_agent' => $eventData['user_agent'] ?? null,
                'url' => $eventData['url'] ?? null,
            ]);
        }

        return response('OK', 200);
    }
}
