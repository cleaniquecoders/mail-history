# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

`cleaniquecoders/mailhistory` — a Laravel package that automatically tracks emails sent via Mail and Notification by listening to Laravel's mail events. It stores email metadata, body, headers, and delivery status in a `mail_histories` table.

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

**Hash-based identification**: Emails are identified by a hash stored in the `X-Metadata-hash` header. Mailables use the `InteractsWithMailMetadata` trait to set this hash via `configureMetadataHash()`. If no hash header is present, listeners generate one from `Str::orderedUuid()`.

**Key classes**:
- `MailHistory` (src/MailHistory.php) — constants (`STATUS_SENDING`, `STATUS_SENT`, `ORIGIN_MAIL`, `ORIGIN_NOTIFICATION`) and static `getHashFromHeader()` utility
- `Models\MailHistory` — Eloquent model with `InteractsWithHash` (scope) and `InteractsWithUuid` (from `cleaniquecoders/traitify`)
- The model class is configurable via `config('mailhistory.model')`

**Status flow**: Records are created with status `Sending` on `MessageSending`, then updated to `Sent` on `MessageSent` (matched by hash).

## Testing

Tests use Pest with Orchestra Testbench. `TestCase` sets up an in-memory SQLite database and runs the migration stub directly. Test views are in `tests/resources/views/`.

## Config

The config file (`config/mailhistory.php`) controls:
- `enabled` — toggle via `MAILHISTORY_ENABLED` env var
- `model` — swappable Eloquent model class
- `events` — event-to-listener mapping (extensible)
