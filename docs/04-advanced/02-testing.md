# Testing Guide

Comprehensive guide for testing Mail History in your Laravel application.

## Testing Strategies

### Feature Tests

Test that emails are being tracked:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Mail\WelcomeMail;
use App\Models\User;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Support\Facades\Mail;

class MailHistoryTest extends TestCase
{
    public function test_mail_is_tracked()
    {
        $user = User::factory()->create();

        Mail::to($user->email)->send(new WelcomeMail());

        $this->assertDatabaseHas('mail_histories', [
            'status' => 'Sent',
        ]);
    }

    public function test_mail_history_contains_correct_data()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Mail::to($user->email)->send(new WelcomeMail());

        $mailHistory = MailHistory::latest()->first();

        $this->assertNotNull($mailHistory);
        $this->assertEquals('Sent', $mailHistory->status);
        $this->assertStringContainsString('test@example.com', json_encode($mailHistory->headers));
    }

    public function test_mail_tracking_can_be_disabled()
    {
        config(['mailhistory.enabled' => false]);

        $count = MailHistory::count();

        Mail::to('test@example.com')->send(new WelcomeMail());

        $this->assertEquals($count, MailHistory::count());
    }
}
```

### Unit Tests

Test custom hash generation:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Mail\OrderConfirmation;
use App\Models\Order;

class CustomHashTest extends TestCase
{
    public function test_order_confirmation_generates_custom_hash()
    {
        $order = Order::factory()->create(['id' => 123]);

        $mailable = new OrderConfirmation($order);

        $this->assertEquals(
            'order-123-confirmation',
            $mailable->getMetadataHash()
        );
    }

    public function test_hash_is_unique_for_different_orders()
    {
        $order1 = Order::factory()->create(['id' => 123]);
        $order2 = Order::factory()->create(['id' => 456]);

        $mailable1 = new OrderConfirmation($order1);
        $mailable2 = new OrderConfirmation($order2);

        $this->assertNotEquals(
            $mailable1->getMetadataHash(),
            $mailable2->getMetadataHash()
        );
    }
}
```

### Queue Tests

Test queued emails:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Mail\NewsletterMail;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class QueuedMailTest extends TestCase
{
    public function test_queued_mail_is_tracked()
    {
        Queue::fake();

        Mail::to('test@example.com')->queue(new NewsletterMail());

        // Check record created with "Sending" status
        $this->assertDatabaseHas('mail_histories', [
            'status' => 'Sending',
        ]);

        // Process queue
        Queue::assertPushed(function ($job) {
            $job->handle();
            return true;
        });

        // Check status updated to "Sent"
        $this->assertDatabaseHas('mail_histories', [
            'status' => 'Sent',
        ]);
    }
}
```

## Test Helpers

### Create Test Mailable

```php
<?php

namespace Tests\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    use InteractsWithMailMetadata;

    public function __construct(public ?string $customHash = null)
    {
        if ($customHash) {
            $this->setMetadataHash($customHash);
        } else {
            $this->configureMetadataHash();
        }
    }

    public function build()
    {
        return $this->view('emails.test');
    }
}
```

### Assertions

```php
// Assert mail was tracked
public function assertMailWasTracked(string $hash): void
{
    $this->assertDatabaseHas('mail_histories', ['hash' => $hash]);
}

// Assert mail was sent
public function assertMailWasSent(string $hash): void
{
    $this->assertDatabaseHas('mail_histories', [
        'hash' => $hash,
        'status' => 'Sent',
    ]);
}

// Usage in tests
$this->assertMailWasTracked('test-hash-123');
$this->assertMailWasSent('test-hash-123');
```

## Testing with Laravel Dusk

For browser tests:

```php
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use CleaniqueCoders\MailHistory\Models\MailHistory;

class EmailTrackingTest extends DuskTestCase
{
    public function test_user_registration_sends_tracked_email()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->type('name', 'John Doe')
                ->type('email', 'john@example.com')
                ->type('password', 'password123')
                ->type('password_confirmation', 'password123')
                ->press('Register')
                ->assertPathIs('/dashboard');

            // Verify welcome email was tracked
            $this->assertDatabaseHas('mail_histories', [
                'status' => 'Sent',
            ]);

            $mailHistory = MailHistory::latest()->first();
            $this->assertStringContainsString('john@example.com', json_encode($mailHistory->headers));
        });
    }
}
```

## CI/CD Integration

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2

      - name: Install Dependencies
        run: composer install

      - name: Run Migrations
        run: php artisan migrate --env=testing

      - name: Run Tests
        run: php artisan test
        env:
          MAILHISTORY_ENABLED: true
```

## Mocking

### Mock Mail History

```php
use CleaniqueCoders\MailHistory\Models\MailHistory;
use Mockery;

public function test_with_mock()
{
    $mock = Mockery::mock(MailHistory::class);
    $mock->shouldReceive('where')->andReturnSelf();
    $mock->shouldReceive('first')->andReturn((object)['status' => 'Sent']);

    $this->app->instance(MailHistory::class, $mock);

    // Your test logic
}
```

## Best Practices

1. **Clean Database Between Tests:**

   ```php
   use RefreshDatabase;
   ```

2. **Use Factories:**

   ```php
   $user = User::factory()->create();
   $order = Order::factory()->create();
   ```

3. **Test Both Scenarios:**
   - Test with tracking enabled
   - Test with tracking disabled

4. **Verify Complete Flow:**
   - Create → Sending → Sent

5. **Test Error Cases:**
   - Missing hash
   - Invalid configuration
   - Failed queue jobs

## Next Steps

- Check [Troubleshooting Guide](./03-troubleshooting.md)
- Review [Configuration Reference](../03-architecture/04-configuration-reference.md)
