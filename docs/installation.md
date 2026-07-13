# Installation Guide

## Requirements

- PHP 8.3+ with extensions: `pdo`, `pdo_mysql`, `mbstring`, `curl`, `openssl`, `json`, `zip` (the `zip` extension is required for `.docx` knowledge-base uploads)
- MySQL 8.0+ (or MariaDB 10.6+, which the migration DDL is compatible with)
- Composer 2.x
- Apache with `mod_rewrite` enabled (Hostinger shared hosting ships this by default)

## 1. Install dependencies

```bash
composer install --no-dev --optimize-autoloader
```

(Use `composer install` without `--no-dev` locally if you want PHPUnit/PHP-CS-Fixer available.)

## 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` and set at minimum:

- `APP_KEY` — generate 32+ random characters, e.g. `php -r "echo bin2hex(random_bytes(32));"`
- `JWT_SECRET` — generate the same way as `APP_KEY`, but use a **different** value
- `APP_URL`, `APP_ENV` (`production` on Hostinger), `APP_DEBUG=false` in production
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — from Hostinger hPanel → Databases → MySQL Databases
- `CORS_ALLOWED_ORIGINS` — the exact origin(s) of your Lovable frontend
- `MAIL_*` — SMTP credentials for verification/password-reset emails (Hostinger hPanel → Emails, or an external provider)
- `SEED_SUPER_ADMIN_EMAIL` / `SEED_SUPER_ADMIN_PASSWORD` — used once by `composer seed` to create your first admin login

Never commit `.env` — it's already in `.gitignore`.

## 3. Create the database

In Hostinger hPanel: **Databases → MySQL Databases → Create Database**,
then create a database user and attach it with **All Privileges**.
Use those credentials in `.env`.

## 4. Run migrations

```bash
composer migrate
# equivalent to: php bin/migrate.php
```

Other migration commands:

```bash
composer migrate:rollback   # roll back the most recent batch
composer migrate:fresh      # DROP all tables and re-run every migration (destructive — asks for confirmation)
php bin/migrate.php status  # show which migrations have run
```

## 5. Run seeders

```bash
composer seed
```

This seeds:
- The 6 default roles (`super-admin`, `admin`, `user`, `team-owner`, `team-member`, `viewer`)
- A baseline set of permissions, assigned per role
- An initial Super Admin account, **only if** `SEED_SUPER_ADMIN_EMAIL` and `SEED_SUPER_ADMIN_PASSWORD` are set in `.env` (the seeder skips this step with a warning if they're empty — it will never create an admin account with a blank/default password).
- 4 default subscription plans (Free, Starter, Pro, Enterprise) — edit or replace these via `PUT /admin/plans/{uuid}` / `POST /admin/plans` once logged in as the super admin; they're a sensible starting point, not fixed.

Re-running `composer seed` is safe — every seeder checks for existing rows before inserting.

## 6. Point the web server at `/public`

**Preferred:** In Hostinger hPanel, set the domain's document root to
the project's `public/` folder (Advanced → PHP Configuration, or
Websites → Manage → Advanced, depending on plan). This is the most
secure option since `app/`, `config/`, `storage/`, and `.env` are
never web-accessible even if `.htaccess` were somehow bypassed.

**If your plan doesn't allow changing the document root:** upload the
whole project to `public_html/` and rely on the root-level
`.htaccess` included in this repo — it blocks direct access to
`app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `storage/`,
`tests/`, and `vendor/`, and routes all other requests through
`public/index.php`.

## 7. Verify the installation

```
GET https://your-domain.com/api/v1/health
```

Expected response:

```json
{
  "status": 200,
  "success": true,
  "message": "Service is healthy.",
  "data": {
    "status": "ok",
    "timestamp": "2026-07-09T00:00:00+00:00",
    "app": { "name": "AI Chatbot SaaS", "env": "production", "version": "v1" },
    "checks": { "database": "ok" }
  },
  "errors": {},
  "pagination": null
}
```

If `data.checks.database` is not `"ok"`, double-check `DB_*` values in
`.env` and that the database user has privileges on the database.

## 8. Set up the website-crawl cron job

Website knowledge sources are queued, not processed inline (shared-hosting
PHP request timeouts are too short to crawl multiple pages). In Hostinger
hPanel → **Advanced → Cron Jobs**, add a job running every 5 minutes:

```
*/5 * * * * php /home/<your-account>/domains/<yourdomain>/bin/process-crawl-jobs.php 5 >> /home/<your-account>/domains/<yourdomain>/storage/Logs/crawl-cron.log 2>&1
```

The trailing `5` limits each run to 5 queued jobs, keeping every
invocation bounded regardless of how many sites are queued at once.

## 9. Configure the Gemini API (required for AI chat replies)

1. Get a free API key from [Google AI Studio](https://aistudio.google.com/app/apikey).
2. Set in `.env`:
   ```
   GEMINI_API_KEY=your-key-here
   GEMINI_MODEL=gemini-1.5-flash
   GEMINI_EMBEDDING_MODEL=text-embedding-004
   ```
3. That's it — no separate embedding API key or vector database setup
   needed. Embeddings are generated automatically whenever a knowledge
   source finishes processing, and stored directly in MySQL
   (`documents.embedding`).

**Cost note:** Gemini's free tier covers moderate usage; all pricing in
`config/ai_pricing.php` defaults to `0` so `ai_usage_logs.estimated_cost`
reads `0.00` until you configure real per-1k-token prices via the
`PRICING_GEMINI_*` env vars (useful once you're on a paid tier, or add
a second provider with real costs).

**Safety settings:** `config/ai.php`'s `default_safety_settings` apply
to every bot unless it sets its own `safety_settings` (see
`docs/api.md`). The defaults block medium-and-above harassment, hate
speech, sexual, and dangerous content — adjust per your use case and
Gemini's own usage policies.

## 10. Set up the billing cycle and webhook retry cron jobs

Two more scheduled jobs, alongside the crawl-job processor from step 8:

```
*/15 * * * * php /home/<your-account>/domains/<yourdomain>/bin/process-webhook-retries.php >> /home/<your-account>/domains/<yourdomain>/storage/Logs/webhook-cron.log 2>&1
0 2 * * * php /home/<your-account>/domains/<yourdomain>/bin/process-billing-cycle.php >> /home/<your-account>/domains/<yourdomain>/storage/Logs/billing-cron.log 2>&1
```

- **Webhook retries** every 15 minutes — retries failed outgoing webhook deliveries, up to 5 attempts each.
- **Billing cycle** once daily (here, 2am server time) — expires trials into their grace period, renews active subscriptions due for a new period (generating invoices), and expires subscriptions whose grace period has lapsed.

All cron job runs (crawl, webhook retries, billing cycle) are tracked
in the `cron_job_runs` table and visible on the admin dashboard
(`GET /admin/dashboard` → `cron_jobs`) — if a job stops running, it'll
show as stale there before you'd notice it any other way.

## 11. Billing: manual by default, Stripe-ready

The platform ships with `BILLING_PROVIDER=manual` (default) — fully
functional without any payment gateway: subscribing activates
immediately, and you (or your customer) arrange payment out-of-band,
then an admin marks the invoice paid via `POST /admin/invoices/{uuid}/mark-paid`.
This is genuinely usable for invoicing/bank-transfer/manual billing
relationships, not a placeholder.

To add Stripe (or another gateway) later: implement
`App\Core\Contracts\PaymentProviderInterface` in a new
`StripePaymentProvider` class, add credentials to `config/billing.php`'s
`stripe` section (already reserved: `STRIPE_KEY`, `STRIPE_SECRET`,
`STRIPE_WEBHOOK_SECRET` in `.env.example`), add one `match` arm in
`PaymentProviderFactory::provider()`, and set `BILLING_PROVIDER=stripe`.
`SubscriptionService` and every controller are unaffected.

## Local development

```bash
php -S localhost:8000 -t public
```

Then visit `http://localhost:8000/api/v1/health`.

## File permissions (Hostinger)

Ensure these directories are writable by the PHP process (typically
`755` is sufficient on Hostinger; avoid `777`):

```
storage/Logs
storage/Uploads
storage/KnowledgeBase
storage/Cache/Rate
```
