# Mail History Documentation

Welcome to the Mail History package documentation. This Laravel package allows you to capture and track any mail sent out using Laravel's Mail or Notification features.

## Documentation Structure

This documentation is organized into the following sections:

### [01. Getting Started](./01-getting-started/README.md)

Learn how to install and configure the Mail History package in your Laravel application.

- [Installation](./01-getting-started/01-installation.md)
- [Configuration](./01-getting-started/02-configuration.md)
- [Quick Start](./01-getting-started/03-quick-start.md)

### [02. Usage](./02-usage/README.md)

Comprehensive guides on using the package features.

- [Mail Tracking](./02-usage/01-mail-tracking.md)
- [Notification Tracking](./02-usage/02-notification-tracking.md)
- [Artisan Commands](./02-usage/03-artisan-commands.md)

### [03. Architecture](./03-architecture/README.md)

Deep dive into the package's internal architecture and design.

- [Overview](./03-architecture/01-overview.md)
- [Event System](./03-architecture/02-event-system.md)
- [Database Schema](./03-architecture/03-database-schema.md)
- [Configuration Reference](./03-architecture/04-configuration-reference.md)

### [04. Advanced](./04-advanced/README.md)

Advanced topics and customization options.

- [Custom Hash Generation](./04-advanced/01-custom-hash.md)
- [Testing Guide](./04-advanced/02-testing.md)
- [Troubleshooting](./04-advanced/03-troubleshooting.md)

### [05. Delivery Tracking](./05-delivery-tracking/README.md)

Post-delivery email tracking via provider webhooks, open pixels, and click tracking.

- [Overview](./05-delivery-tracking/01-overview.md) - Architecture and status lifecycle
- [Webhook Setup](./05-delivery-tracking/02-webhook-setup.md) - Mailgun, SES, Postmark, SendGrid, Resend
- [Open Tracking](./05-delivery-tracking/03-open-tracking.md) - Pixel-based open detection
- [Click Tracking](./05-delivery-tracking/04-click-tracking.md) - Link click tracking with redirect
- [Provider Reference](./05-delivery-tracking/05-provider-reference.md) - Payload formats and event mapping
- [Commands](./05-delivery-tracking/06-commands.md) - Stats, pruning, and test webhooks
- [Reporting & Statistics](./05-delivery-tracking/07-reporting.md) - Action-based reporting for dashboards
- [Dashboard UI](./05-delivery-tracking/08-dashboard-ui.md) - Built-in Livewire dashboard

## Quick Links

- [Upgrade Guide](../UPGRADE.md)
- [GitHub Repository](https://github.com/cleaniquecoders/mail-history)
- [Packagist](https://packagist.org/packages/cleaniquecoders/mailhistory)
- [Report Issues](https://github.com/cleaniquecoders/mail-history/issues)
- [Changelog](../CHANGELOG.md)
- [Contributing Guide](../CONTRIBUTING.md)
- [License](../LICENSE.md)

## Getting Help

If you encounter any issues or have questions:

1. Check the [Troubleshooting Guide](./04-advanced/03-troubleshooting.md)
2. Review existing [GitHub Issues](https://github.com/cleaniquecoders/mail-history/issues)
3. Create a new issue if needed

## About

Mail History is developed and maintained by [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim) and [contributors](https://github.com/cleaniquecoders/mail-history/contributors).

Licensed under the [MIT License](../LICENSE.md).
