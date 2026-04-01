# Changelog

All notable changes to `MailHistory` will be documented in this file.

## 3.0.0 - Email Delivery Status Tracking - 2026-04-01

### What's New

This release adds full post-delivery email tracking to Mail History. All new features are **opt-in and disabled by default** — zero breaking changes from 2.x.

#### Delivery Status Tracking

Track the complete email lifecycle beyond `Sending → Sent`:

```
Sending → Sent → Delivered → Opened → Clicked
                     ↘ Bounced / Complained / Failed

```
- **6 new status constants** — Delivered, Opened, Clicked, Bounced, Complained, Failed
- **`mail_history_events` table** — Append-only event log with provider, IP, user-agent, URL, and raw payload
- **Implied status backfill** — An open auto-creates a Delivered event; a click auto-creates both Delivered and Opened

#### Provider Webhooks

Receive delivery callbacks from your email provider:

- **Mailgun** (HMAC-SHA256), **Amazon SES** (SNS), **Postmark** (token), **SendGrid** (ECDSA), **Resend** (Svix)
- Signature verification per provider
- Swappable handler classes via config
- Provider-specific header injection (`X-Mailgun-Variables`, `X-SES-MESSAGE-TAGS`, etc.)

```env
MAILHISTORY_WEBHOOKS_ENABLED=true
MAILHISTORY_MAILGUN_SIGNING_KEY=your-key

```
#### Open & Click Tracking

Self-hosted tracking without provider webhooks:

- **Open tracking** — 1x1 transparent GIF pixel injected into HTML emails
- **Click tracking** — Links rewritten through encrypted redirect URL (prevents open redirect attacks)
- Traits: `InteractsWithOpenTracking`, `InteractsWithClickTracking`

```env
MAILHISTORY_TRACK_OPENS=true
MAILHISTORY_TRACK_CLICKS=true

```
#### Reporting Action

`MailHistoryReport` contract with `GetMailHistoryReport` implementation:

| Method | Use Case |
|--------|----------|
| `summary()` | KPI cards — counts + percentage rates |
| `trends()` | Time-series (daily/weekly/monthly) |
| `byProvider()` | Provider comparison |
| `timeline()` | Single email event history |
| `topRecipients()` | Most bounced/complained addresses |
| `recentActivity()` | Latest events feed |
| `stale()` | Emails stuck in a status |
| `byHeader()` | Per-subject/sender breakdown |

Swappable via `config('mailhistory.report')`.

#### Livewire Dashboard

Built-in dashboard with Livewire 3 & 4 dual support:

- KPI cards, trends table, provider breakdown
- Stale email alerts, top bounced/complained lists
- Recent activity feed with inline timeline expansion
- Period selector (7–90 days), dark mode support
- Tailwind CDN — zero build step

```env
MAILHISTORY_UI_ENABLED=true

```
#### Artisan Commands

| Command | Description |
|---------|-------------|
| `mailhistory:stats --days=30` | Delivery statistics table |
| `mailhistory:prune --days=90` | Delete old records |
| `mailhistory:test-webhook {provider} {type}` | Simulate webhook events |

#### Laravel Events

| Event | Fired When |
|-------|-----------|
| `MailHistoryEventReceived` | Every delivery event |
| `MailDelivered` | Delivery confirmed |
| `MailBounced` | Email bounced |
| `MailComplained` | Spam complaint |

### Upgrade from 2.x

**Zero breaking changes.** One required step:

```bash
composer update cleaniquecoders/mailhistory
php artisan vendor:publish --tag="mailhistory-migrations"
php artisan migrate

```
See [UPGRADE.md](https://github.com/cleaniquecoders/mail-history/blob/main/UPGRADE.md) for full details.

### Documentation

Complete documentation at [`docs/05-delivery-tracking/`](https://github.com/cleaniquecoders/mail-history/tree/main/docs/05-delivery-tracking) with Mermaid diagrams.

### Stats

- 73 tests, 167 assertions
- 40+ new files
- 5 provider handlers
- 8 reporting methods
- Full Livewire 3/4 dashboard

## 2.4.0 - 2026-03-31

### What's Changed

#### Added

- Laravel 13 support (illuminate constraints include `^13.0`)
- PHPUnit 12 compatibility
- Pest 4 support

#### Changed

- Updated `phpunit.xml.dist` for PHPUnit 12
- Standardized CI workflow (Laravel 12 + PHP 8.4/8.3)
- Updated dev dependencies (larastan, phpstan plugins, collision)

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/2.3.0...2.4.0

## 2.3.0 - 2025-05-01

### What's Changed

* Bump dependabot/fetch-metadata from 2.2.0 to 2.3.0 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/16
* Bump aglipanci/laravel-pint-action from 2.4 to 2.5 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/17

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/2.2.0...2.3.0

## 2.2.0 - 2024-11-27

### What's Changed

* Bump aglipanci/laravel-pint-action from 2.3.1 to 2.4 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/12
* Bump dependabot/fetch-metadata from 1.6.0 to 2.2.0 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/14
* Added Testbench
* Update unit test

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/2.1.0...2.2.0

## Added Laravel 11 Support - 2024-03-21

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/2.0.0...2.1.0

## 2.0.0 - 2024-02-15

### What's Changed

* Bump dependabot/fetch-metadata from 1.5.1 to 1.6.0 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/5
* Bump actions/checkout from 3 to 4 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/6
* Bump aglipanci/laravel-pint-action from 2.3.0 to 2.3.1 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/8
* Refactor  by @nasrulhazim in https://github.com/cleaniquecoders/mail-history/pull/9

### New Contributors

* @nasrulhazim made their first contribution in https://github.com/cleaniquecoders/mail-history/pull/9

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/1.4.0...2.0.0

## 1.4.0 - 2023-06-06

### What's Changed

- Fix hashed not generated 276f597
- Remove cached PHPUnit Result 071b1fe
- Bump aglipanci/laravel-pint-action from 2.2.0 to 2.3.0 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/3
- Bump dependabot/fetch-metadata from 1.4.0 to 1.5.1 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/4

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/1.3.2...1.4.0

## 1.3.2 - 2023-05-10

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/1.3.1...1.3.2

## 1.3.1 - 2023-05-10

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/1.3.0...1.3.1

## 1.3.0 - 2023-05-06

### What's Changed

- Bump dependabot/fetch-metadata from 1.3.6 to 1.4.0 by @dependabot in https://github.com/cleaniquecoders/mail-history/pull/2
- Added Laravel 9 Support
- Allow Custom Hash Generator

### New Contributors

- @dependabot made their first contribution in https://github.com/cleaniquecoders/mail-history/pull/2

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/1.2.0...1.3.0

## 1.2.0 - 2023-04-09

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/1.1.0...1.2.0

## 1.1.0 - 2023-04-05

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/compare/1.0.0...1.1.0

## 1.0.0 - 2023-04-05

**Full Changelog**: https://github.com/cleaniquecoders/mail-history/commits/1.0.0
