# Architecture Overview

This document provides a comprehensive overview of the Mail History package architecture.

## System Architecture

Mail History is a Laravel package that integrates with Laravel's mail and notification systems through event listeners, capturing email metadata without requiring changes to your application's existing mail logic.

## Core Components

### 1. Service Provider

**Class:** `MailHistoryServiceProvider`

The service provider is responsible for:

- Registering package configuration
- Publishing migrations and config files
- Registering Artisan commands
- Binding event listeners to Laravel events

```php
public function configurePackage(Package $package): void
{
    $package
        ->name('mailhistory')
        ->hasConfigFile()
        ->hasConsoleCommands(
            MailHistoryCommand::class,
            MailHistoryTestCommand::class
        )
        ->hasMigration('create_mailhistory_table');
}
```

The service provider checks if tracking is enabled and registers event listeners dynamically from configuration.

### 2. Event System

Mail History uses Laravel's event system to intercept email operations:

**Mail Events:**

- `Illuminate\Mail\Events\MessageSending`
- `Illuminate\Mail\Events\MessageSent`

**Notification Events:**

- `Illuminate\Notifications\Events\NotificationSending`
- `Illuminate\Notifications\Events\NotificationSent`

### 3. Event Listeners

Four dedicated listeners handle different scenarios:

**Mail Listeners:**

- `StoreMessageSending` - Creates a record when mail is about to be sent
- `StoreMessageSent` - Updates the record when mail has been sent

**Notification Listeners:**

- `StoreNotificationMessageSending` - Creates a record for notification emails
- `StoreNotificationMessageSent` - Updates notification email status

### 4. Traits

**InteractsWithMailMetadata**

Used by Mailable classes to:

- Generate unique tracking hashes
- Store and retrieve metadata
- Configure hash values

```php
trait InteractsWithMailMetadata
{
    public function configureMetadataHash(): self
    {
        if (empty($this->getMetadataHash())) {
            $this->setMetadataHash();
        }
        return $this;
    }

    public function setMetadataHash($value = null): self
    {
        $this->metadata('hash', sha1(
            empty($value) ? Str::orderedUuid() : $value
        ));
        return $this;
    }

    public function getMetadataHash(): string
    {
        return data_get($this->metadata, 'hash', '');
    }
}
```

**InteractsWithMail**

Used by Notification classes to:

- Store Mailable instances
- Retrieve configured Mailable objects
- Bridge notifications with mailables

**InteractsWithHash**

Used by the MailHistory model to:

- Find records by hash
- Query related records
- Scope queries by hash

### 5. Models

**MailHistory Model**

The Eloquent model representing mail history records:

```php
class MailHistory extends Model
{
    use HasFactory, InteractsWithHash, InteractsWithUuid;

    protected $guarded = ['id'];

    protected $casts = [
        'headers' => 'array',
        'content' => 'array',
        'meta' => 'array',
    ];
}
```

Features:

- UUID generation for unique identification
- JSON casting for flexible data storage
- Hash-based querying
- Eloquent query scopes

### 6. Commands

**MailHistoryCommand**

Clears mail history records:

```bash
php artisan mailhistory:clear
```

**MailHistoryTestCommand**

Tests mail and notification tracking:

```bash
php artisan mailhistory:test {email} {type} [--queue=]
```

## Data Flow

### Mail Tracking Flow

1. **Mailable Creation**
   - Developer creates Mailable instance
   - Constructor calls `configureMetadataHash()`
   - Unique hash is generated and stored

2. **Sending Initiated**
   - `Mail::send()` or similar method called
   - Laravel fires `MessageSending` event

3. **Pre-Send Capture**
   - `StoreMessageSending` listener catches event
   - Extracts email metadata from Symfony Message
   - Creates MailHistory record with status "Sending"

4. **Email Delivery**
   - Laravel sends email via configured driver
   - Driver (SMTP, Mailgun, SES, etc.) handles delivery

5. **Post-Send Update**
   - Laravel fires `MessageSent` event
   - `StoreMessageSent` listener catches event
   - Updates MailHistory record to status "Sent"

### Notification Tracking Flow

1. **Notification Creation**
   - Developer creates Notification instance
   - Constructor calls `setMail()` with configured Mailable
   - Mailable has hash already configured

2. **Notification Dispatched**
   - `$user->notify()` or `Notification::send()` called
   - Laravel fires `NotificationSending` event

3. **Pre-Send Capture**
   - `StoreNotificationMessageSending` listener catches event
   - Checks if mail channel is being used
   - Extracts Mailable from notification
   - Creates MailHistory record with status "Sending"

4. **Email Delivery**
   - Notification sends via mail channel
   - Underlying Mailable is sent

5. **Post-Send Update**
   - Laravel fires `NotificationSent` event
   - `StoreNotificationMessageSent` listener catches event
   - Updates MailHistory record to status "Sent"

## Integration Points

### Laravel Mail System

Mail History integrates with Laravel's mail system at these points:

- **SwiftMailer/Symfony Mailer** - Extracts message data
- **Mail Facade** - Listens to mail events
- **Queue System** - Tracks queued emails
- **Mailable Classes** - Uses traits for metadata

### Laravel Notification System

Integration with notifications:

- **Notification Class** - Uses `InteractsWithMail` trait
- **Mail Channel** - Captures mail channel notifications
- **Notifiable Trait** - Works with any notifiable model
- **Queue System** - Tracks queued notifications

## Metadata Structure

### Hash Generation

Default hash generation uses SHA-1 with ordered UUID:

```php
$hash = sha1(Str::orderedUuid());
```

Custom hash can be provided:

```php
$mailable->setMetadataHash('order-123-confirmation');
```

### Metadata Storage

Metadata is stored in the `meta` JSON column:

```json
{
    "hash": "3a4f5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a"
}
```

### Headers Capture

Full email headers are captured in JSON format:

```json
{
    "Subject": "Welcome to Our App",
    "From": "noreply@app.com",
    "To": "user@example.com",
    "Content-Type": "text/html; charset=utf-8",
    "Message-ID": "<abc123@mail.app.com>"
}
```

### Content Storage

Email content stored in structured format:

```json
{
    "text": "Plain text version of email...",
    "text-charset": "utf-8",
    "html": "<html><body>HTML version...</body></html>"
}
```

## Lifecycle Management

### Record Creation

Records are created with status "Sending" when:

- `MessageSending` event fires
- `NotificationSending` event fires (for mail channel)

### Record Update

Records are updated to status "Sent" when:

- `MessageSent` event fires
- `NotificationSent` event fires (for mail channel)

### Record Cleanup

Records can be cleaned up via:

- `mailhistory:clear` command (truncates table)
- Manual Eloquent queries (selective deletion)
- Scheduled cleanup tasks

## Queue Handling

### Synchronous Emails

For immediate email sending:

1. MessageSending event fires
2. Record created with status "Sending"
3. Email sent immediately
4. MessageSent event fires
5. Record updated to "Sent"

Process typically completes in milliseconds.

### Queued Emails

For queued email sending:

1. MessageSending event fires when job queued
2. Record created with status "Sending"
3. Job waits in queue
4. Queue worker processes job
5. Email sent via driver
6. MessageSent event fires
7. Record updated to "Sent"

Status remains "Sending" until queue worker processes the job.

## Error Handling

### Failed Queue Jobs

If a queued email fails:

- Record remains in "Sending" status
- Failed job logged in `failed_jobs` table
- Retry logic follows Laravel's queue configuration

### Mail Driver Errors

If mail driver fails:

- Exception thrown by Laravel
- Record may remain in "Sending" status
- Error logged to Laravel logs

### Missing Configuration

If tracking is disabled:

- Events still fire
- Listeners not registered
- No records created

## Performance Considerations

### Database Writes

Each email generates:

- 1 INSERT on MessageSending
- 1 UPDATE on MessageSent

For high-volume applications, consider:

- Database indexing on hash column (already included)
- Separate database connection for mail history
- Regular cleanup of old records

### Memory Usage

JSON columns store:

- Full headers (~1-5 KB)
- Email body (variable size)
- HTML content (variable size)

Plan storage capacity accordingly.

### Query Performance

The hash column is indexed for optimal performance:

```php
$table->string('hash')->index();
```

Queries by hash are fast and efficient.

## Security Architecture

### Data Sensitivity

Mail history stores:

- Email addresses
- Email content
- User metadata

Consider:

- Encryption at rest
- Access control
- GDPR compliance
- Data retention policies

### Hash Predictability

Default SHA-1 hash with UUID is:

- Non-predictable
- Unique per email
- Not reversible

For higher security needs, use custom hash generation.

## Extensibility

### Custom Listeners

Add custom listeners in config:

```php
'events' => [
    MessageSent::class => [
        StoreMailMessageSent::class,
        App\Listeners\LogEmailSent::class,
    ],
],
```

### Custom Model

Extend the base model:

```php
namespace App\Models;

class MailHistory extends \CleaniqueCoders\MailHistory\Models\MailHistory
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

Update configuration:

```php
'model' => \App\Models\MailHistory::class,
```

### Custom Metadata

Add custom metadata to mailables:

```php
public function __construct()
{
    $this->configureMetadataHash();
    $this->metadata('order_id', $this->order->id);
    $this->metadata('user_id', $this->user->id);
}
```

## Testing Architecture

The package includes test classes:

- `WelcomeMail` - Test Mailable
- `WelcomeNotification` - Test Notification
- `MailHistoryTestCommand` - CLI testing tool

These enable verification without modifying application code.

## Next Steps

- Learn about the [Event System](./02-event-system.md)
- Review the [Database Schema](./03-database-schema.md)
- Check the [Configuration Reference](./04-configuration-reference.md)
