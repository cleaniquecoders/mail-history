# Event System

Understanding how Mail History uses Laravel's event system to capture email metadata.

## Laravel Events

Mail History listens to four core Laravel events to track email lifecycle.

## Mail Events

### MessageSending Event

**Class:** `Illuminate\Mail\Events\MessageSending`

**When Fired:** Before an email is sent

**Listener:** `CleaniqueCoders\MailHistory\Listeners\Mails\StoreMessageSending`

**Action:** Creates a new mail history record with status "Sending"

### MessageSent Event

**Class:** `Illuminate\Mail\Events\MessageSent`

**When Fired:** After an email has been successfully sent

**Listener:** `CleaniqueCoders\MailHistory\Listeners\Mails\StoreMessageSent`

**Action:** Updates the existing mail history record to status "Sent"

## Notification Events

### NotificationSending Event

**Class:** `Illuminate\Notifications\Events\NotificationSending`

**When Fired:** Before a notification is sent (any channel)

**Listener:** `CleaniqueCoders\MailHistory\Listeners\Notifications\StoreMessageSending`

**Action:** If using mail channel, creates a mail history record with status "Sending"

### NotificationSent Event

**Class:** `Illuminate\Notifications\Events\NotificationSent`

**When Fired:** After a notification has been successfully sent

**Listener:** `CleaniqueCoders\MailHistory\Listeners\Notifications\StoreMessageSent`

**Action:** If using mail channel, updates mail history record to status "Sent"

## Event Registration

Events and listeners are registered in the service provider:

```php
public function packageRegistered()
{
    if (! config('mailhistory.enabled')) {
        return;
    }

    foreach (config('mailhistory.events') as $event => $listeners) {
        foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
            Event::listen($event, $listener);
        }
    }
}
```

Configuration controls which listeners are registered:

```php
'events' => [
    MessageSending::class => [
        StoreMailMessageSending::class,
    ],
    MessageSent::class => [
        StoreMailMessageSent::class,
    ],
    NotificationSending::class => [
        StoreNotificationMessageSending::class,
    ],
    NotificationSent::class => [
        StoreNotificationMessageSent::class,
    ],
],
```

## Event Flow Diagrams

### Synchronous Mail Flow

```
Mail::send() → MessageSending Event → Create Record (Sending)
                      ↓
              Send via Driver
                      ↓
        MessageSent Event → Update Record (Sent)
```

### Queued Mail Flow

```
Mail::queue() → MessageSending Event → Create Record (Sending)
                      ↓
              Job Added to Queue
                      ↓
        Queue Worker Processes Job
                      ↓
              Send via Driver
                      ↓
        MessageSent Event → Update Record (Sent)
```

### Notification Flow

```
User->notify() → NotificationSending Event → Check Mail Channel
                         ↓
               Create Record (Sending)
                         ↓
               Send via Mail Channel
                         ↓
        NotificationSent Event → Update Record (Sent)
```

## Listener Implementation

### StoreMessageSending Listener

Extracts metadata from `MessageSending` event:

- Retrieves Symfony Message object
- Extracts headers
- Gets email body
- Retrieves HTML/text content
- Extracts tracking hash from metadata
- Creates database record

### StoreMessageSent Listener

Updates record from `MessageSent` event:

- Retrieves tracking hash from metadata
- Finds matching record by hash
- Updates status to "Sent"
- Updates timestamp

### Notification Listeners

Similar to mail listeners but:

- Extract Mailable from notification
- Check if mail channel is being used
- Only process mail channel notifications

## Custom Event Listeners

You can add custom listeners to extend functionality.

### Example: Logging Listener

```php
namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogEmailSent
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        Log::info('Email sent', [
            'to' => $message->getTo(),
            'subject' => $message->getSubject(),
        ]);
    }
}
```

Register in configuration:

```php
'events' => [
    MessageSent::class => [
        StoreMailMessageSent::class,
        \App\Listeners\LogEmailSent::class,
    ],
],
```

### Example: Metrics Listener

```php
namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;

class RecordEmailMetrics
{
    public function handle(MessageSent $event): void
    {
        // Increment counter
        cache()->increment('emails_sent_today');

        // Track by hour
        $hour = now()->format('Y-m-d-H');
        cache()->increment("emails_sent_{$hour}");
    }
}
```

## Event Data Access

### MessageSending Event

```php
// Access the message
$message = $event->message;

// Get message data
$headers = $message->getHeaders();
$body = $message->getBody();
$to = $message->getTo();
$from = $message->getFrom();
$subject = $message->getSubject();
```

### MessageSent Event

```php
// Access the sent message
$message = $event->sent->getSymfonySentMessage();

// Get original message
$original = $event->sent->getOriginalMessage();
```

### NotificationSending Event

```php
// Access notification
$notification = $event->notification;

// Get notifiable
$notifiable = $event->notifiable;

// Get channel
$channel = $event->channel;

// Check if mail channel
if ($channel === 'mail') {
    // Process mail notification
}
```

### NotificationSent Event

```php
// Access notification
$notification = $event->notification;

// Get notifiable
$notifiable = $event->notifiable;

// Get channel
$channel = $event->channel;

// Get response
$response = $event->response;
```

## Disabling Event Tracking

### Disable Globally

Set in `.env`:

```env
MAILHISTORY_ENABLED=false
```

Or in config:

```php
'enabled' => false,
```

### Disable Specific Events

Remove listeners from configuration:

```php
'events' => [
    // Only track sent emails, not sending
    MessageSent::class => [
        StoreMailMessageSent::class,
    ],
],
```

### Disable at Runtime

```php
use Illuminate\Support\Facades\Event;

// Temporarily disable
Event::forget(MessageSending::class);
Event::forget(MessageSent::class);

// Send emails without tracking
Mail::to($user)->send(new WelcomeMail());

// Re-enable (requires re-registration)
```

## Troubleshooting Events

### Events Not Firing

Check that:

1. Package is installed and service provider is registered
2. `MAILHISTORY_ENABLED=true` in `.env`
3. Event listeners are configured
4. Laravel cache is cleared: `php artisan config:clear`

### Records Not Created

Check that:

1. `configureMetadataHash()` is called in Mailable constructor
2. Trait is added to Mailable class
3. Database migration has run
4. Check Laravel logs for errors

### Records Not Updated to "Sent"

Check that:

1. Email was actually sent (check mail logs)
2. Queue workers are running (for queued emails)
3. Hash exists in metadata
4. `MessageSent` event is firing

## Next Steps

- Review the [Database Schema](./03-database-schema.md)
- Check the [Configuration Reference](./04-configuration-reference.md)
- Learn about [Custom Hash Generation](../04-advanced/01-custom-hash.md)
