# Upgrade Guide

## Upgrading from v2.x to v3.0

v3.0 adds delivery status tracking (webhooks, open/click tracking, reporting, dashboard UI). All new features are **opt-in and disabled by default**. The upgrade requires one action: running a new migration.

### Breaking Changes

**None.** v3.0 is fully backward-compatible with v2.x.

- The existing `mail_histories` table and migration are **unchanged**
- The existing `Sending → Sent` tracking flow is **unchanged**
- All existing event listeners remain registered
- The `MailHistory` model retains all existing behavior
- The `InteractsWithMailMetadata` trait retains all existing methods
- No method signatures were changed
- No existing config keys were removed or renamed

### Required Steps

#### 1. Update the Package

```bash
composer update cleaniquecoders/mailhistory
```

#### 2. Publish and Run the New Migration

```bash
php artisan vendor:publish --tag="mailhistory-migrations"
php artisan migrate
```

This creates the `mail_history_events` table. The existing `mail_histories` table is not modified.

#### 3. Re-publish Config (Optional)

If you published the config file previously, you may want to re-publish to see the new config sections:

```bash
php artisan vendor:publish --tag="mailhistory-config" --force
```

New config sections added (all disabled by default):

```php
'event-model' => MailHistoryEvent::class,   // NEW
'report' => GetMailHistoryReport::class,     // NEW

'webhooks' => [                              // NEW
    'enabled' => false,
    // ...
],

'tracking' => [                              // NEW
    'open' => ['enabled' => false],
    'click' => ['enabled' => false],
    // ...
],

'retention' => [                             // NEW
    'enabled' => false,
    'days' => 90,
],

'ui' => [                                    // NEW
    'enabled' => false,
    // ...
],
```

If you don't re-publish, the package uses sensible defaults for all new keys.

### What Changed (Non-Breaking)

#### Config File

New keys added. Existing keys (`enabled`, `model`, `user-model`, `events`) are unchanged.

#### MailHistory Model (`src/Models/MailHistory.php`)

New methods and relationships added:

| Addition | Type | Description |
|----------|------|-------------|
| `events()` | HasMany relationship | Links to `mail_history_events` table |
| `scopeStatus()` | Query scope | Filter by any status string |
| `scopeDelivered()` | Query scope | Filter by `Delivered` status |
| `scopeBounced()` | Query scope | Filter by `Bounced` status |
| `scopeOpened()` | Query scope | Filter by `Opened` status |
| `scopeClicked()` | Query scope | Filter by `Clicked` status |
| `scopeComplained()` | Query scope | Filter by `Complained` status |
| `scopeFailed()` | Query scope | Filter by `Failed` status |
| `is_delivered` | Accessor | `bool` |
| `is_opened` | Accessor | `bool` |
| `is_bounced` | Accessor | `bool` |
| `recordEvent()` | Method | Record a delivery event |
| `getTimeline()` | Method | Get ordered event history |

No existing methods were modified or removed.

#### Status Constants (`src/MailHistory.php`)

New constants added:

```php
STATUS_DELIVERED   = 'Delivered'
STATUS_OPENED      = 'Opened'
STATUS_CLICKED     = 'Clicked'
STATUS_BOUNCED     = 'Bounced'
STATUS_COMPLAINED  = 'Complained'
STATUS_FAILED      = 'Failed'
```

Existing constants unchanged: `STATUS_SENDING`, `STATUS_SENT`, `ORIGIN_MAIL`, `ORIGIN_NOTIFICATION`.

#### InteractsWithMailMetadata Trait

The `configureMetadataHash()` method now also calls `configureProviderHeaders()`, which injects provider-specific headers (e.g., `X-Mailgun-Variables`) based on the mail driver. This is transparent — the method still returns `$this` and existing behavior is preserved.

If you're using a custom mail driver, the new `configureProviderHeaders()` simply does nothing for unknown drivers.

#### Service Provider

The service provider now conditionally registers additional routes in `packageBooted()` — but only when the relevant config flags are enabled. When disabled (default), the provider behaves identically to v2.x.

### Custom Model Users

If you extended the `MailHistory` model, your custom model will inherit all new methods. No action needed unless you have method name conflicts with:

- `events()`
- `recordEvent()`
- `getTimeline()`
- `scopeStatus()` / `scopeDelivered()` / `scopeBounced()` / `scopeOpened()` / `scopeClicked()` / `scopeComplained()` / `scopeFailed()`
- `getIsDeliveredAttribute()` / `getIsOpenedAttribute()` / `getIsBouncedAttribute()`

If you defined any of these in your custom model, they will override the package's implementation (standard PHP inheritance).

### Enabling New Features

All new features are opt-in. Enable only what you need:

```env
# Webhooks (requires provider setup)
MAILHISTORY_WEBHOOKS_ENABLED=true
MAILHISTORY_MAILGUN_SIGNING_KEY=your-key

# Open/Click Tracking
MAILHISTORY_TRACK_OPENS=true
MAILHISTORY_TRACK_CLICKS=true

# Retention/Pruning
MAILHISTORY_RETENTION_ENABLED=true
MAILHISTORY_RETENTION_DAYS=90

# Dashboard UI (requires livewire/livewire)
MAILHISTORY_UI_ENABLED=true
```

### New Dependencies

| Dependency | Required? | When |
|-----------|-----------|------|
| `livewire/livewire` ^3.0 or ^4.0 | Optional | Only if using the dashboard UI (`MAILHISTORY_UI_ENABLED=true`) |

No new required dependencies were added.
