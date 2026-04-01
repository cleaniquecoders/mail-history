# Provider Reference

Detailed reference for each supported email provider's webhook format, event types, and hash extraction.

## Event Type Mapping

Each provider uses different names for the same events. The handlers normalize them:

| Normalized Type | Mailgun | SES | Postmark | SendGrid | Resend |
|----------------|---------|-----|----------|----------|--------|
| `delivered` | `delivered` | `Delivery` | `Delivery` | `delivered` | `email.delivered` |
| `opened` | `opened` | `Open` | `Open` | `open` | `email.opened` |
| `clicked` | `clicked` | `Click` | `Click` | `click` | `email.clicked` |
| `bounced` | `bounced` | `Bounce` | `Bounce` | `bounce` | `email.bounced` |
| `complained` | `complained` | `Complaint` | `SpamComplaint` | `spamreport` | `email.complained` |
| `failed` | `failed` | `Reject` | — | `dropped` | — |

## Mailgun

### Hash Extraction

The handler looks for the hash in this order:

1. `event-data.user-variables.hash` (from `X-Mailgun-Variables` header)
2. `event-data.message.headers.X-Metadata-hash`
3. `event-data.message.headers.x-metadata-hash`

### Payload Structure

```json
{
  "signature": {
    "timestamp": "1700000000",
    "token": "random-token",
    "signature": "hmac-sha256-hex"
  },
  "event-data": {
    "event": "delivered",
    "timestamp": 1700000000,
    "ip": "1.2.3.4",
    "user-variables": { "hash": "abc123" },
    "message": { "headers": {} },
    "client-info": { "user-agent": "..." }
  }
}
```

### Extracted Fields

| Field | Source |
|-------|--------|
| `occurred_at` | `event-data.timestamp` (Unix) |
| `ip_address` | `event-data.ip` |
| `user_agent` | `event-data.client-info.user-agent` |
| `url` | `event-data.url` (click events) |

## Amazon SES (via SNS)

### Hash Extraction

1. `mail.headers[].value` where `name` = `X-Metadata-hash`
2. `mail.tags.hash[0]` (from `X-SES-MESSAGE-TAGS`)

### SNS Wrapper

SES events arrive wrapped in an SNS notification:

```json
{
  "Type": "Notification",
  "MessageId": "...",
  "Message": "{\"eventType\":\"Delivery\",\"mail\":{...}}"
}
```

The `Message` field is a JSON string that must be decoded.

### SNS Subscription Confirmation

When you first subscribe an SNS topic, AWS sends a `SubscriptionConfirmation` request. The handler automatically confirms it by fetching the `SubscribeURL`.

### Extracted Fields

| Field | Source |
|-------|--------|
| `occurred_at` | `mail.timestamp` |
| `url` | `click.link` (click events) |

## Postmark

### Hash Extraction

1. `Metadata.hash` (from `X-PM-Metadata-hash` header)
2. `Headers[].Value` where `Name` = `X-Metadata-hash`

### Payload Structure

```json
{
  "RecordType": "Delivery",
  "DeliveredAt": "2024-01-15T10:00:00Z",
  "Metadata": { "hash": "abc123" },
  "Headers": [
    { "Name": "X-Metadata-hash", "Value": "abc123" }
  ],
  "Geo": { "IP": "1.2.3.4" },
  "UserAgent": "Mozilla/5.0"
}
```

### Extracted Fields

| Field | Source |
|-------|--------|
| `occurred_at` | `DeliveredAt` / `BouncedAt` / `ReceivedAt` |
| `ip_address` | `Geo.IP` |
| `user_agent` | `UserAgent` |
| `url` | `OriginalLink` (click events) |

## SendGrid

### Hash Extraction

1. `hash` (top-level, from unique args)
2. `unique_args.hash` (from `X-SMTPAPI` header)

### Payload Structure

SendGrid sends an **array** of events per request:

```json
[
  {
    "event": "delivered",
    "timestamp": 1700000000,
    "hash": "abc123",
    "ip": "5.6.7.8",
    "useragent": "Mozilla/5.0",
    "url": "https://example.com"
  },
  {
    "event": "open",
    "timestamp": 1700000100,
    "hash": "abc123"
  }
]
```

### Extracted Fields

| Field | Source |
|-------|--------|
| `occurred_at` | `timestamp` (Unix) |
| `ip_address` | `ip` |
| `user_agent` | `useragent` |
| `url` | `url` (click events) |

## Resend

### Hash Extraction

1. `data.headers.X-Metadata-hash` or `data.headers.x-metadata-hash`
2. `data.tags[].value` where `name` = `hash`

### Payload Structure

```json
{
  "type": "email.delivered",
  "data": {
    "created_at": "2024-01-15T10:00:00Z",
    "headers": {
      "X-Metadata-hash": "abc123"
    },
    "tags": [
      { "name": "hash", "value": "abc123" }
    ]
  }
}
```

### Svix Signature Headers

```
svix-id: msg_abc123
svix-timestamp: 1700000000
svix-signature: v1,base64encodedhmac
```

### Extracted Fields

| Field | Source |
|-------|--------|
| `occurred_at` | `data.created_at` or `created_at` |
| `url` | `data.click.link` (click events) |

## Next Steps

- See [Commands](./06-commands.md) for stats, pruning, and testing
- Return to [Webhook Setup](./02-webhook-setup.md) for configuration steps
