# Disaster Recovery

Scenarios, in order of how likely you are to actually hit them, with
what to do for each. Read this *before* you need it.

## 1. Database corruption or accidental data loss

**Symptoms:** queries erroring, an admin/support report of missing
data, a bad migration run against production, a mistaken `DELETE`
without a `WHERE` clause.

**Response:**
1. Stop writes immediately if possible (put the app in maintenance mode — see `config/app.php`'s maintenance flag, or simply take the site offline at the web-server level) to avoid the restored backup missing data written *after* the incident but *before* you notice it.
2. Identify the most recent good backup (see `docs/BACKUP_RESTORE.md`).
3. Restore into a **separate, fresh** database first — never restore directly over the live one until you've confirmed the backup is good.
4. Point a copy of the app at the restored database and spot-check.
5. Only then swap the live `DB_DATABASE` (or restore into the live DB name after confirming) and bring the site back.
6. Post-mortem: what caused it, and does it point to a missing safeguard (e.g., no confirmation step on a destructive admin action)?

**Data loss window:** equal to your backup frequency. Daily backups
(the default recommendation) mean up to 24 hours of data can be lost
in the worst case. If that's unacceptable for your business, increase
backup frequency or add MySQL binary-log-based point-in-time recovery
(beyond this document's scope — see MySQL's own PITR documentation).

## 2. Compromised credentials (JWT secret, DB password, Gemini API key)

**Symptoms:** unexpected AI usage/cost spike, unfamiliar admin
activity in `audit_logs`, a leaked `.env` (accidental git commit,
server compromise).

**Response — do these in order, immediately:**
1. **`JWT_SECRET` leaked**: rotate it in `.env`. This immediately invalidates *every* existing access and refresh token — every logged-in user (including legitimate ones) is logged out and must log in again. There is no way to invalidate only the attacker's tokens without rotating the secret for everyone, since JWTs are stateless.
2. **`DB_PASSWORD` leaked**: rotate the MySQL user's password immediately (via hPanel or `ALTER USER`), update `.env`, restart PHP-FPM/reload the app.
3. **`GEMINI_API_KEY` leaked**: regenerate it in Google AI Studio, update `.env`. Check Google Cloud's billing/usage dashboard for anomalous charges during the exposure window.
4. **Any of the above**: audit `audit_logs` and `webhook_logs` for the exposure window for anything that shouldn't be there. Force-logout all users if you suspect session-level (not just credential-level) compromise: there's no single "revoke everyone" endpoint today, but `sessions` can be truncated directly, or every user individually via `POST /admin/users/{uuid}/force-logout`.
5. Notify affected users if personal data may have been exposed — required by law in many jurisdictions (GDPR, CCPA, etc. depending on where your users are). This is a business/legal decision, not a technical one, but don't skip it.

## 3. Total server loss (Hostinger account issue, VPS disk failure)

**Response:**
1. Provision a new server/hosting account.
2. Follow `docs/DEPLOYMENT.md` for a fresh install (dependencies, `.env`, migrations).
3. Restore the database from the most recent off-box backup.
4. Restore `storage/KnowledgeBase/` from the most recent off-box file backup.
5. Update DNS to point at the new server (if the domain/IP changed).
6. Re-register cron jobs (a fresh server has none).
7. Run through the "Final smoke test" section of `docs/PRODUCTION_CHECKLIST.md`.

This is exactly why backups must live **off** the server they back up
— a same-server backup dies with the server.

## 4. Gemini API outage or quota exhaustion

**Symptoms:** every chat request fails; `ai_usage_logs.status` shows a
spike in `failed`.

**Response:** this is an *external* dependency outage, not something
this codebase can route around automatically (there's no automatic
failover to a second AI provider — `AIProviderFactory` supports adding
one, but only if you've actually built and configured a second
provider ahead of time). In the moment:
1. Check [Google's status page](https://status.cloud.google.com/) for a known outage.
2. If it's quota exhaustion rather than an outage, raise your quota in Google AI Studio / Cloud Console.
3. Consider a temporary maintenance-mode banner on the widget if the outage is prolonged — better UX than every chat silently failing.

## 5. Runaway AI costs

**Symptoms:** `GET /admin/dashboard` → `ai.estimated_cost` climbing
faster than expected, or a surprise bill from Google.

**Response:**
1. Check `GET /admin/users/{uuid}/ai-usage` for individual accounts — a single bot in a tight conversation-memory loop or an abuse pattern (scripted excessive messaging bypassing normal usage) is the most common cause.
2. `AIChatRateLimitMiddleware` and `UsageLimiterMiddleware` are the two built-in guards — confirm they're actually configured with sane limits (`AI_CHAT_RATE_LIMIT_MAX_ATTEMPTS`, plan `messages_limit`) and not accidentally set to something permissive.
3. Suspend the offending account (`POST /admin/users/{uuid}/suspend`) while investigating if abuse is suspected.
4. Set a hard budget alert in Google Cloud's billing console — this is the only true circuit-breaker against an unbounded bill; the application-level rate limits reduce risk but don't guarantee a ceiling.

## Recovery time expectations

These are realistic targets for a small-to-medium single-server
deployment, not guarantees:

| Scenario | Target recovery time |
|---|---|
| Database restore (scenario 1) | 15–60 minutes, depending on database size |
| Credential rotation (scenario 2) | 5–15 minutes |
| Full server rebuild (scenario 3) | 1–4 hours, depending on how current your deployment documentation/automation is |
| External outage (scenario 4) | Outside your control — monitor and communicate |
