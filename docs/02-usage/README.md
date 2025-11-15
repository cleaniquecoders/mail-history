# Usage

This section provides comprehensive guides on using all features of the Mail History package.

## Table of Contents

1. [Mail Tracking](./01-mail-tracking.md) - Track emails sent via Mailable classes
2. [Notification Tracking](./02-notification-tracking.md) - Track emails sent via Notification classes
3. [Artisan Commands](./03-artisan-commands.md) - Available CLI commands

## Overview

Mail History provides two main tracking capabilities:

### Mail Tracking

Track emails sent directly using Laravel's Mailable classes. This includes:

- Automatic capture of email metadata
- Hash-based identification for tracking email lifecycle
- Support for queued emails
- Full email content storage

### Notification Tracking

Track emails sent through Laravel's Notification system. This includes:

- Integration with existing notification classes
- Automatic tracking without changing notification logic
- Support for multi-channel notifications
- Queue-aware tracking

### Command Line Tools

The package includes Artisan commands for:

- Testing mail and notification tracking
- Cleaning up old mail history records
- Verifying configuration

## Common Use Cases

### Development and Testing

Use Mail History to:

- Debug email sending issues
- Verify email content before production
- Test queue configurations
- Monitor email delivery status

### Production Monitoring

Use Mail History to:

- Track email delivery success rates
- Identify failed email attempts
- Audit email communications
- Generate email delivery reports

## Integration Patterns

Mail History integrates seamlessly with:

- **Laravel Queues** - Automatically tracks queued emails
- **Email Drivers** - Works with all Laravel mail drivers (SMTP, Mailgun, SES, etc.)
- **Notification Channels** - Tracks mail channel notifications
- **Testing** - Compatible with Laravel's mail testing features

## What's Included

Each tracking method captures:

- **Headers** - Full email headers including recipients, subject, etc.
- **Body** - Plain text email body
- **Content** - Both text and HTML versions
- **Metadata** - Custom metadata and tracking hash
- **Status** - Current email status (Sending/Sent)
- **Timestamps** - When the email was created and updated

## Next Steps

- Start with [Mail Tracking](./01-mail-tracking.md) to track Mailable classes
- Learn [Notification Tracking](./02-notification-tracking.md) for notifications
- Explore [Artisan Commands](./03-artisan-commands.md) for CLI tools
