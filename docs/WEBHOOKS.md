# Webhooks Guide

For developers integrating with this platform's outgoing webhooks —
receiving real-time notifications when events happen in your account,
rather than polling the API.

## Registering a webhook

```bash
curl -X POST https://yourdomain.com/api/v1/webhooks \
  -H "Authorization: Bearer <your JWT>" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-server.com/webhooks/chatbot-saas",
    "events": ["bot.created", "lead.created", "chat.completed"]
  }'
```

Response (**save the `secret` — it is never shown again**):

```json
{
  "status": 201,
  "data": {
    "id": "a1b2c3d4-...",
    "url": "https://your-server.com/webhooks/chatbot-saas",
    "events": ["bot.created", "lead.created", "chat.completed"],
    "is_active": true,
    "secret": "f3a9...64-hex-chars",
    "created_at": "2026-07-09 12:00:00"
  }
}
```

## Supported events

| Event | Fires when |
|---|---|
| `user.created` | A new account registers |
| `bot.created` | A bot is created |
| `bot.deleted` | A bot is deleted |
| `knowledge.uploaded` | A knowledge source finishes processing (text, Q&A, document, or website) |
| `chat.started` | The first message of a new conversation is sent |
| `chat.completed` | Any assistant reply is generated (streaming or not) |
| `lead.created` | A visitor submits contact info through a bot's widget |
| `subscription.created` | A user subscribes to a plan |
| `subscription.updated` | A subscription is canceled, renewed, or its status otherwise changes |

Use `GET /api/v1/webhooks/events` to fetch this list programmatically
— it's the source of truth if this document ever drifts from the code.

## Payload shape

Every event delivers the same envelope:

```json
{
  "event": "bot.created",
  "timestamp": "2026-07-09T12:00:00+00:00",
  "data": {
    "bot_id": "b7e1...",
    "name": "Support Bot",
    "user_id": 42
  }
}
```

`data`'s inner shape varies per event — see `app/Services/WebhookDispatcherService.php`'s
call sites for the exact fields each event includes, or trigger each
event once against a test endpoint (e.g. webhook.site) and inspect
what arrives.

## Verifying the signature

Every delivery includes an `X-Webhook-Signature` header:
`HMAC-SHA256(raw_request_body, your_secret)`, hex-encoded. Verify it
before trusting the payload:

```php
// PHP receiver example
$payload = file_get_contents('php://input');
$expected = hash_hmac('sha256', $payload, $yourStoredSecret);
$received = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

if (!hash_equals($expected, $received)) {
    http_response_code(401);
    exit;
}
```

```javascript
// Node.js / Express example
const crypto = require('crypto');

app.post('/webhooks/chatbot-saas', express.raw({ type: '*/*' }), (req, res) => {
  const expected = crypto.createHmac('sha256', YOUR_SECRET).update(req.body).digest('hex');
  const received = req.headers['x-webhook-signature'];

  if (!crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(received || ''))) {
    return res.sendStatus(401);
  }

  const event = JSON.parse(req.body);
  // handle event.event / event.data
  res.sendStatus(200);
});
```

Always use a constant-time comparison (`hash_equals` / `timingSafeEqual`),
never `===`/`==` — naive string comparison is vulnerable to timing
attacks that can leak the signature byte-by-byte.

## Delivery behavior

- **Timeout**: your endpoint has 5 seconds to respond. Respond fast — do any slow processing asynchronously on your end and return `200` immediately.
- **Success**: any `2xx` status code.
- **Retries**: a non-2xx response or timeout is retried up to 4 more times (5 attempts total), roughly every 15 minutes, via a background cron job — not immediately in a tight loop.
- **Ordering**: not guaranteed across events. If your integration needs strict ordering, use the `timestamp` field to reorder on your end.
- **At-least-once delivery**: design your receiver to be idempotent (safe to process the same event twice) — network issues can cause a successful delivery to be retried if the retry-triggering condition (e.g., a slow response that arrived just after our timeout) is a false negative on our end.

## Managing webhooks

```
GET    /api/v1/webhooks              List your webhooks
PUT    /api/v1/webhooks/{id}         Toggle active/inactive: { "is_active": false }
DELETE /api/v1/webhooks/{id}         Remove a webhook
GET    /api/v1/webhooks/{id}/logs    Paginated delivery history (status, response code, attempt count)
```

There is no "reveal secret again" endpoint — if you lose it, delete
the webhook and register a new one.

## Security notes

- Your endpoint URL is validated against internal/private IP ranges at registration time and again before every delivery (see `SECURITY.md`'s SSRF section) — you cannot register a webhook pointing at an internal address. Use a real public HTTPS endpoint.
- Always verify the signature. An attacker who knows (or guesses) your webhook URL could otherwise send you fabricated events.
- Use HTTPS for your receiver — there's no reason to send the payload over plaintext HTTP.
