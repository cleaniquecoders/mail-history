# Dashboard UI

Mail History includes a built-in Livewire dashboard for viewing email statistics, delivery trends, and recent activity. It supports both Livewire 3 and Livewire 4.

## Requirements

- `livewire/livewire` ^3.0 or ^4.0

```bash
composer require livewire/livewire
```

## Setup

### 1. Enable the Dashboard

```env
MAILHISTORY_UI_ENABLED=true
```

### 2. Visit the Dashboard

Navigate to `/mailhistory` in your browser (requires authentication by default).

## Dashboard Sections

### KPI Cards

Eight status cards showing counts and percentage rates for the selected period:

- Sending, Sent, Delivered, Opened, Clicked, Bounced, Complained, Failed

Each card shows the count and its percentage of total volume.

### Trends Table

Time-series breakdown with columns for period, total, delivered, opened, bounced, and failed. Switchable between daily, weekly, and monthly intervals.

### Stale Email Alerts

Amber warning panel that appears when emails are stuck in "Sending" status for over 1 hour. Useful for detecting queue issues or mail driver failures.

### Provider Breakdown

Sidebar showing event counts per provider (Mailgun, SES, Postmark, etc.) with delivered, opened, and bounced counts. Only appears when webhook events have been recorded.

### Top Bounced / Complained

Lists the most frequent recipients with bounce or spam complaint status. Useful for identifying problematic email addresses to suppress.

### Recent Activity Feed

Table of the latest 20 events across all mail records showing event type, hash, provider, IP address, and relative timestamp. Click any row to expand an inline timeline showing the full event history for that email.

## Configuration

```php
// config/mailhistory.php
'ui' => [
    'enabled' => env('MAILHISTORY_UI_ENABLED', false),
    'prefix' => 'mailhistory',
    'middleware' => ['web', 'auth'],
    'name' => 'mailhistory.',
],
```

### Custom Route Prefix

```php
'ui' => [
    'prefix' => 'admin/mail-dashboard',
],
```

Dashboard becomes available at `/admin/mail-dashboard`.

### Authorization

Guard the dashboard with middleware or gates:

```php
// Via middleware
'ui' => [
    'middleware' => ['web', 'auth', 'can:view-mail-history'],
],
```

```php
// Define the gate in AuthServiceProvider
Gate::define('view-mail-history', function ($user) {
    return $user->is_admin;
});
```

Or use a custom middleware:

```php
'ui' => [
    'middleware' => ['web', 'auth', 'role:admin'],
],
```

### Custom Route Name

```php
'ui' => [
    'name' => 'admin.mailhistory.',
],
```

Generate URLs with:

```php
route('admin.mailhistory.dashboard')
```

## Livewire 3 vs 4 Compatibility

The package automatically detects the installed Livewire version:

- **Livewire 4** — Uses `Livewire::addNamespace()` for namespaced component registration
- **Livewire 3** — Falls back to `Livewire::component()` for direct registration

No code changes are needed. The component tag works on both versions:

```blade
<livewire:mailhistory::dashboard />
```

## Customizing Views

Publish the views to customize the dashboard appearance:

```bash
php artisan vendor:publish --tag="mailhistory-views"
```

This copies all views to `resources/views/vendor/mailhistory/`. You can then modify the layout, styles, or component structure.

### Key View Files

| File | Purpose |
|------|---------|
| `layout.blade.php` | Standalone HTML layout with Tailwind CDN |
| `index.blade.php` | Mounts the Livewire dashboard component |
| `livewire/dashboard.blade.php` | Full dashboard template |

### Embedding in Your Own Layout

If you want to embed the dashboard in your existing app layout instead of the standalone one, publish the views and modify `index.blade.php`:

```blade
@extends('layouts.app')

@section('content')
    <livewire:mailhistory::dashboard />
@endsection
```

## Features

### Live Polling

The dashboard polls every 10 seconds (`wire:poll.10s`) for fresh data. No manual refresh needed.

### Period Selection

Filter all data by time range (7, 14, 30, 60, or 90 days) and trend interval (daily, weekly, monthly). Changes apply instantly via Livewire reactivity.

### Inline Timeline

Click any row in the recent activity table to expand the full event timeline for that email. Shows each event type, timestamp, provider, IP address, and clicked URL in chronological order.

### Dark Mode

The dashboard respects the system dark mode preference via Tailwind's `darkMode: 'media'` setting.

## Next Steps

- See [Reporting & Statistics](./07-reporting.md) for programmatic access to the same data
- Review [Webhook Setup](./02-webhook-setup.md) to populate provider data
- Configure [Open Tracking](./03-open-tracking.md) and [Click Tracking](./04-click-tracking.md)
