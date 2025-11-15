# Installation

This guide walks you through installing the Mail History package in your Laravel application.

## Requirements

- PHP 8.1 or higher
- Laravel 9.x, 10.x, or 11.x
- Composer

## Installation Steps

### 1. Install via Composer

Install the package using Composer:

```bash
composer require cleaniquecoders/mailhistory
```

The package will be automatically discovered by Laravel's package auto-discovery feature.

### 2. Publish the Migration

Publish the migration file to your application:

```bash
php artisan vendor:publish --tag="mailhistory-migrations"
```

This will create a migration file in your `database/migrations` directory.

### 3. Run the Migration

Execute the migration to create the `mail_histories` table:

```bash
php artisan migrate
```

The migration creates a table with the following structure:

- `id` - Primary key
- `uuid` - Unique identifier for each record
- `hash` - Indexed hash for tracking email status
- `status` - Email status (Sending or Sent)
- `headers` - JSON field for email headers
- `body` - Text field for email body
- `content` - JSON field containing text, text-charset, and html
- `meta` - JSON field for additional metadata
- `timestamps` - Created and updated timestamps

### 4. Publish Configuration (Optional)

If you need to customize the package configuration, publish the config file:

```bash
php artisan vendor:publish --tag="mailhistory-config"
```

This will create a `config/mailhistory.php` file in your application.

## Verification

To verify the installation was successful:

1. Check that the `mail_histories` table exists in your database
2. Verify the config file is published (if you published it)
3. Run the test command:

```bash
php artisan mailhistory:test your-email@example.com --mail
```

Note: For mail testing, you need a user record with the specified email address.

## What's Next?

Now that you've installed the package, proceed to [Configuration](./02-configuration.md) to learn about available configuration options.
