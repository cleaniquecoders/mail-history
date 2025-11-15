# Configuration Reference

Complete reference for all Mail History configuration options.

## Configuration File Location

`config/mailhistory.php`

Publish with:

```bash
php artisan vendor:publish --tag="mailhistory-config"
```

## Configuration Options

### enabled

**Type:** Boolean
**Default:** `true`
**Environment Variable:** `MAILHISTORY_ENABLED`

Enable or disable mail history tracking globally.

```php
'enabled' => env('MAILHISTORY_ENABLED', true),
```

**Environment File:**

```env
MAILHISTORY_ENABLED=true
```

**Usage:**

- Set to `false` to disable tracking without removing the package
- Useful for testing environments where tracking is not needed
- When disabled, event listeners are not registered

### model

**Type:** String (Class name)
**Default:** `CleaniqueCoders\MailHistory\Models\MailHistory::class`

Specify the Eloquent model class for mail history records.

```php
'model' => MailHistory::class,
```

**Custom Model Example:**

```php
'model' => \App\Models\MailHistory::class,
```

**Usage:**

- Extend the base model to add relationships
- Add custom methods and scopes
- Override default behavior

### user-model

**Type:** String (Class name)
**Default:** `\App\Models\User`

Define the user model for your application.

```php
'user-model' => '\App\Models\User',
```

**Usage:**

- Used by test command to find users
- Reference for user-related functionality
- Can be customized for non-standard user models

### events

**Type:** Array
**Structure:** Event class => Array of listener classes

Configure which listeners handle Laravel events.

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

**Event Classes:**

- `Illuminate\Mail\Events\MessageSending`
- `Illuminate\Mail\Events\MessageSent`
- `Illuminate\Notifications\Events\NotificationSending`
- `Illuminate\Notifications\Events\NotificationSent`

**Listener Classes:**

- `CleaniqueCoders\MailHistory\Listeners\Mails\StoreMessageSending`
- `CleaniqueCoders\MailHistory\Listeners\Mails\StoreMessageSent`
- `CleaniqueCoders\MailHistory\Listeners\Notifications\StoreMessageSending`
- `CleaniqueCoders\MailHistory\Listeners\Notifications\StoreMessageSent`

## Complete Configuration File

```php
<?php

use CleaniqueCoders\MailHistory\Listeners\Mails\StoreMessageSending as StoreMailMessageSending;
use CleaniqueCoders\MailHistory\Listeners\Mails\StoreMessageSent as StoreMailMessageSent;
use CleaniqueCoders\MailHistory\Listeners\Notifications\StoreMailSending as StoreNotificationMessageSending;
use CleaniqueCoders\MailHistory\Listeners\Notifications\StoreMailSent as StoreNotificationMessageSent;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;

return [
    'enabled' => env('MAILHISTORY_ENABLED', true),

    'model' => MailHistory::class,

    'user-model' => '\App\Models\User',

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
];
```

## Environment Variables

### MAILHISTORY_ENABLED

Control mail history tracking via environment.

**Example `.env` configurations:**

```env
# Production - Enabled
MAILHISTORY_ENABLED=true

# Development - Enabled
MAILHISTORY_ENABLED=true

# Testing - Disabled
MAILHISTORY_ENABLED=false

# Staging - Enabled
MAILHISTORY_ENABLED=true
```

## Customization Examples

### Adding Custom Listeners

Add your own listeners alongside package listeners:

```php
'events' => [
    MessageSent::class => [
        StoreMailMessageSent::class,
        \App\Listeners\LogEmailSent::class,
        \App\Listeners\RecordEmailMetrics::class,
    ],
],
```

### Tracking Only Sent Emails

Remove "Sending" listeners to only track successfully sent emails:

```php
'events' => [
    MessageSent::class => [
        StoreMailMessageSent::class,
    ],
    NotificationSent::class => [
        StoreNotificationMessageSent::class,
    ],
],
```

### Using Custom Model with Relationships

```php
// config/mailhistory.php
'model' => \App\Models\MailHistory::class,

// app/Models/MailHistory.php
<?php

namespace App\Models;

use CleaniqueCoders\MailHistory\Models\MailHistory as BaseMailHistory;

class MailHistory extends BaseMailHistory
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
```

### Environment-Specific Configuration

Different settings per environment:

```php
// config/mailhistory.php
'enabled' => env('MAILHISTORY_ENABLED', app()->environment('production')),

// .env.production
MAILHISTORY_ENABLED=true

// .env.testing
MAILHISTORY_ENABLED=false

// .env.local
MAILHISTORY_ENABLED=true
```

## Runtime Configuration

### Checking Configuration

```php
// Check if enabled
$enabled = config('mailhistory.enabled');

// Get model class
$modelClass = config('mailhistory.model');

// Get user model
$userModel = config('mailhistory.user-model');

// Get event configuration
$events = config('mailhistory.events');
```

### Modifying at Runtime

```php
// Disable temporarily
config(['mailhistory.enabled' => false]);

// Change model class
config(['mailhistory.model' => \App\Models\CustomMailHistory::class]);
```

Note: Runtime changes don't affect already registered event listeners.

## Configuration Caching

After changing configuration, clear the cache:

```bash
php artisan config:clear
```

For production, cache configuration:

```bash
php artisan config:cache
```

## Best Practices

### 1. Use Environment Variables

Control features via `.env` instead of modifying config files:

```env
MAILHISTORY_ENABLED=true
```

### 2. Version Control

Commit the configuration file:

```bash
git add config/mailhistory.php
git commit -m "Add mail history configuration"
```

### 3. Environment-Specific Settings

Use different `.env` files for each environment:

```
.env.production
.env.staging
.env.local
```

### 4. Documentation

Document custom configuration in your project README:

```markdown
## Mail History Configuration

- `MAILHISTORY_ENABLED` - Enable/disable email tracking (default: true)
```

### 5. Testing

Test with tracking disabled:

```php
// tests/Feature/MailTest.php
public function setUp(): void
{
    parent::setUp();
    config(['mailhistory.enabled' => false]);
}
```

## Troubleshooting

### Configuration Not Applied

1. Clear configuration cache:

   ```bash
   php artisan config:clear
   ```

2. Verify file location: `config/mailhistory.php`

3. Check environment variables are loaded

### Events Not Registering

1. Ensure `enabled` is `true`
2. Check event classes are correct
3. Verify listener classes exist
4. Clear config cache

### Custom Model Not Working

1. Verify model extends base model
2. Check namespace is correct
3. Ensure model is autoloaded
4. Clear config cache

## Next Steps

- Learn about [Custom Hash Generation](../04-advanced/01-custom-hash.md)
- See [Testing Guide](../04-advanced/02-testing.md)
- Check [Troubleshooting Guide](../04-advanced/03-troubleshooting.md)
