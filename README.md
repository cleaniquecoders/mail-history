# Keep track all the emails sent in the your Laravel application.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/mailhistory.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/mailhistory)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/mailhistory/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/mailhistory/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/mailhistory/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/mailhistory/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/mailhistory.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/mailhistory)

## Installation

You can install the package via composer:

```bash
composer require cleaniquecoders/mailhistory
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="mailhistory-migrations"
php artisan migrate
```

Next, open your `app/Providers/EventServiceProvider.php` and update the `$listen` property as following:

```php
protected $listen = [
    \Illuminate\Mail\Events\MessageSending::class => [
        \CleaniqueCoders\MailHistory\Listeners\StoreMessageSending::class,
    ],
    \Illuminate\Mail\Events\MessageSent::class => [
        \CleaniqueCoders\MailHistory\Listeners\StoreMessageSent::class,
    ],
];
```

## Usage

Developers don't need to do anything, since all the records will capture by the listeners.

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
