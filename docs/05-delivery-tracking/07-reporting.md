# Reporting & Statistics

Mail History provides a `MailHistoryReport` action that gives you everything you need to build dashboards, analytics pages, and monitoring views. All methods return data structures ready for UI consumption.

## Setup

The report action is automatically registered in the container. Use it via dependency injection or the container:

```php
use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;

// Dependency injection (controllers, Livewire, etc.)
public function __construct(private MailHistoryReport $report) {}

// Or resolve from container
$report = app(MailHistoryReport::class);
```

## Available Methods

### summary()

Get counts and delivery rates for all statuses.

```php
$summary = $report->summary();
// Optionally filter by date range
$summary = $report->summary(from: now()->subDays(30), to: now());
```

**Returns:**

```php
[
    'statuses' => [
        'Sending'    => 2,
        'Sent'       => 45,
        'Delivered'  => 312,
        'Opened'     => 198,
        'Clicked'    => 87,
        'Bounced'    => 5,
        'Complained' => 1,
        'Failed'     => 3,
    ],
    'total' => 653,
    'rates' => [
        'Sending'    => 0.31,
        'Sent'       => 6.89,
        'Delivered'  => 47.78,
        'Opened'     => 30.32,
        'Clicked'    => 13.32,
        'Bounced'    => 0.77,
        'Complained' => 0.15,
        'Failed'     => 0.46,
    ],
]
```

**UI idea:** Dashboard KPI cards showing total sent, delivery rate, open rate, bounce rate.

### trends()

Get time-series data grouped by day, week, or month.

```php
// Daily (default)
$daily = $report->trends('daily', now()->subDays(30), now());

// Weekly
$weekly = $report->trends('weekly', now()->subWeeks(12), now());

// Monthly
$monthly = $report->trends('monthly', now()->subMonths(6), now());
```

**Returns:** Collection of rows, each with:

```php
[
    'period'     => '2025-01-15',  // or '2025-W03' or '2025-01'
    'total'      => 42,
    'sending'    => 0,
    'sent'       => 5,
    'delivered'  => 20,
    'opened'     => 12,
    'clicked'    => 3,
    'bounced'    => 1,
    'complained' => 0,
    'failed'     => 1,
]
```

**UI idea:** Line or bar chart showing email volume and status breakdown over time.

### byProvider()

Get event counts broken down by email provider.

```php
$providers = $report->byProvider();
// Optionally filter by date range
$providers = $report->byProvider(from: now()->subDays(30));
```

**Returns:** Collection of rows:

```php
[
    'provider'   => 'mailgun',
    'total'      => 150,
    'delivered'  => 120,
    'opened'     => 80,
    'clicked'    => 30,
    'bounced'    => 8,
    'complained' => 2,
    'failed'     => 10,
]
```

**UI idea:** Provider comparison table or stacked bar chart.

### timeline()

Get the full event timeline for a single email, by hash or ID.

```php
// By hash
$events = $report->timeline('abc123');

// By ID
$events = $report->timeline(42);
```

**Returns:** Collection of events ordered by `occurred_at`:

```php
[
    [
        'type'        => 'delivered',
        'provider'    => 'mailgun',
        'occurred_at' => '2025-01-15 10:00:05',
        'ip_address'  => null,
        'user_agent'  => null,
        'url'         => null,
    ],
    [
        'type'        => 'opened',
        'provider'    => null,
        'occurred_at' => '2025-01-15 10:05:12',
        'ip_address'  => '1.2.3.4',
        'user_agent'  => 'Mozilla/5.0...',
        'url'         => null,
    ],
]
```

**UI idea:** Vertical timeline component in an email detail view.

### topRecipients()

Get the most frequent recipients for a given status.

```php
$bounced = $report->topRecipients('Bounced', limit: 10);
$complained = $report->topRecipients('Complained', limit: 5, from: now()->subDays(30));
```

**Returns:**

```php
[
    ['recipient' => 'bad@example.com', 'count' => 12],
    ['recipient' => 'invalid@test.com', 'count' => 5],
]
```

**UI idea:** Table of problematic email addresses to review or suppress.

### recentActivity()

Get the latest events across all mail records.

```php
$activity = $report->recentActivity(limit: 50);
```

**Returns:** Collection ordered by most recent first:

```php
[
    [
        'mail_history_id' => 42,
        'hash'            => 'abc123',
        'type'            => 'opened',
        'provider'        => null,
        'occurred_at'     => '2025-01-15 10:05:12',
        'ip_address'      => '1.2.3.4',
        'user_agent'      => 'Mozilla/5.0...',
        'url'             => null,
    ],
]
```

**UI idea:** Live activity feed or recent events sidebar.

### stale()

Find records stuck in a status for longer than expected.

```php
// Emails stuck in "Sending" for over 60 minutes
$stuck = $report->stale('Sending', olderThanMinutes: 60);

// Emails sent but never delivered after 24 hours
$undelivered = $report->stale('Sent', olderThanMinutes: 1440);
```

**Returns:** Collection of `MailHistory` model instances.

**UI idea:** Alert panel or monitoring dashboard showing emails that may need attention.

### byHeader()

Get status breakdown grouped by any email header value.

```php
// By subject line
$bySubject = $report->byHeader('Subject');

// By sender
$byFrom = $report->byHeader('From', from: now()->subDays(7));
```

**Returns:**

```php
[
    [
        'Welcome' => 'Welcome',   // the header value
        'total'   => 150,
        'Delivered' => 120,
        'Opened'    => 80,
        'Bounced'   => 5,
    ],
    [
        'Invoice #123' => 'Invoice #123',
        'total' => 45,
        'Delivered' => 40,
        'Bounced' => 3,
    ],
]
```

**UI idea:** Per-mailable or per-subject analytics table showing which emails perform best.

## Usage Examples

### Livewire Dashboard Component

```php
namespace App\Livewire;

use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;
use Livewire\Component;

class MailDashboard extends Component
{
    public int $days = 30;

    public function render()
    {
        $report = app(MailHistoryReport::class);
        $from = now()->subDays($this->days);

        return view('livewire.mail-dashboard', [
            'summary'  => $report->summary(from: $from),
            'trends'   => $report->trends('daily', $from, now()),
            'bounced'  => $report->topRecipients('Bounced', 5, $from),
            'stale'    => $report->stale('Sending', 60),
            'activity' => $report->recentActivity(20),
        ]);
    }
}
```

### API Endpoint

```php
// routes/api.php
use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;

Route::get('/api/mail-stats', function (MailHistoryReport $report) {
    return response()->json([
        'summary'   => $report->summary(from: now()->subDays(30)),
        'providers' => $report->byProvider(from: now()->subDays(30)),
    ]);
})->middleware('auth:sanctum');
```

### Scheduled Monitoring

```php
// app/Console/Kernel.php
use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;

$schedule->call(function () {
    $report = app(MailHistoryReport::class);
    $stuck = $report->stale('Sending', 120);

    if ($stuck->isNotEmpty()) {
        // Notify admin
        Notification::route('mail', 'admin@example.com')
            ->notify(new StuckEmailsAlert($stuck->count()));
    }
})->hourly();
```

## Custom Implementation

To replace the default report with your own logic, implement the contract and update config:

```php
namespace App\Actions;

use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;

class CustomMailHistoryReport implements MailHistoryReport
{
    // Implement all methods...
}
```

```php
// config/mailhistory.php
'report' => \App\Actions\CustomMailHistoryReport::class,
```

## Next Steps

- Return to the [Delivery Tracking Overview](./01-overview.md)
- See [Commands](./06-commands.md) for CLI-based stats
