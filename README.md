# Mail History

This package will allow you to capture any mail send out either using Mail or Notification features in Laravel.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/mailhistory.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/mailhistory) [![PHPStan](https://github.com/cleaniquecoders/mail-history/actions/workflows/phpstan.yml/badge.svg)](https://github.com/cleaniquecoders/mail-history/actions/workflows/phpstan.yml) [![run-tests](https://github.com/cleaniquecoders/mail-history/actions/workflows/run-tests.yml/badge.svg)](https://github.com/cleaniquecoders/mail-history/actions/workflows/run-tests.yml) [![Fix PHP code style issues](https://github.com/cleaniquecoders/mail-history/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/cleaniquecoders/mail-history/actions/workflows/fix-php-code-style-issues.yml) [![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/mailhistory.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/mailhistory)

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

If you need to configure more, do publish the config file and update the config file as neccessary.

```bash
php artisan vendor:publish --tag="mailhistory-config"
```

## Usage

We need to configure two parts - Mail & Notification.

### Mail

All mails are required to use mail metadata trait.

```php
<?php

namespace App\Mail;

use CleaniqueCoders\MailHistory\Concerns\InteractsWithMailMetadata;

class DefaultMail extends Mailable
{
    use InteractsWithMailMetadata, SerializesModels;
```

And in your mail constructor, do call the following method:

```php
public function __construct()
{
    $this->configureMetadataHash();
}
```

With this setup, we can track which email has been sent or still sending.

### Notification

> Do configure you mail prior to this step.
> At the moment, it only works with Mailable, not Mail Message class.

For notifications classes, you will need to add the following trait:

```php
use CleaniqueCoders\MailHistory\Concerns\InteractsWithMail;

class DefaultNotification extends Notification
{
    use InteractsWithMail;
```

Then in your notification constructor, you need to initialise the mail object.

```php
public function __construct()
{
    $this->setMail(
        new \App\Mails\DefaultMail
    );
}
```

And update the `toMail()` as following:

```php
/**
 * Get the mail representation of the notification.
 */
public function toMail(object $notifiable): Mailable
{
    return $this->getMail()->to($notifiable->email);
}
```

### Artisan Commands

If you need to clean up your mail history table, you can run:

```bash
php artisan mailhistory:clear
```

If you need to test the mail or notification:

```bash
php artisan mailhistory:test you-email@app.com --mail
php artisan mailhistory:test you-email@app.com --notification
```

> Do take note, for mail testing, you need a user record that have the email address as we are using Notifiable trait.

You may run the test using specified queue:

```bash
php artisan mailhistory:test you-email@app.com --mail --queue=mail
```

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
