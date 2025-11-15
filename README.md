# Mail History

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/mailhistory.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/mailhistory) [![PHPStan](https://github.com/cleaniquecoders/mail-history/actions/workflows/phpstan.yml/badge.svg)](https://github.com/cleaniquecoders/mail-history/actions/workflows/phpstan.yml) [![run-tests](https://github.com/cleaniquecoders/mail-history/actions/workflows/run-tests.yml/badge.svg)](https://github.com/cleaniquecoders/mail-history/actions/workflows/run-tests.yml) [![Fix PHP code style issues](https://github.com/cleaniquecoders/mail-history/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/cleaniquecoders/mail-history/actions/workflows/fix-php-code-style-issues.yml) [![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/mailhistory.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/mailhistory)

A Laravel package for automatically tracking emails sent through Mail and Notification features. Capture email metadata, monitor delivery status, and maintain a complete history of your application's email communications.

## Features

- 🚀 **Automatic Tracking** - Captures email metadata without changing existing code
- 📊 **Status Monitoring** - Tracks email lifecycle from "Sending" to "Sent"
- 🔍 **Hash-based Identification** - Unique identifiers for each email
- ⚡ **Queue Support** - Works seamlessly with Laravel's queue system
- 🎯 **Mailable & Notification Support** - Track both mail types
- 🛠️ **Artisan Commands** - Built-in testing and maintenance tools

## Quick Start

### Installation

```bash
composer require cleaniquecoders/mailhistory
php artisan vendor:publish --tag="mailhistory-migrations"
php artisan migrate
```

### Basic Usage

Add the trait to your Mailable:

```php
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;

class WelcomeMail extends Mailable
{
    use InteractsWithMailMetadata;

    public function __construct()
    {
        $this->configureMetadataHash();
    }
}
```

That's it! Your emails are now being tracked automatically.

## Documentation

Comprehensive documentation is available in the `docs/` directory:

### 📚 [Complete Documentation](./docs/README.md)

- **[Getting Started](./docs/01-getting-started/README.md)** - Installation, configuration, and quick start
- **[Usage Guide](./docs/02-usage/README.md)** - Mail tracking, notifications, and commands
- **[Architecture](./docs/03-architecture/README.md)** - Technical deep-dive and design patterns
- **[Advanced Topics](./docs/04-advanced/README.md)** - Custom hashes, testing, and troubleshooting

### Quick Links

- [Installation Guide](./docs/01-getting-started/01-installation.md)
- [Configuration](./docs/01-getting-started/02-configuration.md)
- [Mail Tracking](./docs/02-usage/01-mail-tracking.md)
- [Notification Tracking](./docs/02-usage/02-notification-tracking.md)
- [Artisan Commands](./docs/02-usage/03-artisan-commands.md)
- [Troubleshooting](./docs/04-advanced/03-troubleshooting.md)

## Requirements

- PHP 8.1 or higher
- Laravel 9.x, 10.x, or 11.x

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
