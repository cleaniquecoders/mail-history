# Artisan Commands

Mail History provides Artisan commands for testing and maintenance tasks.

## Available Commands

### mailhistory:test

Test mail or notification tracking functionality.

### mailhistory:clear

Clear mail history records from the database.

## Testing Command

### Syntax

```bash
php artisan mailhistory:test {email} {type} [--queue=]
```

### Arguments

- `email` - The email address to send the test to (required)
- `type` - The type of test: `mail` or `notification` (required)

### Options

- `--queue=` - Optional queue name to use for sending

### Examples

#### Test Mail Tracking

Send a test email using the Mail facade:

```bash
php artisan mailhistory:test user@example.com mail
```

This will:

1. Find a user with the specified email address
2. Send a test `WelcomeMail` Mailable
3. Track the email in mail history

#### Test Notification Tracking

Send a test email using a Notification:

```bash
php artisan mailhistory:test user@example.com notification
```

This will:

1. Find a user with the specified email address
2. Send a test `WelcomeNotification`
3. Track the email via notification system

#### Test with Specific Queue

Send a test email to a specific queue:

```bash
# Test mail on the 'emails' queue
php artisan mailhistory:test user@example.com mail --queue=emails

# Test notification on the 'notifications' queue
php artisan mailhistory:test user@example.com notification --queue=notifications
```

This is useful for testing queue-specific configurations.

### Requirements

**Important:** The test command requires a user record with the specified email address to exist in your database.

Create a test user first:

```bash
php artisan tinker
```

```php
User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);
```

Then run the test:

```bash
php artisan mailhistory:test test@example.com mail
```

### Test Mailables

The package includes test Mailable and Notification classes:

**WelcomeMail** (`CleaniqueCoders\MailHistory\Mail\WelcomeMail`)

- Simple welcome email
- Already configured with `InteractsWithMailMetadata`
- Includes hash generation

**WelcomeNotification** (`CleaniqueCoders\MailHistory\Notifications\WelcomeNotification`)

- Simple welcome notification
- Uses `InteractsWithMail` trait
- Wraps the WelcomeMail Mailable

### Verifying Test Results

After running the test command, verify the email was tracked:

```bash
php artisan tinker
```

```php
use CleaniqueCoders\MailHistory\Models\MailHistory;

// Check the latest mail history record
$latest = MailHistory::latest()->first();

// View the record details
$latest->toArray();

// Check status
echo $latest->status; // Should be 'Sending' or 'Sent'

// View the hash
echo $latest->hash;
```

### Troubleshooting Test Command

#### User Not Found

```
User with email not found
```

**Solution:** Create a user with the specified email address first.

#### Invalid Type

```
Only mail or notification type are allowed.
```

**Solution:** Use either `mail` or `notification` as the type argument:

```bash
php artisan mailhistory:test user@example.com mail
```

#### Queue Not Processing

If the email shows "Sending" status and never changes:

1. Check queue workers are running:

```bash
php artisan queue:work
```

1. Check failed jobs:

```bash
php artisan queue:failed
```

1. Review Laravel logs for errors

## Clear Command

### Syntax

```bash
php artisan mailhistory:clear
```

### Description

Clears all mail history records from the database by truncating the `mail_histories` table.

### Interactive Confirmation

The command asks for confirmation before clearing:

```
Are you sure want to clear the mail history records? (no):
```

Type `yes` to confirm, or `no` (or press Enter) to cancel.

### Example

```bash
$ php artisan mailhistory:clear
Are you sure want to clear the mail history records? (no): yes
Mail history successfully cleared.
```

### Use Cases

#### Development Cleanup

Clear test emails during development:

```bash
php artisan mailhistory:clear
```

#### Scheduled Cleanup

Add to your scheduled tasks for automatic cleanup:

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Clear mail history monthly
    $schedule->command('mailhistory:clear')
        ->monthly()
        ->withoutOverlapping();
}
```

#### Manual Maintenance

Clear old records before a major version upgrade or database optimization.

### Warning

This command truncates the entire table. All mail history records will be permanently deleted. This action cannot be undone.

### Alternative: Selective Deletion

For more granular control, use Eloquent queries:

```php
use CleaniqueCoders\MailHistory\Models\MailHistory;

// Delete old records (older than 30 days)
MailHistory::where('created_at', '<', now()->subDays(30))->delete();

// Delete sent emails only
MailHistory::where('status', 'Sent')->delete();

// Delete failed emails (stuck in Sending status)
MailHistory::where('status', 'Sending')
    ->where('created_at', '<', now()->subHours(2))
    ->delete();
```

## Creating Custom Commands

You can create custom maintenance commands for your specific needs:

### Example: Archive Old Records

```php
<?php

namespace App\Console\Commands;

use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Console\Command;

class ArchiveMailHistory extends Command
{
    protected $signature = 'mail-history:archive {days=30}';

    protected $description = 'Archive mail history older than specified days';

    public function handle()
    {
        $days = $this->argument('days');

        $count = MailHistory::where('created_at', '<', now()->subDays($days))
            ->count();

        if ($count === 0) {
            $this->info('No records to archive.');
            return 0;
        }

        $this->info("Found {$count} records to archive.");

        if ($this->confirm('Do you want to proceed?')) {
            // Archive logic here (e.g., export to CSV, move to archive table)

            MailHistory::where('created_at', '<', now()->subDays($days))
                ->delete();

            $this->info("Archived and deleted {$count} records.");
        }

        return 0;
    }
}
```

### Example: Email Statistics

```php
<?php

namespace App\Console\Commands;

use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Console\Command;

class MailHistoryStats extends Command
{
    protected $signature = 'mail-history:stats';

    protected $description = 'Display mail history statistics';

    public function handle()
    {
        $total = MailHistory::count();
        $sent = MailHistory::where('status', 'Sent')->count();
        $sending = MailHistory::where('status', 'Sending')->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Emails', $total],
                ['Successfully Sent', $sent],
                ['Currently Sending', $sending],
                ['Success Rate', $total > 0 ? round(($sent / $total) * 100, 2) . '%' : 'N/A'],
            ]
        );

        $this->info('Recent emails:');

        $recent = MailHistory::latest()->take(5)->get(['hash', 'status', 'created_at']);

        $this->table(
            ['Hash', 'Status', 'Created At'],
            $recent->map(fn($r) => [
                substr($r->hash, 0, 12) . '...',
                $r->status,
                $r->created_at->diffForHumans(),
            ])
        );

        return 0;
    }
}
```

## Best Practices

### Testing in Development

1. Always test with a real user account:

```bash
php artisan mailhistory:test dev@example.com mail
```

1. Verify tracking in database:

```bash
php artisan tinker
> MailHistory::latest()->first()
```

1. Test queue processing:

```bash
# Terminal 1: Run queue worker
php artisan queue:work

# Terminal 2: Send test email
php artisan mailhistory:test dev@example.com mail --queue=emails
```

### Maintenance Schedule

Set up regular maintenance:

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Delete old records weekly
    $schedule->call(function () {
        MailHistory::where('created_at', '<', now()->subDays(30))->delete();
    })->weekly();

    // Or use the clear command monthly
    $schedule->command('mailhistory:clear')->monthly();
}
```

### Production Testing

Use a dedicated testing email for production verification:

```bash
php artisan mailhistory:test monitoring@yourdomain.com mail
```

Monitor the result to ensure tracking is working correctly.

## Next Steps

- Explore [Architecture Overview](../03-architecture/01-overview.md)
- Learn about [Custom Hash Generation](../04-advanced/01-custom-hash.md)
- See [Testing Guide](../04-advanced/02-testing.md)
