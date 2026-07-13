# Cron Jobs Guide

Three scheduled scripts, all under `bin/`, all tracked in the
`cron_job_runs` table (visible on the admin dashboard) so a silently-
broken cron is visible before a support ticket tells you about it.

## The three jobs

### 1. `bin/process-crawl-jobs.php` — website knowledge-base crawling

**Schedule:** every 5 minutes
**Cron line:**
```
*/5 * * * * php /home/<account>/domains/<yourdomain>/bin/process-crawl-jobs.php 5 >> /home/<account>/domains/<yourdomain>/storage/Logs/crawl-cron.log 2>&1
```

**What it does:** processes up to N (`5` in the line above — the CLI
argument) queued `website_crawl_jobs` rows per run. Website knowledge
sources are never crawled inline on the HTTP request that creates
them — shared-hosting PHP request timeouts (typically 30–60s) are far
too short to crawl multiple pages. Runs `SsrfGuard`-protected fetches
(see `SECURITY.md`), chunks the combined page text, generates
embeddings, and dispatches the `knowledge.uploaded` webhook event.

**Why every 5 minutes:** balances "a user isn't waiting more than a
few minutes to see their crawl complete" against "not hammering the
cron scheduler with excessive invocations." Lower the interval if
users report crawls feeling slow to start; the `5` argument (jobs per
run) is the better lever for *throughput* once a run has started.

### 2. `bin/process-webhook-retries.php` — outgoing webhook redelivery

**Schedule:** every 15 minutes
**Cron line:**
```
*/15 * * * * php /home/<account>/domains/<yourdomain>/bin/process-webhook-retries.php >> /home/<account>/domains/<yourdomain>/storage/Logs/webhook-cron.log 2>&1
```

**What it does:** retries up to 20 `webhook_logs` rows with
`status = 'failed'` and `attempt < 5`, in order. Initial delivery is
always attempted synchronously at the moment the event fires (see
`docs/WEBHOOKS.md`); this cron only handles what that immediate
attempt couldn't deliver — a customer endpoint that was briefly down,
timed out, or returned a non-2xx status.

**Why 15 minutes, not more frequent:** a webhook receiver that's down
for a few minutes doesn't need a retry every 60 seconds — 15-minute
spacing across up to 5 attempts gives a receiver roughly an hour of
total retry window without generating excessive request volume
against an endpoint that's clearly having trouble.

### 3. `bin/process-billing-cycle.php` — subscription lifecycle

**Schedule:** once daily
**Cron line:**
```
0 2 * * * php /home/<account>/domains/<yourdomain>/bin/process-billing-cycle.php >> /home/<account>/domains/<yourdomain>/storage/Logs/billing-cron.log 2>&1
```

**What it does**, in order:
1. Expires trials whose `trial_ends_at` has passed → moves them to `past_due` with a grace period (`config('billing.grace_period_days')`, default 7 days).
2. Cancels subscriptions with `cancel_at_period_end = 1` whose period has actually ended.
3. Renews active subscriptions whose `current_period_end` has passed — extends the period and generates a new invoice if the plan has a price.
4. Expires `past_due` subscriptions whose grace period has lapsed without payment.

**Why once daily, at 2am:** billing state transitions aren't
time-sensitive to the minute — a trial that technically expired at
11:58pm being processed at 2am the next day is a non-issue. Running
during low-traffic hours (server time) minimizes any resource
contention with real user requests. Adjust the hour to your own
userbase's low-traffic window if 2am server time doesn't correspond
to a quiet period for you.

## Verifying cron jobs are actually running

Three ways, in order of convenience:

1. **Admin dashboard**: `GET /admin/dashboard` → `cron_jobs` — shows the latest run of each, with status and timestamps.
2. **Direct query**: `SELECT job_name, status, started_at, finished_at FROM cron_job_runs ORDER BY id DESC LIMIT 10;`
3. **Log files**: `storage/Logs/crawl-cron.log`, `webhook-cron.log`, `billing-cron.log` — the raw stdout/stderr from each invocation, per the `>>` redirect in the cron lines above.

If a job hasn't run recently (check `started_at` against how long ago
it should have last fired), the cron scheduler itself likely isn't
invoking it — check hPanel's Cron Jobs list is actually saved/enabled,
or (VPS) `crontab -l` as the correct user.

## Adding a new cron job

Follow the existing pattern in any of the three scripts:

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);

use App\Repositories\CronJobRunRepository;

/** @var App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php'; // required — see below
$container = $app->container();

$cronRuns = $container->resolve(CronJobRunRepository::class);
$runId = $cronRuns->start('your-job-name');

try {
    // ... do the work, resolving services via $container->resolve(...)
    $cronRuns->finish($runId, true, 'Summary of what happened.');
} catch (\Throwable $e) {
    $cronRuns->finish($runId, false, $e->getMessage());
    fwrite(STDERR, "Failed: {$e->getMessage()}\n");
    exit(1);
}
```

**Critical**: always bootstrap through `bootstrap/app.php`, never
`Container::getInstance()` directly. A real bug caught during this
project's own Phase 6 audit: a script that skipped `bootstrap/app.php`
and called the container directly failed immediately, because
`bootstrap/bindings.php` (which registers interface bindings like
`VectorSearchRepositoryInterface`) only runs *inside*
`bootstrap/app.php` — never assume it's already loaded.
