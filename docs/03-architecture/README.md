# Architecture

This section provides a deep dive into the internal architecture and design of the Mail History package.

## Table of Contents

1. [Overview](./01-overview.md) - System architecture and design patterns
2. [Event System](./02-event-system.md) - Laravel event listeners and flow
3. [Database Schema](./03-database-schema.md) - Database structure and relationships
4. [Configuration Reference](./04-configuration-reference.md) - Complete configuration guide

## Architecture Principles

Mail History is built on these core principles:

### Event-Driven Design

The package uses Laravel's event system to capture email metadata without modifying your existing code. It listens to:

- Mail events (MessageSending, MessageSent)
- Notification events (NotificationSending, NotificationSent)

### Trait-Based Integration

Integration is achieved through PHP traits:

- `InteractsWithMailMetadata` - For Mailable classes
- `InteractsWithMail` - For Notification classes
- `InteractsWithHash` - For hash-based querying

### Minimal Configuration

The package follows Laravel's convention over configuration principle, requiring minimal setup while remaining customizable.

### Queue-Aware

Built with queue systems in mind, tracking both synchronous and asynchronous email delivery.

## Package Structure

```
src/
├── Commands/              # Artisan commands
├── Concerns/              # Reusable traits
├── Exceptions/            # Custom exceptions
├── Facades/               # Laravel facades
├── Listeners/             # Event listeners
│   ├── Mails/            # Mail event listeners
│   └── Notifications/    # Notification event listeners
├── Mail/                  # Test mailable classes
├── Models/                # Eloquent models
├── Notifications/         # Test notification classes
├── MailHistory.php        # Main package class
└── MailHistoryServiceProvider.php  # Service provider
```

## Key Components

### Service Provider

`MailHistoryServiceProvider` registers:

- Configuration
- Migrations
- Commands
- Event listeners

### Event Listeners

Four listeners capture email lifecycle events:

- `StoreMessageSending` - Captures mail being sent
- `StoreMessageSent` - Updates mail status to sent
- `StoreNotificationMessageSending` - Captures notification mail being sent
- `StoreNotificationMessageSent` - Updates notification mail status

### Models

`MailHistory` model provides:

- Database interaction
- UUID generation
- Hash-based querying
- JSON casting for metadata

### Traits

Traits provide:

- Metadata management
- Hash generation
- Mail object handling

## Data Flow

### Mail Flow

```
Mailable Created
    ↓
configureMetadataHash() called
    ↓
Mail::send() triggered
    ↓
MessageSending event fired
    ↓
StoreMessageSending listener creates record (status: Sending)
    ↓
Email sent via driver
    ↓
MessageSent event fired
    ↓
StoreMessageSent listener updates record (status: Sent)
```

### Notification Flow

```
Notification Created
    ↓
setMail() called with Mailable
    ↓
User->notify() triggered
    ↓
NotificationSending event fired
    ↓
StoreNotificationMessageSending listener creates record (status: Sending)
    ↓
Email sent via mail channel
    ↓
NotificationSent event fired
    ↓
StoreNotificationMessageSent listener updates record (status: Sent)
```

## Design Patterns

### Observer Pattern

Event listeners observe Laravel's mail and notification events.

### Strategy Pattern

Different listeners handle mail vs notification events.

### Factory Pattern

Service provider registers and creates event listeners.

### Repository Pattern

MailHistory model encapsulates data access logic.

## Extension Points

The package can be extended at these points:

### Custom Model

Extend the MailHistory model for custom behavior:

```php
namespace App\Models;

use CleaniqueCoders\MailHistory\Models\MailHistory as BaseMailHistory;

class MailHistory extends BaseMailHistory
{
    // Custom methods and relationships
}
```

### Custom Event Listeners

Add custom listeners in configuration:

```php
'events' => [
    MessageSent::class => [
        StoreMailMessageSent::class,
        YourCustomListener::class, // Add here
    ],
],
```

### Custom Hash Generation

Override hash generation:

```php
public function __construct()
{
    $this->setMetadataHash('custom-hash-value');
}
```

## Performance Considerations

### Database Indexes

The `hash` column is indexed for fast lookups.

### JSON Columns

Header, content, and meta use JSON columns for flexible storage.

### Queue Support

Event listeners can be queued for better performance.

## Security Considerations

### Data Storage

Email content includes sensitive information. Consider:

- Encryption at rest
- Access control
- Regular cleanup of old records
- GDPR compliance

### Hash Security

Hashes use SHA-1 by default. For sensitive applications, consider custom hash generation with stronger algorithms.

## Next Steps

- Read the [Overview](./01-overview.md) for detailed architecture
- Understand the [Event System](./02-event-system.md)
- Review the [Database Schema](./03-database-schema.md)
- Check the [Configuration Reference](./04-configuration-reference.md)
