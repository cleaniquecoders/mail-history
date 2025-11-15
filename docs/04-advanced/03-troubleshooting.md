# Troubleshooting

Common issues and solutions for Mail History.

## Installation Issues

### Migration Fails

**Problem:** Migration throws an error

**Solutions:**

1. Check database connection:

   ```bash
   php artisan db:show
   ```

2. Verify migration file was published:

   ```bash
   ls database/migrations/*mailhistory*
   ```

3. Re-publish and migrate:

   ```bash
   php artisan vendor:publish --tag="mailhistory-migrations" --force
   php artisan migrate
   ```

### Package Not Discovered

**Problem:** Service provider not loaded

**Solutions:**

1. Clear config cache:

   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. Manually register in `config/app.php`:

   ```php
   'providers' => [
       CleaniqueCoders\MailHistory\MailHistoryServiceProvider::class,
   ],
   ```

## Tracking Issues

### Emails Not Being Tracked

**Problem:** No records created in `mail_histories` table

**Checklist:**

1. ✅ Verify tracking is enabled:

   ```php
   // In .env
   MAILHISTORY_ENABLED=true
   ```

2. ✅ Check trait is added to Mailable:

   ```php
   use InteractsWithMailMetadata;
   ```

3. ✅ Verify `configureMetadataHash()` is called:

   ```php
   public function __construct()
   {
       $this->configureMetadataHash();
   }
   ```

4. ✅ Check event listeners are registered:

   ```bash
   php artisan event:list
   ```

5. ✅ Clear caches:

   ```bash
   php artisan config:clear
   php artisan event:clear
   ```

### Status Stuck at "Sending"

**Problem:** Records remain in "Sending" status

**Common Causes:**

1. **Queue Not Running**

   ```bash
   # Check queue workers
   ps aux | grep "queue:work"

   # Start queue worker
   php artisan queue:work
   ```

2. **Failed Jobs**

   ```bash
   # Check failed jobs
   php artisan queue:failed

   # Retry failed jobs
   php artisan queue:retry all
   ```

3. **Mail Driver Issue**
   - Check mail configuration
   - Review Laravel logs
   - Test mail driver directly

### Missing Hash Values

**Problem:** Hash is empty or null

**Solutions:**

1. Ensure `configureMetadataHash()` is called before sending
2. Check trait is properly imported
3. Verify constructor doesn't have errors

```php
// Correct implementation
public function __construct()
{
    $this->configureMetadataHash();
}
```

## Notification Issues

### Notification Emails Not Tracked

**Problem:** Notification emails aren't being recorded

**Checklist:**

1. ✅ Add `InteractsWithMail` trait to Notification
2. ✅ Call `setMail()` in constructor
3. ✅ Return Mailable from `toMail()`
4. ✅ Ensure underlying Mailable has `InteractsWithMailMetadata`

**Correct Implementation:**

```php
class YourNotification extends Notification
{
    use InteractsWithMail;

    public function __construct()
    {
        $this->setMail(new YourMailable());
    }

    public function toMail($notifiable): Mailable
    {
        return $this->getMail()->to($notifiable->email);
    }
}
```

### "Mail not set" Error

**Problem:** Exception thrown when sending notification

**Solution:**

Ensure `setMail()` is called in constructor:

```php
public function __construct()
{
    $this->setMail(new YourMailable()); // Don't forget this!
}
```

## Configuration Issues

### Configuration Not Applied

**Problem:** Changes to config file not taking effect

**Solutions:**

1. Clear config cache:

   ```bash
   php artisan config:clear
   ```

2. Verify file location: `config/mailhistory.php`

3. Check environment variables in `.env`

### Custom Model Not Working

**Problem:** Custom model not being used

**Solutions:**

1. Verify model extends base model:

   ```php
   class MailHistory extends \CleaniqueCoders\MailHistory\Models\MailHistory
   {
       // Your customizations
   }
   ```

2. Update configuration:

   ```php
   'model' => \App\Models\MailHistory::class,
   ```

3. Clear config cache:

   ```bash
   php artisan config:clear
   ```

## Performance Issues

### Slow Database Queries

**Problem:** Queries taking too long

**Solutions:**

1. **Add Indexes:**

   ```php
   Schema::table('mail_histories', function (Blueprint $table) {
       $table->index('created_at');
       $table->index('status');
   });
   ```

2. **Clean Old Records:**

   ```php
   MailHistory::where('created_at', '<', now()->subDays(30))->delete();
   ```

3. **Use Chunking:**

   ```php
   MailHistory::where('status', 'Sent')->chunk(100, function ($emails) {
       // Process emails
   });
   ```

### High Database Storage

**Problem:** `mail_histories` table growing too large

**Solutions:**

1. **Schedule Regular Cleanup:**

   ```php
   // In app/Console/Kernel.php
   $schedule->call(function () {
       MailHistory::where('created_at', '<', now()->subDays(30))->delete();
   })->daily();
   ```

2. **Archive Old Records:**

   ```bash
   php artisan mailhistory:clear
   ```

3. **Optimize Table:**

   ```sql
   OPTIMIZE TABLE mail_histories;
   ```

## Testing Issues

### Tests Failing

**Problem:** Tests related to mail history fail

**Solutions:**

1. **Use RefreshDatabase:**

   ```php
   use RefreshDatabase;
   ```

2. **Disable Tracking in Tests:**

   ```php
   public function setUp(): void
   {
       parent::setUp();
       config(['mailhistory.enabled' => false]);
   }
   ```

3. **Clear Test Database:**

   ```bash
   php artisan migrate:fresh --env=testing
   ```

### Test Command Not Working

**Problem:** `mailhistory:test` fails

**Solutions:**

1. Create test user:

   ```php
   User::create([
       'name' => 'Test User',
       'email' => 'test@example.com',
       'password' => bcrypt('password'),
   ]);
   ```

2. Verify user model path in config:

   ```php
   'user-model' => '\App\Models\User',
   ```

3. Check queue workers are running

## Debugging

### Enable Debug Mode

```php
// In .env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Check Logs

```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# View queue logs
tail -f storage/logs/queue.log
```

### Inspect Records

```php
// Latest mail history
$latest = MailHistory::latest()->first();
dd($latest->toArray());

// Check specific hash
$email = MailHistory::where('hash', 'your-hash')->first();
dd($email);
```

### Verify Events

```bash
# List all events
php artisan event:list | grep Mail
```

## Common Error Messages

### "Class 'MailHistory' not found"

**Solution:** Import the model:

```php
use CleaniqueCoders\MailHistory\Models\MailHistory;
```

### "Call to undefined method configureMetadataHash()"

**Solution:** Add the trait:

```php
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;
```

### "SQLSTATE[42S02]: Base table or view not found"

**Solution:** Run migrations:

```bash
php artisan migrate
```

## Getting Help

If you're still experiencing issues:

1. **Check Documentation:** Review relevant sections in this documentation

2. **Search Issues:** Look for similar issues on [GitHub](https://github.com/cleaniquecoders/mail-history/issues)

3. **Enable Debugging:** Set `APP_DEBUG=true` and check logs

4. **Create Issue:** If problem persists, [open an issue](https://github.com/cleaniquecoders/mail-history/issues/new) with:
   - Laravel version
   - Package version
   - PHP version
   - Error messages
   - Steps to reproduce

## Prevention Tips

1. **Always Clear Cache After Changes:**

   ```bash
   php artisan config:clear
   ```

2. **Use Version Control:** Track configuration changes in git

3. **Monitor Queue Workers:** Ensure they're running in production

4. **Regular Maintenance:** Schedule cleanup tasks

5. **Test After Updates:** Run tests after updating the package

## Next Steps

- Review [Architecture Overview](../03-architecture/01-overview.md)
- Check [Configuration Reference](../03-architecture/04-configuration-reference.md)
- See [Testing Guide](./02-testing.md)
