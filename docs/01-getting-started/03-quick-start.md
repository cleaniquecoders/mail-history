# Quick Start

Get up and running with Mail History in minutes. This guide shows you how to start tracking emails in your Laravel application.

## Basic Setup

After installing and configuring the package, you need to make minimal changes to your Mail and Notification classes.

## Tracking Mailable Classes

### Step 1: Add the Trait

Add the `InteractsWithMailMetadata` trait to your Mailable class:

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use InteractsWithMailMetadata, SerializesModels;

    // Your mail class code...
}
```

### Step 2: Configure the Hash

In your Mailable constructor, call the `configureMetadataHash()` method:

```php
public function __construct()
{
    $this->configureMetadataHash();
}
```

### Complete Example

Here's a complete example of a trackable Mailable:

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use InteractsWithMailMetadata, Queueable, SerializesModels;

    public function __construct()
    {
        $this->configureMetadataHash();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Our Application',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
        );
    }
}
```

### Sending the Mail

Send the mail as you normally would:

```php
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

Mail::to($user)->send(new WelcomeMail());
```

The mail will be automatically tracked in the `mail_histories` table.

## Tracking Notifications

For notifications that send emails, you need to configure both the notification and the underlying mailable.

### Step 1: Create a Trackable Mailable

First, ensure your Mailable class is configured for tracking (as shown above).

### Step 2: Add Trait to Notification

Add the `InteractsWithMail` trait to your Notification class:

```php
<?php

namespace App\Notifications;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMail;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use InteractsWithMail;

    // Your notification code...
}
```

### Step 3: Initialize the Mail Object

In your notification constructor, set the mail object:

```php
use App\Mail\WelcomeMail;

public function __construct()
{
    $this->setMail(new WelcomeMail());
}
```

### Step 4: Update the toMail Method

Modify the `toMail()` method to return the configured Mailable:

```php
public function toMail(object $notifiable): Mailable
{
    return $this->getMail()->to($notifiable->email);
}
```

### Complete Example

Here's a complete notification example:

```php
<?php

namespace App\Notifications;

use App\Mail\WelcomeMail;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Mail\Mailable;

class WelcomeNotification extends Notification
{
    use InteractsWithMail, Queueable;

    public function __construct()
    {
        $this->setMail(new WelcomeMail());
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

### Sending the Notification

Send the notification as usual:

```php
use App\Notifications\WelcomeNotification;

$user->notify(new WelcomeNotification());
```

## Verifying Tracking

After sending an email, you can verify it's being tracked:

### Check the Database

Query the `mail_histories` table:

```php
use CleaniqueCoders\MailHistory\Models\MailHistory;

$records = MailHistory::latest()->get();
```

### Check Email Status

Each record has a `status` field:

- **Sending** - Email is queued or being sent
- **Sent** - Email has been successfully sent

### Example Query

```php
// Get all sent emails
$sentEmails = MailHistory::where('status', 'Sent')->get();

// Get emails by hash
$email = MailHistory::where('hash', $hashValue)->first();

// Get recent emails
$recentEmails = MailHistory::latest()->take(10)->get();
```

## Testing Your Setup

Use the built-in test command to verify everything is working:

```bash
# Test mail tracking
php artisan mailhistory:test user@example.com --mail

# Test notification tracking
php artisan mailhistory:test user@example.com --notification

# Test with a specific queue
php artisan mailhistory:test user@example.com --mail --queue=emails
```

**Note:** For mail testing, ensure a user with the specified email address exists in your database.

## What's Next?

- Learn more about [Mail Tracking](../02-usage/01-mail-tracking.md)
- Explore [Notification Tracking](../02-usage/02-notification-tracking.md)
- Discover available [Artisan Commands](../02-usage/03-artisan-commands.md)
