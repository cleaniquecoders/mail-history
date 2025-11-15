# Notification Tracking

Learn how to track emails sent via Laravel's Notification system using Mail History.

## Overview

Notification tracking allows you to capture email metadata for notifications sent through Laravel's notification system. This is particularly useful for applications that primarily use notifications for email communications.

## Important Notes

- Notification tracking requires a properly configured Mailable class
- Only works with Mailable-based notifications (not MailMessage)
- The underlying Mailable must have the `InteractsWithMailMetadata` trait

## Implementation

### Step 1: Create a Trackable Mailable

First, create a Mailable class with tracking enabled:

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountVerificationMail extends Mailable
{
    use InteractsWithMailMetadata, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $verificationUrl
    ) {
        $this->configureMetadataHash();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email Address',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
        );
    }
}
```

### Step 2: Create the Notification

Create a notification class and add the `InteractsWithMail` trait:

```php
<?php

namespace App\Notifications;

use App\Mail\AccountVerificationMail;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Mail\Mailable;

class AccountVerificationNotification extends Notification
{
    use InteractsWithMail, Queueable;

    public function __construct(
        public string $verificationUrl
    ) {
        // Initialize the mail object in constructor
        $this->setMail(new AccountVerificationMail(
            auth()->user(),
            $this->verificationUrl
        ));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return $this->getMail()->to($notifiable->email);
    }
}
```

## Complete Examples

### Password Reset Notification

```php
<?php

namespace App\Notifications;

use App\Mail\PasswordResetMail;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Mail\Mailable;

class PasswordResetNotification extends Notification
{
    use InteractsWithMail, Queueable;

    public function __construct(
        public string $token
    ) {
        $this->setMail(new PasswordResetMail($this->token));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return $this->getMail()->to($notifiable->email);
    }
}
```

Corresponding Mailable:

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use InteractsWithMailMetadata, Queueable, SerializesModels;

    public function __construct(
        public string $token
    ) {
        $this->configureMetadataHash();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'resetUrl' => url(route('password.reset', [
                    'token' => $this->token,
                    'email' => request('email'),
                ])),
            ],
        );
    }
}
```

### Multi-Channel Notification

```php
<?php

namespace App\Notifications;

use App\Mail\OrderShippedMail;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Mail\Mailable;

class OrderShippedNotification extends Notification
{
    use InteractsWithMail, Queueable;

    public function __construct(
        public Order $order
    ) {
        $this->setMail(new OrderShippedMail($this->order));
    }

    public function via(object $notifiable): array
    {
        // Multiple channels, but only mail is tracked
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return $this->getMail()->to($notifiable->email);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => 'Your order has been shipped!',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'order_id' => $this->order->id,
            'message' => 'Your order has been shipped!',
        ]);
    }
}
```

### Queued Notification

```php
<?php

namespace App\Notifications;

use App\Mail\WelcomeMail;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Mail\Mailable;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use InteractsWithMail, Queueable;

    public function __construct()
    {
        $this->setMail(new WelcomeMail());

        // Specify queue
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return $this->getMail()->to($notifiable->email);
    }
}
```

## Sending Notifications

### Using Notifiable Trait

```php
use App\Notifications\AccountVerificationNotification;

// Send to a user
$user->notify(new AccountVerificationNotification($verificationUrl));
```

### Using Notification Facade

```php
use App\Notifications\AccountVerificationNotification;
use Illuminate\Support\Facades\Notification;

// Send to a user
Notification::send($user, new AccountVerificationNotification($verificationUrl));

// Send to multiple users
Notification::send($users, new AccountVerificationNotification($verificationUrl));
```

### Anonymous Notifications

```php
use App\Notifications\OrderShippedNotification;
use Illuminate\Support\Facades\Notification;

Notification::route('mail', 'guest@example.com')
    ->notify(new OrderShippedNotification($order));
```

## Configuration

### Initializing Mail in Constructor

The `setMail()` method must be called in the notification constructor:

```php
public function __construct()
{
    $this->setMail(new YourMailable());
}
```

### Passing Data to Mailable

Pass data through the Mailable constructor:

```php
public function __construct(Order $order)
{
    $this->setMail(new OrderMail($order));
}
```

### Dynamic Recipient Configuration

The `toMail()` method receives the notifiable:

```php
public function toMail(object $notifiable): Mailable
{
    return $this->getMail()
        ->to($notifiable->email)
        ->cc($notifiable->manager->email);
}
```

## Tracking Behavior

### What Gets Tracked

Notification tracking captures the same information as mail tracking:

- Full email headers
- Email body (plain text)
- Email content (text and HTML)
- Metadata with tracking hash
- Status (Sending/Sent)
- Timestamps

### Tracking Events

The package listens to these notification events:

- `NotificationSending` - Before notification is sent
- `NotificationSent` - After notification is sent

## Common Patterns

### User Registration Flow

```php
// In your registration controller
$user = User::create($validatedData);

$user->notify(new WelcomeNotification());
$user->notify(new AccountVerificationNotification($verificationUrl));
```

### Order Status Updates

```php
// In your order service
$order->update(['status' => 'shipped']);

$order->user->notify(new OrderShippedNotification($order));
```

### Bulk Notifications

```php
// Send to multiple users efficiently
$users = User::where('subscribed', true)->get();

Notification::send($users, new NewsletterNotification($newsletter));
```

## Querying Notification Emails

```php
use CleaniqueCoders\MailHistory\Models\MailHistory;

// Find verification emails
$verifications = MailHistory::whereJsonContains(
    'headers',
    ['Subject' => 'Verify Your Email Address']
)->get();

// Check if verification email was sent
$sent = MailHistory::where('hash', $hash)
    ->where('status', 'Sent')
    ->exists();
```

## Best Practices

### 1. Separate Mailable Classes

Create dedicated Mailable classes for each notification type:

```
app/Mail/
├── AccountVerificationMail.php
├── PasswordResetMail.php
├── OrderShippedMail.php
└── WelcomeMail.php
```

### 2. Use Constructor for Configuration

Always configure the mail object in the notification constructor:

```php
public function __construct($data)
{
    $this->setMail(new YourMailable($data));
}
```

### 3. Leverage Queues

Use `ShouldQueue` for all notifications:

```php
class YourNotification extends Notification implements ShouldQueue
{
    use InteractsWithMail, Queueable;
}
```

### 4. Handle Failures Gracefully

Implement failure handling:

```php
public function failed(Throwable $exception): void
{
    // Log the failure or notify administrators
    Log::error('Notification failed', [
        'notification' => get_class($this),
        'exception' => $exception->getMessage(),
    ]);
}
```

## Limitations

### MailMessage Not Supported

The package currently does not support Laravel's `MailMessage` class:

```php
// This will NOT be tracked
public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Test')
        ->line('Hello!');
}
```

Instead, use Mailable classes:

```php
// This WILL be tracked
public function toMail(object $notifiable): Mailable
{
    return $this->getMail()->to($notifiable->email);
}
```

### Mail Channel Only

Only the `mail` channel notifications are tracked. Other channels (database, broadcast, SMS) are not affected.

## Troubleshooting

### Notifications Not Being Tracked

1. Verify the `InteractsWithMail` trait is added to notification
2. Check that `setMail()` is called in constructor
3. Ensure the Mailable has `InteractsWithMailMetadata` trait
4. Verify `configureMetadataHash()` is called in Mailable constructor

### "Mail not set" Error

This occurs when `setMail()` is not called:

```php
// Wrong
public function __construct()
{
    // Missing setMail()
}

// Correct
public function __construct()
{
    $this->setMail(new YourMailable());
}
```

### Tracking Multiple Notification Attempts

Use custom hash to track retries:

```php
public function __construct($userId, $attempt = 1)
{
    $mailable = new YourMailable();
    $mailable->setMetadataHash("user-{$userId}-attempt-{$attempt}");
    $this->setMail($mailable);
}
```

For more troubleshooting, see [Troubleshooting Guide](../04-advanced/03-troubleshooting.md).

## Next Steps

- Learn about [Artisan Commands](./03-artisan-commands.md)
- See [Custom Hash Generation](../04-advanced/01-custom-hash.md)
- Explore [Architecture Overview](../03-architecture/01-overview.md)
