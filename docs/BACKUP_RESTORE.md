# Backup & Restore Strategy

Two things need backing up: the **database** (everything transactional
— users, bots, conversations, subscriptions, billing) and **uploaded
files** (`storage/KnowledgeBase/`, the raw documents behind knowledge
sources — re-derivable from the source files if lost, but the
originals themselves aren't reconstructable).

## What to back up

| What | Where | Why it matters |
|---|---|---|
| MySQL database | all tables | Everything: users, billing, conversations, bot config. The single most important backup. |
| `storage/KnowledgeBase/` | uploaded documents | Original PDFs/DOCX files behind knowledge sources. Losing these means re-uploading them (the extracted/chunked text is still in the DB, so bots keep working — but you can't re-process or re-embed with a different model without the originals). |
| `.env` | environment config | Not user data, but losing it means regenerating every secret and reconfiguring from scratch. Back it up somewhere *other* than the same server (a password manager, encrypted note — never plaintext in a public repo). |

`storage/Logs/`, `storage/Cache/`, and `vendor/` do **not** need
backing up — logs are operational/disposable, cache is regenerable,
and `vendor/` is reconstructable from `composer.json` + `composer install`.

## Database backup

### Hostinger shared hosting

hPanel provides automatic daily backups on most plans (Databases →
Backups) — confirm your plan includes this and what the retention
window is. Additionally, run a manual `mysqldump` on a schedule via
cron for a second, independent copy:

```bash
#!/bin/bash
# bin/backup-database.sh (create this file; not included by default
# since credentials shouldn't live in a script committed to version
# control — this reads them from .env at runtes to avoid duplicating secrets)
set -euo pipefail
cd "$(dirname "$0")/.."
source <(grep -E '^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=' .env | sed 's/^/export /')
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="../backups"  # deliberately OUTSIDE the web-accessible tree
mkdir -p "$BACKUP_DIR"
mysqldump --host="$DB_HOST" --port="${DB_PORT:-3306}" --user="$DB_USERNAME" --password="$DB_PASSWORD" \
  --single-transaction --quick --routines --triggers "$DB_DATABASE" \
  | gzip > "$BACKUP_DIR/db-$TIMESTAMP.sql.gz"
# Keep the last 14 daily backups, delete older ones
find "$BACKUP_DIR" -name 'db-*.sql.gz' -mtime +14 -delete
```

Cron: `0 3 * * * bash /home/<account>/domains/<yourdomain>/bin/backup-database.sh >> /home/<account>/domains/<yourdomain>/storage/Logs/backup-cron.log 2>&1`

`--single-transaction` gets a consistent snapshot without locking
tables — safe to run against a live database with active traffic.

### VPS

Same `mysqldump` approach, plus consider shipping the backup off-box
(rsync to a second server, or an S3-compatible bucket via `aws s3 cp`
/ `rclone`) — a backup that lives on the same disk as the database it
backs up doesn't protect against disk failure or account compromise.

## File backup

```bash
tar czf "storage-backup-$(date +%Y%m%d).tar.gz" storage/KnowledgeBase/
```

Same retention/off-box principles as the database dump. If storage
volume is large, an incremental approach (`rsync -a --link-dest`) is
more efficient than a fresh full archive every time.

## Restore procedure

### Database

```bash
gunzip -c db-20260709-030000.sql.gz | mysql --host="$DB_HOST" --user="$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE"
```

Restoring into a database that already has data will conflict on
every primary key and unique constraint — restore into a **fresh**
database (or truncate every table first) unless you specifically want
to merge, which this dump format doesn't support cleanly.

### Files

```bash
tar xzf storage-backup-20260709.tar.gz -C /path/to/deployed/app/
```

Confirm ownership/permissions match what the web server expects after
extracting (`chown -R <web-server-user> storage/`).

### After any restore

1. Run `php bin/migrate.php` (not `fresh`) — confirms the restored schema matches what the current codebase expects; harmless no-op if it already matches.
2. Spot-check: log in as a known user, open a known bot, confirm a known conversation's messages are present.
3. Check `cron_job_runs` — if the restore rolled back past the last cron execution, the next scheduled run will simply pick up where it left off; no manual intervention needed.

## Backup testing

A backup you've never restored is a hypothesis, not a backup. At
minimum, once after initial setup: take a backup, restore it into a
throwaway database, and confirm the app runs against it. Repeat
whenever the schema changes significantly (a new phase's migrations).
