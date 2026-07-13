# Maintenance Guide

Ongoing operational care for a running deployment.

## Daily

Nothing manual required if cron jobs and backups are configured —
this section exists so you know what's happening automatically:
- Billing cycle processing (trials, renewals, grace periods)
- Database backup (if you've set up `docs/BACKUP_RESTORE.md`'s script)

## Weekly

- **Check `GET /admin/dashboard`** — user growth, AI cost trend, pending payments, webhook failure rate. Nothing here should be a surprise if you check weekly rather than discovering it monthly.
- **Check `cron_job_runs`** for any job with a stale `started_at` or a `failed` status — see `docs/CRON_JOBS.md`'s verification section.
- **Skim `storage/Logs/system.log`** for recurring errors (`GET /admin/logs/errors`) — a single one-off error is normal; the same error repeating is a signal.

## Monthly

- **Review disk usage** (`storage.disk_free_mb` on the dashboard) — knowledge-base uploads and log files are the two things that grow unbounded without active management.
- **Review `ai_pricing.php` values** against Google's current published pricing if you're on a paid Gemini tier — prices can change; stale config means inaccurate cost reporting, not incorrect billing (Google bills you directly regardless of what this app thinks the price is).
- **Rotate any credential that's been unchanged for a while** as a matter of hygiene, even without a known compromise — `GEMINI_API_KEY` and `DB_PASSWORD` are the two most practical to rotate periodically.
- **Test a backup restore** (see `docs/BACKUP_RESTORE.md`'s "Backup testing" section) if you haven't recently.

## Log rotation

Monolog's `RotatingFileHandler` (configured in `LoggerFactory`)
handles this automatically — one file per day per channel, with old
files beyond `LOG_DAYS` (default 14) deleted automatically. No manual
`logrotate` configuration is needed for the application's own logs.

If you're on a VPS and also care about **web server** access/error
logs (Apache/nginx, separate from this application's own Monolog
output), those follow your Linux distribution's standard `logrotate`
setup — not something this application manages.

## Database maintenance

MySQL/MariaDB on Hostinger shared hosting is managed by the host —
no manual `OPTIMIZE TABLE`/vacuum-equivalent is typically necessary or
even accessible. On a self-managed VPS, consider:

```sql
-- Periodically, on tables with heavy insert+delete churn (webhook_logs, usage_counters):
OPTIMIZE TABLE webhook_logs;
```

Not urgent for a new deployment; relevant once tables have
accumulated significant deleted-row overhead (months of production
traffic, not days).

## Handling a stuck/failed cron job

If `cron_job_runs` shows a job stuck in `status = 'running'` for far
longer than it should ever take (the process likely died mid-run
without reaching its `finally`-equivalent `finish()` call — a PHP
fatal error or the process being killed): this row is stale
metadata, not an active lock — nothing prevents the *next* scheduled
run from starting normally. Manually mark it however you like for
your own bookkeeping (`UPDATE cron_job_runs SET status='failed' WHERE id=...`)
if the stale "running" status is confusing your own monitoring; it
has no functional effect on the app itself.

## Handling a growing `webhook_logs` table

This table is append-only and has no automatic pruning. If a
particular webhook has been failing for a long time (a customer
abandoned their integration without deleting the webhook), its
`webhook_logs` rows accumulate indefinitely. Two options:
1. Periodically deactivate/delete webhooks that have had zero successful deliveries in a long window — a query like `SELECT webhook_id, COUNT(*) FROM webhook_logs WHERE status='failed' GROUP BY webhook_id HAVING COUNT(*) > 100` surfaces candidates.
2. Add a scheduled cleanup (not currently shipped) that deletes `webhook_logs` rows older than N days regardless of status, if delivery history beyond a certain age has no operational value for you.

## Handling a growing `ai_usage_logs` table

Also append-only, and the primary source for cost/usage
analytics — don't delete from it casually, since `AdminDashboardService`
and `AnalyticsService` both query it for historical figures. If it
becomes large enough to matter (tens of millions of rows, well beyond
what a typical single-server deployment reaches organically), consider
an archival strategy: periodically move rows older than N months into
a separate `ai_usage_logs_archive` table (same schema), keeping the
live table faster to query for recent-period dashboards.
