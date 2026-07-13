# Production Readiness Checklist

Work through this list before pointing a real domain at a real launch.
Grouped in the order you'd actually do them.

## 1. Environment

- [ ] `.env` created from `.env.example`, **not** committed to version control
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` — verify by hitting an endpoint that throws (e.g. malformed JSON body) and confirming the response has no stack trace
- [ ] `APP_URL` set to the real public HTTPS URL
- [ ] `JWT_SECRET` is a freshly generated random value (`php -r "echo bin2hex(random_bytes(32));"`), **not** the value from `.env.example`, **not** reused from any other environment
- [ ] `DB_PASSWORD` is a strong, unique value — not the shared-hosting default
- [ ] `SEED_SUPER_ADMIN_PASSWORD` was a strong one-time value used only during initial seeding, and has since been changed via the normal password-change flow (or the seeder was never re-run with it after go-live)
- [ ] `GEMINI_API_KEY` is a real, working key with billing/quota configured on the Google AI Studio side appropriate for expected traffic
- [ ] `CORS_ALLOWED_ORIGINS` lists your actual frontend domain(s) explicitly — **not** `*`, unless you have a specific reason and understand the tradeoff (see `SECURITY.md`)
- [ ] `MAIL_*` variables point to a real, working SMTP provider (test by triggering a password-reset email)
- [ ] `BILLING_PROVIDER` set deliberately (`manual` is fine for launch; see `docs/architecture.md` for adding Stripe)

## 2. Database

- [ ] Fresh production database created, with a dedicated, non-root MySQL user scoped to only that database
- [ ] `php bin/migrate.php` run successfully (not `fresh` — that drops everything; only run `fresh` against an environment you're happy to wipe)
- [ ] `php bin/seed.php` run once, with `SEED_SUPER_ADMIN_EMAIL`/`SEED_SUPER_ADMIN_PASSWORD` set to real values you'll actually use
- [ ] Confirmed super-admin login works, then (optional but recommended) removed `SEED_SUPER_ADMIN_*` from `.env` so a future accidental re-seed can't recreate a known-password account
- [ ] Reviewed the seeded default plans (`plans` table) and adjusted pricing/limits to match your actual offering before any real customer subscribes

## 3. Web server

- [ ] Document root points at `/public` (preferred) — if it can't, confirm the root `.htaccess` fallback is in place and working
- [ ] HTTPS certificate installed and forced (the shipped `.htaccess` redirects `http`→`https` — confirm this actually fires)
- [ ] `.env`, `storage/`, `database/`, `app/`, `bootstrap/`, `config/`, `vendor/` are **not** web-accessible (`curl https://yourdomain.com/.env` must 403/404)
- [ ] PHP version is 8.3+ (`php -v` on the server, not just locally)
- [ ] Required PHP extensions present: `pdo_mysql`, `curl`, `mbstring`, `json`, `zip`, `fileinfo`, `openssl`
- [ ] See `docs/PHP_CONFIGURATION.md` for `php.ini` recommendations (upload limits, execution time, opcache)

## 4. Cron jobs

All three registered in hPanel → Cron Jobs (or `crontab -e` on a VPS) — see `docs/CRON_JOBS.md` for exact lines:

- [ ] `bin/process-crawl-jobs.php` — every 5 minutes
- [ ] `bin/process-webhook-retries.php` — every 15 minutes
- [ ] `bin/process-billing-cycle.php` — once daily
- [ ] Confirmed at least one successful run of each via `GET /admin/dashboard` → `cron_jobs`, or directly: `SELECT * FROM cron_job_runs ORDER BY id DESC LIMIT 10;`

## 5. Storage & permissions

- [ ] `storage/` and its subdirectories are writable by the web server user
- [ ] `storage/KnowledgeBase/` is **not** web-accessible (confirm with a direct `curl`)
- [ ] Disk space monitored — `GET /admin/dashboard` → `storage.disk_free_mb` — set an alert threshold appropriate to your plan

## 6. Security

- [ ] Read `SECURITY.md` in full
- [ ] Confirmed `X-Frame-Options`, `Content-Security-Policy`, `X-Content-Type-Options` headers are present on a real response (`curl -I`)
- [ ] Confirmed rate limiting actually triggers (send >20 requests/min to a widget chat endpoint, expect a `429`)
- [ ] Confirmed SSRF guard blocks a private-IP webhook/crawl URL in the real deployed environment, not just locally

## 7. Monitoring & backups

- [ ] Read and implement `docs/BACKUP_RESTORE.md`
- [ ] Read `docs/DISASTER_RECOVERY.md` and confirm you could actually execute it under pressure
- [ ] Log rotation confirmed working — Monolog's `RotatingFileHandler` caps each channel at 30 days by default (`config/logging.php`); confirm `storage/Logs/` isn't growing unbounded after a week of real traffic
- [ ] Decide who gets paged when `ai_usage_logs` shows a spike in `status = 'failed'`, or when `webhook_logs` shows sustained `failed` deliveries

## 8. Billing sanity check

- [ ] Subscribed a real test account to a paid plan end-to-end and confirmed an invoice is generated with the correct amount
- [ ] Confirmed the trial → grace period → expired lifecycle behaves as expected (see `docs/architecture.md`'s Phase 5 section for the exact state machine) — or manually walk a test subscription through it by adjusting dates directly in the database, as was done during this project's own QA
- [ ] Confirmed `POST /admin/invoices/{uuid}/mark-paid` works for your actual manual-billing workflow (bank transfer, invoice, etc.)

## 9. Final smoke test

Run through, against the real production URL:
1. Register → verify email → log in
2. Create a bot → activate it → add a knowledge source → confirm it reaches `status: completed`
3. Send a message through the public widget endpoint → confirm a real Gemini reply comes back
4. Subscribe to a plan → confirm an invoice appears
5. Log in as the super admin → confirm `/admin/dashboard` returns real numbers

If all five work, you're live.
