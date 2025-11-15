# Custom Hash Generation

Learn how to generate custom tracking hashes for Mail History.

## Default Hash Generation

By default, Mail History generates hashes using SHA-1 with ordered UUIDs:

```php
$hash = sha1(Str::orderedUuid());
// Example: 3a4f5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a
```

This ensures:

- Uniqueness across all emails
- No collisions
- Random, non-predictable values

## Custom Hash Usage

### When to Use Custom Hashes

Use custom hashes when you need to:

- Correlate emails with business entities (orders, invoices, etc.)
- Track email retries with the same identifier
- Create human-readable identifiers
- Query emails by business ID

### Setting Custom Hashes

#### In Mailable Constructor

```php
public function __construct(Order $order)
{
    $this->order = $order;

    // Set custom hash based on order ID
    $this->setMetadataHash("order-{$order->id}-confirmation");
}
```

#### For Retry Tracking

```php
public function __construct(Order $order, int $attempt = 1)
{
    $this->order = $order;
    $this->attempt = $attempt;

    // Track retries
    $this->setMetadataHash("order-{$order->id}-attempt-{$attempt}");
}
```

#### With Timestamps

```php
public function __construct(User $user)
{
    $this->user = $user;

    // Include timestamp for uniqueness
    $timestamp = now()->timestamp;
    $this->setMetadataHash("user-{$user->id}-welcome-{$timestamp}");
}
```

## Examples

### Order Confirmation

```php
<?php

namespace App\Mail;

use App\Models\Order;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Mail\Mailable;

class OrderConfirmation extends Mailable
{
    use InteractsWithMailMetadata;

    public function __construct(public Order $order)
    {
        // Custom hash with order ID
        $this->setMetadataHash("order-{$this->order->id}-confirmation");
    }

    // Rest of the mailable...
}
```

Query by order:

```php
// Find confirmation email for order #123
$email = MailHistory::where('hash', 'order-123-confirmation')->first();
```

### Invoice Email

```php
<?php

namespace App\Mail;

use App\Models\Invoice;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Mail\Mailable;

class InvoiceMail extends Mailable
{
    use InteractsWithMailMetadata;

    public function __construct(public Invoice $invoice)
    {
        // Hash with invoice number
        $this->setMetadataHash("invoice-{$this->invoice->invoice_number}");
    }

    // Rest of the mailable...
}
```

### Password Reset

```php
<?php

namespace App\Mail;

use App\Models\User;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Mail\Mailable;

class PasswordResetMail extends Mailable
{
    use InteractsWithMailMetadata;

    public function __construct(
        public User $user,
        public string $token
    ) {
        // Hash with user ID and token
        $this->setMetadataHash("password-reset-{$this->user->id}-{$this->token}");
    }

    // Rest of the mailable...
}
```

### Multi-Part Campaign

```php
<?php

namespace App\Mail;

use App\Models\User;
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Mail\Mailable;

class CampaignEmail extends Mailable
{
    use InteractsWithMailMetadata;

    public function __construct(
        public User $user,
        public string $campaignId,
        public int $partNumber
    ) {
        // Hash for campaign tracking
        $this->setMetadataHash(
            "campaign-{$this->campaignId}-user-{$this->user->id}-part-{$this->partNumber}"
        );
    }

    // Rest of the mailable...
}
```

## Best Practices

### 1. Ensure Uniqueness

Make sure custom hashes are unique to avoid collisions:

```php
// Good - Includes unique identifiers
"order-{$orderId}-{$type}-{$timestamp}"

// Risky - May have collisions
"order-confirmation"
```

### 2. Use Meaningful Names

Create hashes that are descriptive:

```php
// Good - Descriptive and searchable
"subscription-{$subscriptionId}-renewal-notice"

// Poor - Hard to understand
"sub-{$id}-rn"
```

### 3. Include Context

Add relevant context to the hash:

```php
// With context
"user-{$userId}-{$emailType}-{$date}"

// Examples:
"user-123-welcome-2025-11-16"
"user-456-newsletter-2025-11-16"
```

### 4. Consider Retries

For emails that may be retried:

```php
public function __construct(Order $order, int $attempt = 1)
{
    if ($attempt === 1) {
        // First attempt - simple hash
        $this->setMetadataHash("order-{$order->id}-notification");
    } else {
        // Retry - include attempt number
        $this->setMetadataHash("order-{$order->id}-notification-retry-{$attempt}");
    }
}
```

## Querying with Custom Hashes

### Find Specific Email

```php
// Find order confirmation
$email = MailHistory::where('hash', "order-123-confirmation")->first();

// Check if sent
$sent = MailHistory::where('hash', "order-123-confirmation")
    ->where('status', 'Sent')
    ->exists();
```

### Pattern Matching

```php
// Find all emails for an order
$orderEmails = MailHistory::where('hash', 'like', 'order-123-%')->get();

// Find all password resets for a user
$resets = MailHistory::where('hash', 'like', 'password-reset-456-%')->get();

// Find all campaign emails
$campaignEmails = MailHistory::where('hash', 'like', 'campaign-summer2025-%')->get();
```

### Business Logic Queries

```php
// Check if order confirmation was sent
public function hasOrderConfirmationBeenSent(Order $order): bool
{
    return MailHistory::where('hash', "order-{$order->id}-confirmation")
        ->where('status', 'Sent')
        ->exists();
}

// Get all emails for a user
public function getUserEmails(User $user): Collection
{
    return MailHistory::where('hash', 'like', "user-{$user->id}-%")->get();
}

// Count campaign emails sent
public function getCampaignEmailCount(string $campaignId): int
{
    return MailHistory::where('hash', 'like', "campaign-{$campaignId}-%")
        ->where('status', 'Sent')
        ->count();
}
```

## Advanced Patterns

### With Metadata Storage

Combine custom hash with additional metadata:

```php
public function __construct(Order $order)
{
    $this->order = $order;

    // Set hash
    $this->setMetadataHash("order-{$order->id}-confirmation");

    // Add additional metadata
    $this->metadata('order_id', $order->id);
    $this->metadata('order_total', $order->total);
    $this->metadata('customer_email', $order->customer_email);
}
```

Query by metadata:

```php
// Find by hash
$email = MailHistory::where('hash', "order-123-confirmation")->first();

// Access metadata
$orderId = $email->meta['order_id'];
$orderTotal = $email->meta['order_total'];
```

### Composite Keys

Create complex composite keys:

```php
public function __construct(
    User $user,
    string $type,
    ?Carbon $scheduledAt = null
) {
    $parts = [
        'user',
        $user->id,
        $type,
        ($scheduledAt ?? now())->format('Y-m-d-H-i-s'),
    ];

    $this->setMetadataHash(implode('-', $parts));
}

// Results in: user-123-welcome-2025-11-16-10-30-00
```

### UUID Prefix

Combine custom identifier with UUID for guaranteed uniqueness:

```php
public function __construct(Order $order)
{
    $uuid = Str::uuid();
    $this->setMetadataHash("order-{$order->id}-{$uuid}");
}

// Results in: order-123-550e8400-e29b-41d4-a716-446655440000
```

## Hash Security

### Hashing Sensitive Data

If including sensitive data in hash:

```php
public function __construct(User $user, string $token)
{
    // Hash the token instead of including it directly
    $hashedToken = hash('sha256', $token);

    $this->setMetadataHash("password-reset-{$user->id}-{$hashedToken}");
}
```

### Using Stronger Algorithms

For security-critical applications:

```php
public function __construct(Order $order)
{
    // Use SHA-256 instead of default SHA-1
    $identifier = "order-{$order->id}-" . Str::orderedUuid();
    $hash = hash('sha256', $identifier);

    $this->setMetadataHash($hash);
}
```

## Migration from Default to Custom Hashes

If transitioning from default to custom hashes:

```php
public function __construct(Order $order, bool $useCustomHash = true)
{
    if ($useCustomHash) {
        // New custom hash format
        $this->setMetadataHash("order-{$order->id}-confirmation");
    } else {
        // Fall back to default
        $this->configureMetadataHash();
    }
}
```

## Testing Custom Hashes

```php
use Tests\TestCase;
use App\Mail\OrderConfirmation;
use CleaniqueCoders\MailHistory\Models\MailHistory;

class CustomHashTest extends TestCase
{
    public function test_order_confirmation_uses_custom_hash()
    {
        $order = Order::factory()->create(['id' => 123]);

        $mailable = new OrderConfirmation($order);

        $this->assertEquals(
            'order-123-confirmation',
            $mailable->getMetadataHash()
        );
    }

    public function test_can_find_email_by_custom_hash()
    {
        $order = Order::factory()->create(['id' => 123]);

        Mail::to('test@example.com')->send(new OrderConfirmation($order));

        $email = MailHistory::where('hash', 'order-123-confirmation')->first();

        $this->assertNotNull($email);
        $this->assertEquals('Sent', $email->status);
    }
}
```

## Next Steps

- See [Testing Guide](./02-testing.md) for testing strategies
- Check [Troubleshooting](./03-troubleshooting.md) for common issues
- Review [Configuration Reference](../03-architecture/04-configuration-reference.md)
