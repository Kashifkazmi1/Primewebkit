# Security

This document covers the platform's security posture: what's been
audited, what was found and fixed, what's deliberately out of scope
and why, and how to report a vulnerability.

## Reporting a vulnerability

Email the maintainer directly (see `README.md`) rather than opening a
public issue. Include reproduction steps and the affected endpoint(s).
There is no bug-bounty program at this time.

## Phase 6 security audit — findings and fixes

Every item below was found by actually reading the relevant code (and
in several cases, by writing a small script to prove the behavior),
not by assertion. Six real, independently-verified issues were found
and fixed:

| # | Issue | Category | Fix |
|---|---|---|---|
| 1 | Gemini API key sent as a URL query parameter (`?key=...`) on every chat/embedding request — leaks into error messages, retry logs, and any intermediary that logs request URLs. | Sensitive information exposure | Moved to the `x-goog-api-key` request header in `GeminiProvider`. Google's API supports both; the header form never appears in a URL. |
| 2 | The website-crawler and outgoing-webhook features make HTTP requests to URLs *supplied by users*, with no check against internal/private targets (cloud metadata endpoint, `localhost`, RFC1918 ranges). | SSRF | New `App\Core\Security\SsrfGuard`, resolving every hostname and rejecting private/loopback/link-local/reserved ranges and the cloud metadata address. Applied at registration time (immediate feedback) and again immediately before each actual request (DNS-rebinding defense). |
| 3 | The crawler's `CURLOPT_FOLLOWLOCATION` let a page pass the initial SSRF check and then redirect straight to an internal address, bypassing it. | SSRF (redirect bypass) | Redirects are now followed manually, one hop at a time, with `SsrfGuard` re-checked before every hop. |
| 4 | `usage_counters` read-check-then-write pattern (SELECT to check existence, then INSERT or UPDATE) had a genuine race condition: concurrent requests for the same user+metric+period could lose an increment, or both attempt an INSERT and collide on the unique constraint. | Race condition | Rewritten using atomic `INSERT ... ON DUPLICATE KEY UPDATE` (and `GREATEST(0, value + delta)` for clamped subtraction) — the entire read-modify-write happens in one atomic statement. |
| 5 | `CorsMiddleware` would still send `Access-Control-Allow-Credentials: true` even when the origin matched only via a `*` wildcard entry — the textbook "any origin + credentials" CORS misconfiguration, exploitable if a non-browser client or a browser bug ever failed to enforce the spec's own prohibition on that combination. | CORS misconfiguration | Credentials are now only ever sent when the origin matched an **explicit** allow-list entry, never the wildcard. |
| 6 | `WebhookService` returned the raw repository row on every list/toggle call — including the signing `secret` on every read (not just at creation, contradicting the API's own "shown once" documentation) and the internal auto-increment `id` (inconsistent with every other resource, which maps `uuid` to the public `id` and never exposes the sequential one). | Sensitive information exposure / API inconsistency | Added `toPublicArray()`; the secret is now returned exactly once, at creation, and the internal id is never exposed. |

A boolean-coercion bug was also fixed in `WebhookController::toggle()`
(a JSON string `"false"` would have PHP-cast to `true` via a naive
`(bool)` cast) — not itself a security vulnerability given the
endpoint's low blast radius, but corrected for correctness
(`FILTER_VALIDATE_BOOLEAN` used instead).

## Threat model by category

### SQL injection
Every query goes through `QueryBuilder` (parameterized, backtick-quoted
identifiers) or explicit PDO prepared statements — nowhere in the
codebase is user input concatenated directly into SQL. The only raw
`->exec()` calls are DDL statements in migration code (developer-
authored, never reachable from an HTTP request) and `whereRaw()`
usages, all of which bind values via named parameters rather than
interpolating them into the SQL string.

### XSS
This is a JSON API with no server-rendered HTML views, so classic
reflected/stored XSS against *this backend's own pages* doesn't apply.
The one place raw HTML is generated is transactional email
(`MailService`), which escapes all interpolated values with
`htmlspecialchars()`. Frontend clients consuming this API are
responsible for escaping any user-generated content (chat messages,
bot names, etc.) before rendering it as HTML.

### CSRF
Not applicable in the traditional sense: authentication is a Bearer
JWT in the `Authorization` header, never a cookie, and there is no
ambient credential a browser would attach automatically to a
cross-origin request. An attacker's page cannot forge an authenticated
request without first obtaining the token through some other means
(e.g. XSS on the *frontend* — a separate application's responsibility).

### SSRF
See findings #2–3 above. Both crawler and webhook-delivery features
now validate every target through `SsrfGuard`. A residual TOCTOU
window exists between the pre-flight check and the actual TCP
connection (full elimination requires pinning the resolved IP via
`CURLOPT_RESOLVE`) — a reasonable future hardening step, not
implemented here; documented as a known limitation.

### Command injection
No `shell_exec`, `exec`, `system`, `passthru`, or `eval` calls exist
anywhere in the codebase (verified by grep across `app/` and `bin/`).

### Prompt injection
See `docs/architecture.md`'s Phase 4 section — `PromptSecurityService`
detects and strips injection patterns from retrieved knowledge-base
content (the higher-risk path — a compromised crawled page) and flags
(without blocking) direct attempts in user chat messages.

### Directory traversal / unsafe file uploads
Uploaded filenames are stripped to `[A-Za-z0-9._-]` before use in a
path — even though `.` survives that filter, `/` does not, so `..`
sequences can never actually traverse a directory (they'd just be
literal dots in a filename). Uploaded files are stored outside the
public webroot (`storage/KnowledgeBase/`, never under `public/`) and
their MIME type is verified server-side via `finfo` on the actual
file content, not the client-supplied `Content-Type` header.

### Broken authentication / JWT validation
JWTs are signed and verified via `firebase/php-jwt` with an explicit
`Key` object bound to a specific algorithm (HS256) — the classic
"algorithm confusion" attack (switching `RS256` to `HS256` to forge a
signature with the public key) does not apply to this library's v6
API. Tokens are short-lived (15 minutes); refresh tokens are opaque,
hashed at rest, single-use (rotated on every refresh), and revocable
server-side. Failed logins are throttled per IP+email *and* trigger a
persisted account lock after a threshold — the two mechanisms are
independent, so IP rotation alone doesn't bypass the account-level
lock.

### Broken authorization / privilege escalation
Every resource-scoped endpoint resolves through a `*ForUser()` /
`getForUser()` repository or service method that filters by the
authenticated user's id — never by trusting a client-supplied owner
field. No endpoint anywhere accepts or modifies `role_id` (platform
role escalation isn't reachable via the API surface at all — role
grants are a deliberately out-of-band operation for now). Team roles
(owner/admin/editor/viewer) are a separate axis from platform roles;
`TeamService::requireRole()` enforces a rank order server-side on
every team-scoped write.

### Mass assignment
Every `update()` call site passes an explicitly whitelisted array
(`array_intersect_key($data, array_flip([...]))`), never the raw
request body — verified by grep across every service with an
`update()` method.

### Rate limiting / API abuse / replay
Three independent layers: a general per-IP `ThrottleMiddleware`, a
stricter `AIChatRateLimitMiddleware` for AI-generating endpoints
(keyed by bot + visitor fingerprint, since those cost money), and
`UsageLimiterMiddleware` enforcing plan quotas. Refresh-token rotation
means a stolen refresh token is single-use — replaying it after the
legitimate client has already refreshed fails.

### Webhook verification
Outgoing webhooks are HMAC-SHA256 signed (`X-Webhook-Signature`);
receivers verify by recomputing the signature over the raw request
body with their registered secret. Incoming webhooks (from a payment
gateway) aren't yet applicable — `ManualPaymentProvider` has no
external party sending them; `PaymentProviderInterface::parseWebhookEvent()`
is the documented seam for adding real signature verification once a
gateway like Stripe is integrated.

### Log injection
No logger call anywhere interpolates raw user-supplied text directly
into the `$message` parameter — every call site uses a fixed message
string with dynamic values passed as a structured context array,
which Monolog serializes as JSON (escaping newlines) rather than
concatenating into the log line.

### Error information disclosure / environment variable exposure
The central exception handler returns internal exception messages and
stack traces **only** when `APP_DEBUG=true`; production responses get
a generic message. `.env` is blocked at the web-server level via
`.htaccess` (`<FilesMatch "^\.">`) regardless of PHP's own behavior.

### Unsafe serialization
No `serialize()`/`unserialize()` call exists anywhere in the codebase
— every payload (API bodies, cached/stored structured data) uses
`json_encode`/`json_decode`, which has no PHP-object-injection
equivalent.

### Clickjacking / security headers
`SecurityHeadersMiddleware` sets `X-Frame-Options: DENY`,
`X-Content-Type-Options: nosniff`, a restrictive `Content-Security-Policy`,
`Referrer-Policy`, and HSTS. `public/.htaccess` sets a fallback subset
of the same headers at the web-server level in case a fatal error
occurs before PHP's own middleware runs, and blocks the `TRACE`/`TRACK`
HTTP methods.

## Known limitations (accepted, documented — not oversights)

- **Registration/login email enumeration**: "an account with this email already exists" on `/auth/register` necessarily reveals whether an email is registered. This is standard practice and a reasonable UX tradeoff; the forgot-password flow deliberately does *not* do this (always returns the same success message regardless of whether the email exists).
- **No platform role-assignment API**: promoting a user to `admin`/`super-admin` currently requires direct database access. This is safer by omission (no API surface = no privilege-escalation vector to audit) at the cost of an admin convenience feature — a reasonable tradeoff for this phase; a future phase could add a `super-admin`-only, heavily-audited endpoint for it.
- **`PermissionMiddleware`'s per-role permission cache is a static, request-lifetime cache**, correct under the standard PHP execution model (one process per request — Apache mod_php, PHP-FPM). If ever deployed under a persistent-worker runtime (Swoole, RoadRunner), this cache would need explicit invalidation on permission changes; not a concern for the Hostinger deployment target this platform is built for.
- **SSRF TOCTOU window**: see the SSRF section above.
