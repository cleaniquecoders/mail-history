# Configuration

Learn about all available configuration options for the Mail History package.

## Configuration File

The configuration file is located at `config/mailhistory.php` after publishing.

## Configuration Options

### Enable/Disable Tracking

Control whether mail history tracking is enabled:

```php
'enabled' => env('MAILHISTORY_ENABLED', true),
```

You can control this via your `.env` file:

```env
MAILHISTORY_ENABLED=true
```

Set to `false` to disable mail tracking without removing the package.

### Mail History Model

Specify the model class used for storing mail history records:

```php
'model' => MailHistory::class,
```

You can extend the default model and specify your custom model here.

### User Model

Define the user model for your application:

```php
'user-model' => '\App\Models\User',
```

This is used for testing and relating mail records to users.

### Event Listeners

Configure which listeners handle mail and notification events:

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

The package listens to four Laravel events:

- **MessageSending** - Triggered when a mail is about to be sent
- **MessageSent** - Triggered after a mail has been sent
- **NotificationSending** - Triggered when a notification is about to be sent
- **NotificationSent** - Triggered after a notification has been sent

## Environment Variables

Add these to your `.env` file for easy configuration:

```env
# Enable or disable mail history tracking
MAILHISTORY_ENABLED=true
```

## Custom Model Example

If you need to extend the default model with additional functionality:

1. Create your custom model:

```php
<?php

namespace App\Models;

use CleaniqueCoders\MailHistory\Models\MailHistory as BaseMailHistory;

class MailHistory extends BaseMailHistory
{
    // Add your custom methods and relationships here

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

2. Update the configuration:

```php
'model' => \App\Models\MailHistory::class,
```

## Production Considerations

### Performance

For high-volume applications:

- Consider using a separate database connection for mail history
- Implement regular cleanup of old records using the `mailhistory:clear` command
- Monitor the `mail_histories` table size

### Queue Configuration

Mail History works seamlessly with Laravel queues. Ensure your queue workers are running:

```bash
php artisan queue:work
```

### Storage

The `mail_histories` table stores:

- Full email headers (JSON)
- Email body (text)
- Email content (JSON with text and HTML)
- Additional metadata (JSON)

Plan your database storage accordingly for production use.

## What's Next?

Learn how to start tracking emails in the [Quick Start Guide](./03-quick-start.md).
