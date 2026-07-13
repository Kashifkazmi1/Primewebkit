# Changelog

All notable changes to this project, organized by build phase.

## Phase 6 — Production Hardening (current)

### Security fixes
- Moved the Gemini API key from a URL query parameter to the `x-goog-api-key` header, preventing it from leaking into error messages and request logs.
- Added `SsrfGuard`: validates every user-supplied URL (website crawl targets, webhook endpoints) against private/loopback/link-local/reserved IP ranges and the cloud metadata address, applied at registration time and again immediately before each request.
- Fixed a redirect-based SSRF bypass in the website crawler — redirects are now followed manually with `SsrfGuard` re-validated at every hop.
- Fixed a race condition in `usage_counters` writes (read-check-then-write → atomic `INSERT ... ON DUPLICATE KEY UPDATE`).
- Fixed a CORS misconfiguration risk: `Access-Control-Allow-Credentials` is no longer sent when an origin matches only via the `*` wildcard.
- Fixed `WebhookService` exposing the raw internal database ID and the signing secret on every read (not just at creation).
- Fixed a boolean-coercion bug in `WebhookController::toggle()` where a JSON string `"false"` would have been miscast to `true`.

### Performance
- Added a composite index on `ai_usage_logs (bot_id, status)` and an index on `messages.created_at`.
- Eliminated an N+1 query in `PlanLimitService::countTeamMembersOwnedBy()`.

### Code quality
- Removed dead scaffolding directories (`app/Policies`, `app/Providers`, `app/Responses`, `app/Traits`, `app/Validators`) never referenced anywhere in the codebase.
- Added a real PHPUnit configuration and unit test suite.

### Documentation
- Full production documentation suite: `SECURITY.md`, `DEPLOYMENT.md`, and 14 focused guides under `docs/`.

## Phase 5 — SaaS Platform, Monetization & Administration

- Super Admin dashboard, user management, plans, subscriptions, billing.
- `PaymentProviderInterface` + `ManualPaymentProvider` — Stripe-ready architecture.
- `UsageLimiterMiddleware` — plan limit and feature-flag enforcement.
- Teams, API key rotation/scopes, outgoing webhooks, analytics, notifications, settings, white-label.
- 16 new database tables.

## Phase 4 — AI Provider Integration

- `AIChatProviderInterface` / `AIEmbeddingProviderInterface` + `AIProviderFactory`.
- `GeminiProvider`, RAG pipeline, prompt engine, conversation memory, AI usage tracking.
- Streaming, regenerate, stop-generation, suggested questions, auto-titles, export.
- Prompt-injection defense, AI-specific rate limiting.
- 3 new/altered database tables.

## Phase 3 — Core Domain

- Bots, knowledge sources, document extraction, website crawler, conversations, messages, widgets, leads, API keys.
- 10 new database tables.

## Phase 2 — Authentication & RBAC

- JWT auth, full lifecycle, account lockout, RBAC.
- 10 new database tables.

## Phase 1 — Foundation

- DI container, router, query builder, migrations/seeders, logging, error handling.
