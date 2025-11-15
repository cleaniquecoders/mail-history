# Advanced

Advanced topics and customization options for Mail History.

## Table of Contents

1. [Custom Hash Generation](./01-custom-hash.md) - Generate custom tracking hashes
2. [Testing Guide](./02-testing.md) - Testing strategies and examples
3. [Troubleshooting](./03-troubleshooting.md) - Common issues and solutions

## Overview

This section covers advanced usage patterns, customization techniques, and troubleshooting strategies for Mail History.

## Topics Covered

### Custom Hash Generation

Learn how to:

- Override default hash generation
- Create meaningful hash values
- Use business identifiers as hashes
- Implement custom hash algorithms

### Testing

Strategies for:

- Testing mail tracking in your application
- Writing feature tests
- Mocking and assertions
- CI/CD integration

### Troubleshooting

Solutions for:

- Common configuration issues
- Event tracking problems
- Queue-related issues
- Performance optimization

## When to Use Advanced Features

### Custom Hashes

Use custom hash generation when:

- You need to correlate emails with business entities (orders, users, etc.)
- Default UUID-based hashes are not sufficient
- You want human-readable identifiers
- You need to track email retries

### Advanced Testing

Implement advanced testing when:

- Building critical email workflows
- Requiring high test coverage
- Testing queue interactions
- Validating email content

### Performance Optimization

Consider optimization when:

- Sending high volumes of emails (> 10,000/day)
- Database storage is a concern
- Query performance degrades
- Application response times increase

## Best Practices

### 1. Custom Hash Naming

Use descriptive hash patterns:

```php
// Good - Descriptive and unique
$this->setMetadataHash("order-{$orderId}-confirmation");
$this->setMetadataHash("user-{$userId}-welcome");

// Avoid - Too generic
$this->setMetadataHash("email-1");
```

### 2. Test Coverage

Ensure comprehensive test coverage:

- Test mail tracking is working
- Verify hash generation
- Check status updates
- Validate queued emails

### 3. Monitoring

Implement monitoring for:

- Emails stuck in "Sending" status
- Failed queue jobs
- Database growth
- Performance metrics

### 4. Maintenance

Regular maintenance tasks:

- Archive old records
- Clean up failed emails
- Optimize database tables
- Review storage usage

## Security Considerations

### Email Content

Email content may contain:

- Personal information (PII)
- Financial data
- Authentication tokens
- Sensitive business information

**Recommendations:**

- Implement encryption at rest
- Regular data cleanup
- Access control for mail history
- GDPR compliance measures

### Hash Collision

While rare, hash collisions can occur:

- Default SHA-1 with UUID has negligible collision risk
- Custom hashes should ensure uniqueness
- Consider including timestamps in custom hashes

## Performance Tips

### Database Optimization

1. **Regular Cleanup:**

   ```php
   // Schedule daily cleanup
   MailHistory::where('created_at', '<', now()->subDays(30))->delete();
   ```

2. **Indexing:**
   - Hash column is already indexed
   - Add indexes for frequently queried JSON fields
   - Consider composite indexes for complex queries

3. **Partitioning:**
   - Partition table by date
   - Archive old partitions
   - Improve query performance

### Application Optimization

1. **Queue Configuration:**
   - Use dedicated queue for emails
   - Configure appropriate workers
   - Monitor queue depth

2. **Chunking:**
   - Process bulk emails in chunks
   - Avoid memory issues
   - Maintain application responsiveness

## Integration Patterns

### With Logging

```php
// Custom listener for logging
class LogEmailSent
{
    public function handle(MessageSent $event): void
    {
        $mailHistory = MailHistory::where('hash', $event->message->getMetadata('hash'))->first();

        Log::info('Email sent', [
            'mail_history_id' => $mailHistory->id,
            'to' => $mailHistory->headers['To'],
            'subject' => $mailHistory->headers['Subject'],
        ]);
    }
}
```

### With Monitoring

```php
// Track metrics
class TrackEmailMetrics
{
    public function handle(MessageSent $event): void
    {
        // Increment counter
        Metrics::increment('emails.sent');

        // Track by type
        $type = $event->message->getMetadata('type', 'unknown');
        Metrics::increment("emails.sent.{$type}");
    }
}
```

### With Notifications

```php
// Alert on failures
class AlertOnStuckEmails extends Command
{
    public function handle()
    {
        $stuck = MailHistory::where('status', 'Sending')
            ->where('created_at', '<', now()->subHours(1))
            ->count();

        if ($stuck > 0) {
            // Send alert to administrators
            Notification::route('mail', 'admin@example.com')
                ->notify(new StuckEmailsAlert($stuck));
        }
    }
}
```

## Advanced Queries

### Finding Duplicate Sends

```php
// Find emails sent multiple times to same recipient
$duplicates = MailHistory::select('headers->To as to', DB::raw('COUNT(*) as count'))
    ->groupBy('to')
    ->having('count', '>', 1)
    ->get();
```

### Email Analytics

```php
// Daily email volume
$dailyVolume = MailHistory::selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->limit(30)
    ->get();

// Success rate
$total = MailHistory::count();
$sent = MailHistory::where('status', 'Sent')->count();
$successRate = $total > 0 ? ($sent / $total) * 100 : 0;
```

### Performance Analysis

```php
// Find slow emails (taking > 1 hour to send)
$slow = MailHistory::whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) > 1')
    ->where('status', 'Sent')
    ->get();
```

## Next Steps

- Learn about [Custom Hash Generation](./01-custom-hash.md)
- Read the [Testing Guide](./02-testing.md)
- Check [Troubleshooting Guide](./03-troubleshooting.md)
