# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

`cleaniquecoders/mailhistory` — a Laravel package that automatically tracks emails sent via Mail and Notification by listening to Laravel's mail events. It stores email metadata, body, headers, and delivery status in a `mail_histories` table, with post-delivery tracking (delivered, opened, clicked, bounced, complained) via provider webhooks and self-hosted pixel/click tracking.

## Commands

```bash
composer test              # Run tests (Pest)
composer test -- --filter="test name"  # Run a single test
composer format            # Fix code style (Pint)
composer analyse           # Static analysis (PHPStan level 4)
composer lint              # Pint + PHPStan together
```

## Architecture

**Event-driven design**: The service provider (`MailHistoryServiceProvider`) registers event listeners from config when `mailhistory.enabled` is true. No middleware or manual wiring needed.

**Two tracking paths** with parallel listener pairs:
- **Mail**: `MessageSending` / `MessageSent` → `Listeners\Mails\StoreMessageSending` / `StoreMessageSent`
- **Notifications**: `NotificationSending` / `NotificationSent` → `Listeners\Notifications\StoreMailSending` / `StoreMailSent`

**Hash-based identification**: Emails are identified by a hash stored in the `X-Metadata-hash` header. Mailables use the `InteractsWithMailMetadata` trait to set this hash via `configureMetadataHash()`. If no hash header is present, listeners generate one from `Str::orderedUuid()`. The trait also injects provider-specific headers (e.g., `X-Mailgun-Variables`, `X-SES-MESSAGE-TAGS`) based on the mail driver for webhook correlation.

**Status flow**: `Sending → Sent → Delivered → Opened → Clicked` (and `Bounced`, `Complained`, `Failed` as terminal states). The first two come from Laravel events; the rest from webhooks or tracking endpoints.

**Delivery tracking** (all opt-in, disabled by default):
- **Webhooks**: `WebhookController` receives POST from providers, delegates to `Webhooks\{Provider}Handler` (implements `Webhooks\Contracts\WebhookHandler`). Each handler verifies signatures and normalizes payloads.
- **Open tracking**: `TrackingController::open()` returns a 1x1 GIF; `InteractsWithOpenTracking` trait injects the pixel.
- **Click tracking**: `TrackingController::click()` decrypts URL and redirects; `InteractsWithClickTracking` trait rewrites links.
- Routes are registered conditionally in `packageBooted()` based on config flags.

**Key classes**:
- `MailHistory` (src/MailHistory.php) — status/origin constants and `getHashFromHeader()` utility
- `Models\MailHistory` — Eloquent model with scopes (`delivered`, `bounced`, `opened`, etc.), `recordEvent()`, `getTimeline()`, and `events()` HasMany relationship
- `Models\MailHistoryEvent` — event log model (type, payload, provider, ip, user_agent, url, occurred_at)
- Both models are configurable via `config('mailhistory.model')` and `config('mailhistory.event-model')`

**Laravel events fired on delivery tracking**: `MailHistoryEventReceived` (always), `MailDelivered`, `MailBounced`, `MailComplained` (type-specific).

## Testing

Tests use Pest with Orchestra Testbench. `TestCase` sets up an in-memory SQLite database and runs both migration stubs directly. Tests that need webhook/tracking routes register them in `beforeEach` since routes are conditionally registered at boot time. Test views are in `tests/resources/views/`.

## Config

The config file (`config/mailhistory.php`) controls:
- `enabled` — toggle via `MAILHISTORY_ENABLED` env var
- `model` / `event-model` — swappable Eloquent model classes
- `events` — event-to-listener mapping (extensible)
- `webhooks` — provider webhook configuration (path, middleware, per-provider handler class + secrets)
- `tracking` — open/click tracking (enabled flags, path, excluded URL patterns)
- `retention` — pruning policy (days)
