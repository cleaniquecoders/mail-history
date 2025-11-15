# Mail Tracking

Learn how to track emails sent via Laravel's Mailable classes using Mail History.

## Overview

Mail tracking captures detailed information about every email sent through Laravel's Mail facade, including headers, content, and delivery status.

## Implementation

### Required Trait

All Mailable classes must use the `InteractsWithMailMetadata` trait:

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use InteractsWithMailMetadata, SerializesModels;

    // Your implementation...
}
```

### Constructor Configuration

Call `configureMetadataHash()` in your constructor to generate a unique tracking hash:

```php
public function __construct()
{
    $this->configureMetadataHash();
}
```

This hash uniquely identifies the email throughout its lifecycle (from "Sending" to "Sent" status).

## Complete Examples

### Basic Mailable

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use InteractsWithMailMetadata, Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {
        $this->configureMetadataHash();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
        );
    }
}
```

### Mailable with Attachments

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use InteractsWithMailMetadata, Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {
        $this->configureMetadataHash();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Invoice',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->invoice->pdf_path)
                ->as('invoice.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
```

### Queued Mailable

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable implements ShouldQueue
{
    use InteractsWithMailMetadata, Queueable, SerializesModels;

    public function __construct()
    {
        $this->configureMetadataHash();

        // Optional: Specify queue
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Monthly Newsletter',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter',
        );
    }
}
```

## Sending Emails

### Using Mail Facade

```php
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Mail;

// Send immediately
Mail::to($user->email)->send(new OrderConfirmation($order));

// Send to multiple recipients
Mail::to($user->email)
    ->cc($manager->email)
    ->bcc($admin->email)
    ->send(new OrderConfirmation($order));
```

### Using Queue

```php
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Mail;

// Queue the email
Mail::to($user->email)->queue(new OrderConfirmation($order));

// Queue with delay
Mail::to($user->email)
    ->later(now()->addMinutes(5), new OrderConfirmation($order));
```

### Using Mailable Directly

```php
use App\Mail\OrderConfirmation;

// Send immediately
(new OrderConfirmation($order))->send();

// Queue the email
(new OrderConfirmation($order))->queue();
```

## Tracking Hash

The tracking hash is automatically generated and allows you to:

### Track Email Lifecycle

```php
use CleaniqueCoders\MailHistory\Models\MailHistory;

// Find emails by hash
$mailHistory = MailHistory::where('hash', $hash)->first();

// Check status
if ($mailHistory->status === 'Sent') {
    // Email was successfully sent
}
```

### Custom Hash Generation

You can provide a custom hash value if needed:

```php
public function __construct()
{
    // Use custom identifier
    $this->setMetadataHash('order-123-confirmation');
}
```

See [Custom Hash Generation](../04-advanced/01-custom-hash.md) for more details.

## What Gets Tracked

For each email, Mail History captures:

### Headers

Full email headers including:

- From/To addresses
- CC/BCC recipients
- Subject
- Content-Type
- Message-ID
- And more...

### Body

The plain text version of the email body.

### Content

JSON object containing:

- `text` - Plain text content
- `text-charset` - Character encoding
- `html` - HTML content

### Metadata

JSON object containing:

- `hash` - Unique tracking identifier
- Custom metadata (if added)

### Status

- `Sending` - Email is being prepared/queued
- `Sent` - Email has been successfully sent

### Timestamps

- `created_at` - When the tracking record was created
- `updated_at` - When the status was last updated

## Querying Mail History

### Basic Queries

```php
use CleaniqueCoders\MailHistory\Models\MailHistory;

// Get all sent emails
$sent = MailHistory::where('status', 'Sent')->get();

// Get emails being sent
$sending = MailHistory::where('status', 'Sending')->get();

// Get recent emails
$recent = MailHistory::latest()->take(10)->get();
```

### Advanced Queries

```php
// Search by subject (in headers)
$emails = MailHistory::whereJsonContains('headers', ['Subject' => 'Order Confirmation'])
    ->get();

// Get emails from today
$today = MailHistory::whereDate('created_at', today())->get();

// Get failed to send (stuck in Sending status)
$failed = MailHistory::where('status', 'Sending')
    ->where('created_at', '<', now()->subHours(2))
    ->get();
```

## Best Practices

### 1. Always Configure Hash

Always call `configureMetadataHash()` in your constructor:

```php
public function __construct()
{
    $this->configureMetadataHash();
}
```

### 2. Use Queues for Bulk Emails

For bulk email operations, always use queues:

```php
foreach ($users as $user) {
    Mail::to($user->email)->queue(new NewsletterMail());
}
```

### 3. Regular Cleanup

Implement regular cleanup of old mail history records:

```bash
# Add to your scheduled tasks
php artisan mailhistory:clear
```

### 4. Monitor Sending Status

Monitor emails stuck in "Sending" status to identify queue issues:

```php
$stuckEmails = MailHistory::where('status', 'Sending')
    ->where('created_at', '<', now()->subHours(1))
    ->count();
```

## Troubleshooting

### Emails Not Being Tracked

1. Verify the trait is added to your Mailable
2. Check that `configureMetadataHash()` is called in constructor
3. Ensure `MAILHISTORY_ENABLED=true` in your `.env`
4. Check event listeners are registered

### Status Stuck at "Sending"

1. Verify queue workers are running
2. Check for failed jobs in `failed_jobs` table
3. Review mail driver configuration
4. Check Laravel logs for errors

### Missing Hash Values

1. Ensure `configureMetadataHash()` is called before sending
2. Verify the trait is properly imported
3. Check for constructor issues

For more troubleshooting tips, see [Troubleshooting Guide](../04-advanced/03-troubleshooting.md).

## Next Steps

- Learn about [Notification Tracking](./02-notification-tracking.md)
- Explore [Artisan Commands](./03-artisan-commands.md)
- See [Custom Hash Generation](../04-advanced/01-custom-hash.md)
