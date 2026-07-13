# Environment Variables Guide

Every variable in `.env.example`, grouped by concern, with what it
controls and what changing it affects. See `docs/PRODUCTION_CHECKLIST.md`
for which of these need a deliberate non-default value before launch.

## Application

| Variable | Default | Notes |
|---|---|---|
| `APP_NAME` | `AI Chatbot SaaS` | Used in email templates and anywhere the platform name is displayed |
| `APP_ENV` | `production` | `local`, `development`, `staging`, or `production` — informational, doesn't itself gate behavior |
| `APP_DEBUG` | `false` | **Must be `false` in production.** Controls whether exception responses include stack traces |
| `APP_URL` | — | Used to build absolute links in emails (password reset, team invitations) |
| `APP_TIMEZONE` | `UTC` | Applied via `date_default_timezone_set()` at bootstrap — affects every timestamp the app writes |
| `APP_LOCALE` | `en` | Reserved for future i18n; not currently used to translate anything |
| `APP_KEY` | — | Reserved for future symmetric encryption needs; not currently used by any shipped feature |

## Database

| Variable | Default | Notes |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Only MySQL/MariaDB is supported |
| `DB_HOST`, `DB_PORT` | `127.0.0.1`, `3306` | |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | — | Use a dedicated, non-root MySQL user scoped to only this database |
| `DB_CHARSET`, `DB_COLLATION` | `utf8mb4`, `utf8mb4_unicode_ci` | Required for full Unicode (emoji, non-Latin scripts) in chat content |
| `DB_PREFIX` | (empty) | Table name prefix, if your hosting environment requires shared-database table namespacing |

## Logging

| Variable | Default | Notes |
|---|---|---|
| `LOG_CHANNEL` | `daily` | Rotation strategy |
| `LOG_LEVEL` | `info` | Anything below this level is discarded, not written |
| `LOG_DAYS` | `14` | How many rotated daily files to keep before deletion |
| `LOG_*_FILE` | `storage/Logs/{channel}.log` | One path per channel (app, api, auth, activity, system, ai) — rarely need changing |

## CORS

| Variable | Default | Notes |
|---|---|---|
| `CORS_ALLOWED_ORIGINS` | — | Comma-separated exact origins, or `*`. **Set to your real frontend domain(s)** — see `SECURITY.md` for why `*` + credentials is dangerous |
| `CORS_ALLOWED_METHODS`, `CORS_ALLOWED_HEADERS` | sensible defaults | Rarely need changing unless your frontend sends a custom header |
| `CORS_ALLOW_CREDENTIALS` | `true` | Only takes effect for explicitly-listed origins, never the `*` wildcard (Phase 6 fix — see `SECURITY.md`) |
| `CORS_MAX_AGE` | `86400` | Preflight cache duration in seconds |

## Security

| Variable | Default | Notes |
|---|---|---|
| `SECURITY_FORCE_HTTPS` | `true` | Also enforced independently at the `.htaccess` level |
| `SECURITY_HSTS_ENABLED`, `SECURITY_HSTS_MAX_AGE` | `true`, `31536000` (1 year) | Sends `Strict-Transport-Security` |
| `SECURITY_RATE_LIMIT_ENABLED` | `true` | Global kill switch for `ThrottleMiddleware` |
| `SECURITY_RATE_LIMIT_MAX_ATTEMPTS`, `_DECAY_SECONDS` | `60`, `60` | General per-IP API throttle — 60 requests/minute by default |
| `SECURITY_LOGIN_MAX_ATTEMPTS`, `_LOCKOUT_MINUTES` | `5`, `15` | Account-level lockout after repeated failed logins — independent of, and in addition to, the IP-based rate limit |

## JWT authentication

| Variable | Default | Notes |
|---|---|---|
| `JWT_SECRET` | — | **Generate fresh per environment**: `php -r "echo bin2hex(random_bytes(32));"`. Rotating this invalidates every existing token immediately (see `docs/DISASTER_RECOVERY.md`) |
| `JWT_ALGO` | `HS256` | Don't change without also changing how the key is provided — asymmetric algorithms need a key pair, not a shared secret |
| `JWT_ISSUER`, `JWT_AUDIENCE` | app-specific strings | Embedded in every token's `iss`/`aud` claims, verified on every request |
| `JWT_ACCESS_TTL_MINUTES` | `15` | Short-lived by design — see `docs/architecture.md`'s auth section |
| `JWT_REFRESH_TTL_DAYS` | `30` | How long a user stays logged in without re-entering credentials, assuming regular use (refresh tokens rotate on use) |

## Mail

| Variable | Default | Notes |
|---|---|---|
| `MAIL_MAILER` | `smtp` | Only SMTP is implemented |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION` | — | Standard SMTP connection details from your provider |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | — | SMTP credentials |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | — | Sender identity on every outgoing email |

## Google Gemini

| Variable | Default | Notes |
|---|---|---|
| `GEMINI_API_KEY` | — | From [Google AI Studio](https://aistudio.google.com/app/apikey). Sent via header, never a URL parameter (see `SECURITY.md`) |
| `GEMINI_BASE_URL` | Google's real endpoint | Override to point at a local mock server for development/testing |
| `GEMINI_MODEL` | `gemini-1.5-flash` | Default chat model for new bots (each bot's `model` field can override) |
| `GEMINI_EMBEDDING_MODEL`, `GEMINI_EMBEDDING_DIMENSIONS` | `text-embedding-004`, `768` | Changing the model requires re-embedding every existing bot (`POST /bots/{uuid}/reembed`) — see `docs/RAG_ARCHITECTURE.md` |
| `GEMINI_TIMEOUT_SECONDS` | `30` | Per-request timeout for non-streaming calls |
| `GEMINI_MAX_OUTPUT_TOKENS` | `2048` | Default cap on generated response length |

## File uploads

| Variable | Default | Notes |
|---|---|---|
| `UPLOAD_MAX_FILE_SIZE_MB` | `20` | Must be ≤ `php.ini`'s `upload_max_filesize` — see `docs/PHP_CONFIGURATION.md` |
| `UPLOAD_ALLOWED_MIME_TYPES` | PDF, TXT, CSV, MD, DOC, DOCX | Comma-separated; verified server-side against actual file content, not the client's claimed `Content-Type` |
| `UPLOAD_STORAGE_PATH`, `KNOWLEDGE_BASE_STORAGE_PATH` | `storage/Uploads`, `storage/KnowledgeBase` | Both outside the public webroot by design |

## Initial super admin (seeder only)

| Variable | Default | Notes |
|---|---|---|
| `SEED_SUPER_ADMIN_NAME/EMAIL/PASSWORD` | — | Only used by `php bin/seed.php`, and only if all three are set — the seeder refuses to create an admin account with a blank password. Consider removing these from `.env` after initial setup (see `docs/PRODUCTION_CHECKLIST.md`) |

## Billing

| Variable | Default | Notes |
|---|---|---|
| `BILLING_PROVIDER` | `manual` | Fully functional without any payment gateway — see `docs/architecture.md`'s Phase 5 section. Set to a future gateway's name once you've implemented `PaymentProviderInterface` for it |
| `BILLING_DEFAULT_CURRENCY` | `USD` | |
| `BILLING_GRACE_PERIOD_DAYS` | `7` | Days a `past_due` subscription keeps working after a failed/lapsed payment before being marked `expired` |
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | — | Reserved for a future `StripePaymentProvider`; unused while `BILLING_PROVIDER=manual` |

## AI platform tuning

| Variable | Default | Notes |
|---|---|---|
| `AI_DEFAULT_PROVIDER` | `gemini` | Which `AIProviderFactory` entry new bots default to |
| `AI_RAG_TOP_K`, `AI_RAG_MIN_SCORE`, `AI_RAG_MAX_CONTEXT_TOKENS` | `5`, `0.55`, `2000` | Retrieval tuning — see `docs/RAG_ARCHITECTURE.md` |
| `AI_MEMORY_MAX_MESSAGES`, `AI_MEMORY_MAX_TOKENS` | `20`, `3000` | Conversation history window per chat turn |
| `AI_CHAT_RATE_LIMIT_MAX_ATTEMPTS`, `_DECAY_SECONDS` | `20`, `60` | Per bot+visitor AI-specific throttle, separate from the general API rate limit |

## AI provider pricing

| Variable | Default | Notes |
|---|---|---|
| `PRICING_GEMINI_*_PER_1K` | all `0` | Matches Gemini's free tier. Set real per-1,000-token prices once on a paid tier, to make `ai_usage_logs.estimated_cost` and the admin dashboard's revenue-vs-cost figures meaningful |

## A note on secrets

Everything in this file that represents a secret (`JWT_SECRET`,
`DB_PASSWORD`, `GEMINI_API_KEY`, `MAIL_PASSWORD`, `STRIPE_SECRET`) stays
in `.env` only — never in the database, never in a config file
committed to version control, never logged. `SettingsService`
(admin-editable operational settings — platform name, branding,
non-secret limits) is a deliberately separate, narrower mechanism that
never touches this class of value. See `docs/architecture.md`'s
Phase 5 "Settings & white-label" section.
