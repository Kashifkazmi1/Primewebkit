# API Reference

Base URL: `https://your-domain.com/api/v1`

All requests/responses use `Content-Type: application/json`. Every
response follows the same envelope:

```json
{
  "status": 200,
  "success": true,
  "message": "Human readable message.",
  "data": {},
  "errors": {},
  "pagination": null
}
```

- `errors` is an object keyed by field name with a list of messages, populated on `422` validation failures.
- `pagination` is populated (`{ total, page, per_page, last_page }`) on paginated list endpoints (introduced from Phase 3 onward).
- Authenticated endpoints require `Authorization: Bearer <access_token>`.

## Rate limiting

Every response includes `X-RateLimit-Limit` and `X-RateLimit-Remaining`
headers. Exceeding the limit returns `429` with a `Retry-After` header
(seconds). The `/auth/login` endpoint additionally enforces a
stricter, per-IP-and-email throttle independent of the general API
limit, and locks the account server-side after repeated failures
(see **Account lockout** below).

---

## Health

### `GET /health`

Public. Returns service + database status. No authentication required.

---

## Authentication

### `POST /auth/register`

Public.

**Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "timezone": "UTC",
  "locale": "en"
}
```

**201 response `data`:** `{ user, access_token, refresh_token, token_type, expires_in }`

The new account is created with `status = "pending"` and role `user`.
A verification email is sent; `status` becomes `"active"` once
`GET /auth/verify-email/{token}` is called successfully.

### `POST /auth/login`

Public.

**Body:** `{ "email": "...", "password": "..." }`

**200 response `data`:** `{ user, access_token, refresh_token, token_type, expires_in }`

**Account lockout:** after `SECURITY_LOGIN_MAX_ATTEMPTS` (default 5)
consecutive failed attempts, the account is locked for
`SECURITY_LOGIN_LOCKOUT_MINUTES` (default 15) — further attempts
return `401` with error code `ACCOUNT_LOCKED` even with the correct
password, until the lockout expires. A successful login resets the
counter.

### `POST /auth/refresh`

Public (requires a valid refresh token, not an access token).

**Body:** `{ "refresh_token": "..." }`

Rotates the refresh token (the old one is revoked) and returns a new
`{ access_token, refresh_token, expires_in }`. The old refresh token
cannot be reused after this call.

### `POST /auth/logout`

Public (requires a valid refresh token). Revokes that single session.

**Body:** `{ "refresh_token": "..." }`

### `POST /auth/logout-all` 🔒

Authenticated. Revokes **every** active session/refresh token for the
current user (all devices).

### `POST /auth/forgot-password`

Public.

**Body:** `{ "email": "..." }`

Always returns `200` with the same message whether or not the email
exists, to prevent account enumeration. If the account exists, a
password-reset email is sent (link valid 60 minutes, single use).

### `POST /auth/reset-password`

Public.

**Body:** `{ "token": "...", "password": "...", "password_confirmation": "..." }`

On success, the password is updated and **every** existing session for
that user is revoked (forces re-login everywhere).

### `POST /auth/resend-verification`

Public.

**Body:** `{ "email": "..." }`

Re-issues a verification email if the account exists and is not yet
verified. Same enumeration-safe response regardless.

### `GET /auth/verify-email/{token}`

Public. Marks the account's email as verified and activates the
account (`status: pending → active`). The token is single-use and
expires after 24 hours.

### `GET /auth/me` 🔒

Authenticated. Returns the current user's public profile.

### `PUT /auth/profile` 🔒

Authenticated.

**Body (all optional):** `{ "name": "...", "timezone": "...", "locale": "..." }`

### `POST /auth/change-password` 🔒

Authenticated.

**Body:** `{ "current_password": "...", "new_password": "...", "new_password_confirmation": "..." }`

Revokes all other sessions on success (current device stays signed in).

### `DELETE /auth/account` 🔒

Authenticated. Soft-deletes the account.

**Body:** `{ "password": "..." }`

---

## Error codes

Every error response's `errors` object (validation) or top-level
`message` (everything else) is paired with an internal `error_code`
concept surfaced via distinct HTTP statuses and messages. Notable
authentication error conditions:

| Situation | HTTP status | Message summary |
|---|---|---|
| Missing/invalid/expired JWT | 401 | Token missing / expired / malformed / invalid signature |
| Wrong email or password | 401 | The provided credentials are incorrect. |
| Account locked (too many failed logins) | 401 | This account is temporarily locked... |
| Account suspended (by an admin) | 401 | This account has been suspended. |
| Too many login attempts (IP+email throttle) | 401 | Too many login attempts. Please try again in a few minutes. |
| Refresh token invalid/expired/already rotated | 401 | The refresh token is invalid or has expired. |
| Email/password validation failure | 422 | Field-level messages in `errors` |
| Duplicate email on register | 422 | An account with this email address already exists. |
| General API rate limit exceeded | 429 | Too many requests. Please slow down... |

---

## Bots 🔒

All endpoints below require `Authorization: Bearer <access_token>` and are scoped to the authenticated user's own bots.

### `GET /bots`
Paginated list (`?page=1&per_page=15`).

### `POST /bots`
**Body:** `{ "name": "...", "description": "...", "system_prompt": "...", "temperature": 0.7, "welcome_message": "...", "primary_color": "#4f46e5" }` (only `name` required). Creates the bot (`status: draft`) plus a default widget configuration.

### `GET /bots/{uuid}`, `PUT /bots/{uuid}`, `DELETE /bots/{uuid}`
Standard CRUD. `PUT` accepts any subset of the create fields plus `status` (`draft|training|active|archived`) and `is_public`. A bot must be `status: active` before its public widget endpoints will serve traffic.

---

## Knowledge sources 🔒

### `GET /bots/{uuid}/knowledge-sources`
List all sources for a bot with status/chunk counts.

### `POST /bots/{uuid}/knowledge-sources/text`
**Body:** `{ "source_name": "...", "content": "..." }`. Chunked and marked `completed` synchronously.

### `POST /bots/{uuid}/knowledge-sources/qa`
**Body:** `{ "question": "...", "answer": "..." }`. Stored as a single `Q: ... \n A: ...` text source.

### `POST /bots/{uuid}/knowledge-sources/document`
Multipart upload, field name `file`. Accepts PDF, DOCX, TXT, MD, CSV — 20MB max. Extracted, chunked, and marked `completed`/`failed` synchronously in the same request.

### `POST /bots/{uuid}/knowledge-sources/website`
**Body:** `{ "start_url": "https://...", "max_pages": 20 }`. Returns immediately with `status: pending` — the crawl itself runs via the `bin/process-crawl-jobs.php` cron job, not inline. Poll `GET /bots/{uuid}/knowledge-sources` to see it transition to `completed`/`failed`.

### `DELETE /bots/{uuid}/knowledge-sources/{sourceUuid}`
Deletes the source and all its chunks.

---

## Widgets 🔒

### `GET /bots/{uuid}/widget`, `PUT /bots/{uuid}/widget`
**PUT body (all optional):** `{ "theme": "light|dark", "position": "bottom-right|bottom-left", "primary_color": "...", "greeting_message": "...", "placeholder_text": "...", "show_branding": true, "custom_css": "...", "allowed_domains": ["example.com"], "is_active": true }`. Leaving `allowed_domains` empty makes the widget embeddable on any domain.

### `GET /bots/{uuid}/widget/embed-script`
Returns a ready-to-paste `<script>` snippet pointing at the public config endpoint.

---

## Conversations & leads 🔒 (dashboard views)

### `GET /bots/{uuid}/conversations` — paginated list
### `GET /bots/{uuid}/conversations/{conversationUuid}` — full message history
### `POST /bots/{uuid}/conversations/{conversationUuid}/close` — marks closed
### `GET /bots/{uuid}/leads` — paginated list of captured leads

---

## Personal API keys 🔒

### `GET /api-keys` — list (never returns the raw key, only the display prefix)
### `POST /api-keys` — **Body:** `{ "name": "...", "expires_at": null }`. Response includes the raw key **once**: `{ "id", "name", "key", "key_prefix", "created_at" }`.
### `DELETE /api-keys/{uuid}` — revokes

---

## Public widget endpoints (embedded on customer sites)

No login — these are called by the embed script running on a third
party's website. Restricted per-bot by `widgets.allowed_domains`
(checked against the `Origin`/`Referer` header); returns `403` if the
calling domain isn't allow-listed.

### `GET /widget/{botUuid}/config`
Returns the bot's public-safe display info + widget appearance config. `404` if the bot isn't `status: active`.

### `POST /widget/{botUuid}/messages`
Superseded by the full AI-generating version documented under **Chat (public widget, AI-generating)** below — kept here as the anchor for the base behavior (session/visitor resolution, message persistence). See that section for the complete request/response shape including the AI-generated reply.

### `POST /widget/{botUuid}/leads`
**Body:** `{ "session_id": "...", "name": "...", "email": "...", "phone": "..." }` (all contact fields optional, but at least a way to reach the visitor is expected in practice).

---

## Bot AI settings 🔒

Configured via the existing `PUT /bots/{uuid}` (Phase 3) — no separate
endpoint. Additional fields beyond Phase 3's `temperature`/`max_output_tokens`:

```json
{
  "top_p": 0.95,
  "top_k": 40,
  "safety_settings": [
    { "category": "HARM_CATEGORY_HARASSMENT", "threshold": "BLOCK_MEDIUM_AND_ABOVE" }
  ],
  "language": "en",
  "personality": "friendly and concise",
  "tone": "casual"
}
```

`safety_settings` categories/thresholds follow Gemini's API values
(`HARM_CATEGORY_HARASSMENT`, `HARM_CATEGORY_HATE_SPEECH`,
`HARM_CATEGORY_SEXUALLY_EXPLICIT`, `HARM_CATEGORY_DANGEROUS_CONTENT`;
thresholds `BLOCK_NONE` | `BLOCK_ONLY_HIGH` | `BLOCK_MEDIUM_AND_ABOVE` | `BLOCK_LOW_AND_ABOVE`).
Leave empty to use the platform defaults in `config/ai.php`.

"Creativity" maps to `temperature` (0-2, default 0.7) — there is no
separate creativity field.

### `POST /bots/{uuid}/reembed` 🔒

Re-generates embeddings for every knowledge-base chunk this bot has.
Use after changing the embedding model/provider — existing vectors
aren't comparable to queries embedded with a different model.

**200 response `data`:** `{ "embedded": <int>, "failed": <int> }`

---

## AI usage 🔒

### `GET /bots/{uuid}/usage`
Paginated raw log (`?page=1&per_page=20`) — one row per AI provider call.

### `GET /bots/{uuid}/usage/summary`
**Query params (optional):** `from`, `to` (datetime strings).

**200 response `data`:** `{ total_requests, total_prompt_tokens, total_completion_tokens, total_tokens, total_cost, failed_requests }`

---

## Chat (public widget, AI-generating)

These four endpoints sit behind both `WidgetOriginMiddleware`
(domain allow-list) **and** `AIChatRateLimitMiddleware` (stricter,
cost-aware throttle — default 20 messages/minute per bot+visitor).

### `POST /widget/{botUuid}/messages`

**Body:** `{ "session_id": "...", "fingerprint": "...", "message": "..." }`

Runs the full pipeline: input sanitization → RAG retrieval → prompt
assembly → Gemini call → persistence → usage logging. Also
auto-generates a conversation title after the 2nd message, in the
background of the same request (best-effort — never fails the turn).

**201 response `data`:** `{ conversation, message (assistant reply), user_message }`

If the model's safety filter blocks the response, `message.content`
will be a polite fallback ("I'm not able to respond to that...") — the
turn still succeeds at the HTTP level; check `ai_usage_logs.status` for
`blocked` to distinguish this from a normal reply.

### `POST /widget/{botUuid}/messages/stream`

Same body as above. Response is `Content-Type: text/event-stream`.
Each frame:

```
data: {"delta":"Hello","done":false}

data: {"delta":" there","done":false}

data: {"done":true,"finish_reason":"STOP","total_tokens":142}
```

An error mid-stream sends a final frame `{"done":true,"error":true,"message":"..."}`
instead of the normal completion frame. Closing the client connection
early is itself the primary stop mechanism (detected via
`connection_aborted()`); see also `/stop` below for an explicit flag.

### `POST /widget/{botUuid}/conversations/{conversationUuid}/regenerate`

No body. Deletes the most recent assistant message and generates a
fresh one for the same prior user message.

**200 response `data`:** `{ "message": <regenerated assistant message> }`

Returns `409` if the conversation is currently generating (e.g. a
stream is still in flight) — wait for it to finish or call `/stop` first.

### `POST /widget/{botUuid}/conversations/{conversationUuid}/stop`

No body. Sets a cancellation flag checked by any in-flight streaming
generation for this conversation between chunks. Returns immediately
with `200` regardless of whether a generation was actually in
progress — it's a request, not a guarantee of a specific prior state.

### `GET /widget/{botUuid}/conversations/{conversationUuid}/suggested-questions`

**200 response `data`:** `{ "questions": ["...", "...", "..."] }` — up to 3 AI-generated follow-up suggestions based on the conversation so far, with a generic fallback if generation fails.

---

## Conversation export 🔒

### `GET /bots/{uuid}/conversations/{conversationUuid}/export?format=json|markdown`

**200 response `data`:** `{ format, content, mime_type, filename }` — `content` is the full export as a string; the client is expected to trigger a download from it (e.g. a `Blob` + `<a download>` in the dashboard frontend).

---

## Plans & subscriptions 🔒

### `GET /subscriptions/plans`
Public-within-auth list of active plans. **200 `data`:** array of `{ id, name, slug, description, monthly_price, yearly_price, currency, limits: {...}, features: {...}, trial_days, is_active }`.

### `GET /subscriptions/current`
**200 `data`:** `{ subscription: {...}|null, limits_and_usage: { limits: {...}, usage: {...} } }`. `subscription` is `null` if the user has never subscribed (they're on the implicit free-tier default limits from `config/default_plan_limits.php`).

### `GET /subscriptions/history`
Every subscription row the user has ever had (including canceled/expired), newest first.

### `POST /subscriptions`
**Body:** `{ "plan_id": "<plan uuid>", "billing_cycle": "monthly|yearly", "coupon_code": "OPTIONAL" }`. Cancels any existing active subscription (no stacking). Starts a trial automatically if this is the user's first-ever subscription and the plan has `trial_days > 0`.

### `POST /subscriptions/{uuid}/cancel`
**Body:** `{ "at_period_end": true }` (default `true` — keeps access until the current period ends; `false` cancels immediately).

### `GET /subscriptions/invoices`
Paginated. No invoice is generated for `$0` plans or while trialing — only once a real charge applies.

---

## Teams 🔒

### `POST /teams` — `{ "name": "..." }`. Creator becomes `owner`.
### `GET /teams/{uuid}` / `GET /teams/{uuid}/members`
### `POST /teams/{uuid}/invite` — `{ "email": "...", "role": "admin|editor|viewer" }`. Requires `admin`+ team role. Subject to the plan's `team_members_limit`.
### `POST /teams/invitations/{token}/accept` — accepts an invitation (the logged-in user's email must match the invited address).
### `DELETE /teams/{uuid}/members/{targetUserId}` / `PUT /teams/{uuid}/members/{targetUserId}/role` — `{ "role": "admin|editor|viewer" }`. Requires `admin`+ team role; the owner can't be removed or changed.

---

## Webhooks (your own outgoing integrations) 🔒

### `GET /webhooks/events` — list of supported event names.
### `GET /webhooks` / `POST /webhooks` — **Body:** `{ "url": "https://...", "events": ["bot.created", "lead.created", ...] }`. Response includes `secret` **once** — used to verify `X-Webhook-Signature` (`HMAC-SHA256(payload, secret)`) on every delivery to your endpoint.
### `DELETE /webhooks/{uuid}` / `PUT /webhooks/{uuid}` (toggle `is_active` via `{ "is_active": false }`) / `GET /webhooks/{uuid}/logs` (paginated delivery history).

**Supported events:** `bot.created`, `bot.deleted`, `chat.started`, `chat.completed`, `lead.created`, `subscription.created`, `subscription.updated`, `user.created`, `knowledge.uploaded`.

---

## Notifications 🔒

### `GET /notifications` (paginated) / `GET /notifications/unread-count` / `POST /notifications/{uuid}/read` / `POST /notifications/read-all`

---

## Analytics 🔒

### `GET /bots/{uuid}/analytics?group_by=day|month|year&limit=30`
Requires the plan's `analytics` feature flag. **200 `data`:** `{ conversations_by_period, messages_by_period, leads_by_period, averages: { response_time_ms, tokens_per_message, cost_per_message }, most_asked_questions: [{question, count}], lead_conversion_rate, average_rating }`.

---

## White-label branding 🔒

### `GET /white-label` / `PUT /white-label`
Requires the plan's `white_label` feature flag (`402` otherwise). **Body:** `{ "logo_path", "primary_color", "secondary_color", "custom_domain", "remove_branding" }` (all optional).

---

## API keys — additional Phase 5 capability 🔒

### `POST /api-keys/{uuid}/rotate`
Revokes the existing key and issues a brand-new one with the same name/scopes/expiry in one call — use when a key may have leaked. Response shape matches `POST /api-keys`, including the new raw key shown once.

`POST /api-keys` now also accepts an optional `"scopes": ["..."]` array, returned on every list/create response.

---

## Public widget — additional Phase 5 endpoint

### `POST /widget/{botUuid}/conversations/{conversationUuid}/rate`
**Body:** `{ "rating": 1-5, "comment": "optional" }`. No auth, subject to the same domain allow-list as other widget endpoints.

---

## Usage limits (`402 Payment Required`)

Any endpoint gated by a plan limit or feature flag returns `402` (not `403`/`429`) when exceeded, with a message naming what to upgrade:

```json
{ "status": 402, "success": false, "message": "You've reached your plan's limit for bots. Please upgrade your plan to continue.", "errors": {} }
```

Gated actions: creating a bot (`bots_limit`), adding knowledge (`knowledge_limit_mb`, and additionally `storage_limit_mb` for document uploads), sending a widget chat message (`messages_limit`), streaming chat (`streaming` feature flag), inviting a team member (`team_members_limit`), viewing bot analytics (`analytics` feature flag), and white-label branding (`white_label` feature flag). A limit of `-1` on any plan means unlimited (the seeded Enterprise plan uses this).

---

## Admin (Super Admin / Admin only) 🔒🛡️

Every route below requires `RoleMiddleware` (`super-admin` or `admin`
platform role) **and** a specific `PermissionMiddleware` grant — an
`admin`-role account without the named permission gets `403` even
though it passed the role check. Super-admins bypass the permission
check implicitly.

### `GET /admin/dashboard`
Full platform overview — see `docs/architecture.md`'s "Admin dashboard" section for the exact shape (`users`, `bots`, `conversations`, `ai`, `storage`, `revenue`, `subscriptions`, `pending_payments`, `webhooks`, `system_health`, `cron_jobs`).

### User management (`users.*` permissions)
- `GET /admin/users?q=&status=&page=` — search/list.
- `POST /admin/users/{uuid}/suspend` / `POST /admin/users/{uuid}/activate` — also revokes all sessions on suspend.
- `DELETE /admin/users/{uuid}` — soft-deletes, revokes sessions.
- `POST /admin/users/{uuid}/reset-password` — emails the user a reset link; the admin never sees the new password.
- `POST /admin/users/{uuid}/force-logout` — revokes all sessions immediately.
- `GET /admin/users/{uuid}/activity` (paginated), `/login-history`, `/api-usage`, `/ai-usage`, `/storage-usage`.

### Plans (`plans.manage`)
Full CRUD: `GET /admin/plans`, `POST /admin/plans`, `GET /admin/plans/{uuid}`, `PUT /admin/plans/{uuid}`, `DELETE /admin/plans/{uuid}` (blocked with `409` if the plan has active subscriptions).

### Billing (`subscriptions.manage` / `coupons.manage`)
- `GET /admin/invoices/pending`, `POST /admin/invoices/{uuid}/mark-paid`, `POST /admin/invoices/{uuid}/void`.
- `GET /admin/coupons`, `POST /admin/coupons` (`{ code, type: percent|fixed, value, max_redemptions?, valid_from?, valid_until? }`), `POST /admin/coupons/{uuid}/deactivate`.

### Settings (`settings.manage`)
- `GET /admin/settings` — grouped by `general`/`branding`/`uploads`/`limits`/`security`.
- `PUT /admin/settings` — **nested body** matching the group structure, e.g. `{ "platform": { "name": "..." }, "security": { "login_max_attempts": 5 } }`.

### Webhook logs (`webhook-logs.view`)
`GET /admin/webhook-logs` — every outgoing delivery attempt, across every user's registered webhooks (paginated).

### Logs (`audit-logs.view`)
`GET /admin/logs/errors` (system channel), `GET /admin/logs/security` (auth channel), `GET /admin/logs/{channel}` (any of `system|auth|api|app|activity|ai`). Query param `?lines=200` (10-1000).

---

*Endpoints added in later phases: none currently planned beyond Phase 6's hardening/documentation pass.*
