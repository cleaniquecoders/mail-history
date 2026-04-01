# Commands

Mail History provides three new Artisan commands for managing delivery tracking data.

## mailhistory:stats

Display email delivery statistics for a given period.

```bash
php artisan mailhistory:stats
php artisan mailhistory:stats --days=7
```

### Output Example

```
 Mail History Stats (last 30 days)

 +-----------+-------+
 | Status    | Count |
 +-----------+-------+
 | Sending   | 2     |
 | Sent      | 45    |
 | Delivered | 312   |
 | Opened    | 198   |
 | Clicked   | 87    |
 | Bounced   | 5     |
 | Complained| 1     |
 | Failed    | 3     |
 | Total     | 653   |
 +-----------+-------+
```

### Options

| Option | Default | Description |
|--------|---------|-------------|
| `--days` | `30` | Number of days to show stats for |

## mailhistory:prune

Delete old mail history records and events based on a retention policy.

```bash
# Delete records older than 90 days (default)
php artisan mailhistory:prune

# Delete records older than 30 days
php artisan mailhistory:prune --days=30

# Only delete events, keep mail history records
php artisan mailhistory:prune --events-only
```

### Options

| Option | Default | Description |
|--------|---------|-------------|
| `--days` | Config value or `90` | Number of days to retain |
| `--events-only` | `false` | Only prune `mail_history_events`, keep `mail_histories` |

### Scheduling

Add to your `app/Console/Kernel.php` for automatic pruning:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('mailhistory:prune --days=90')->daily();
}
```

### Retention Config

Set a default retention period in config:

```php
// config/mailhistory.php
'retention' => [
    'enabled' => env('MAILHISTORY_RETENTION_ENABLED', false),
    'days' => env('MAILHISTORY_RETENTION_DAYS', 90),
],
```

The `--days` option overrides the config value when provided.

## mailhistory:test-webhook

Simulate a webhook event for testing without needing a real email provider.

```bash
# Simulate delivered event from mailgun for the latest mail record
php artisan mailhistory:test-webhook mailgun

# Simulate a specific event type
php artisan mailhistory:test-webhook ses bounced

# Simulate for a specific hash
php artisan mailhistory:test-webhook postmark opened --hash=abc123
```

### Arguments

| Argument | Required | Default | Description |
|----------|----------|---------|-------------|
| `provider` | Yes | — | Provider name: `mailgun`, `ses`, `postmark`, `sendgrid`, `resend` |
| `type` | No | `delivered` | Event type: `delivered`, `opened`, `clicked`, `bounced`, `complained`, `failed` |

### Options

| Option | Default | Description |
|--------|---------|-------------|
| `--hash` | Latest record | The mail history hash to target |

### Use Cases

- Verify your event listeners react correctly to delivery events
- Test your bounce handling logic without sending real emails
- Demo the feature to stakeholders

## Existing Commands

These pre-existing commands remain unchanged:

| Command | Description |
|---------|-------------|
| `mailhistory:clear` | Truncate the mail_histories table |
| `mailhistory:test {email} {type}` | Send a real test email/notification and verify tracking |

## Next Steps

- Return to the [Delivery Tracking Overview](./01-overview.md)
- Review the [Webhook Setup](./02-webhook-setup.md) guide
