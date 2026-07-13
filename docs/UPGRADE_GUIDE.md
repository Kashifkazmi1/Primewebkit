# Upgrade Guide

How to safely deploy a new version of this codebase to an existing
production environment.

## Before every deploy

1. **Back up the database** (see `docs/BACKUP_RESTORE.md`) — even a "safe" additive migration deserves this discipline; it costs a minute and removes the single biggest source of deploy-related panic.
2. **Read the CHANGELOG.md** entry for the version you're deploying — note any migrations, new required environment variables, or breaking changes called out there.
3. **Diff `.env.example`** between your currently-deployed version and the new one — new required variables need real values *before* the app starts using them, not discovered via a runtime error.

## Standard deploy procedure

```bash
# 1. Pull/upload the new code to a staging path, not directly over the live one
# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run new migrations (never `fresh` against production — that drops everything)
php bin/migrate.php

# 4. If new permissions/plans were added in this version, re-run seeders
#    (every seeder checks for existing rows first — safe to re-run)
php bin/seed.php

# 5. Swap the new code into place (atomic if your deploy method supports it —
#    e.g. a symlink swap — to avoid a window where old and new code coexist)

# 6. If opcache.validate_timestamps=0 (see docs/PHP_CONFIGURATION.md), clear it
#    explicitly — otherwise stale bytecode from the old deploy may persist
```

## Migration safety

This project's migrations are **strictly additive** — every schema
change since Phase 1 has been a new migration file, never an edit to
an already-shipped one (see `docs/DEVELOPER_GUIDE.md`). This means:
- Running `php bin/migrate.php` against a production database that's behind is always safe — it only ever applies migrations it hasn't already run, tracked in the `migrations` table.
- Rolling back (`php bin/migrate.php rollback`) undoes the most recent batch — use this if a deploy needs to be reverted and the new migrations haven't been in production long enough to have real data depending on them yet. Rolling back a migration that's been live for a while with real dependent data is much riskier — evaluate case by case.

## Zero-downtime considerations

For a typical single-server Hostinger deployment, brief downtime
during a deploy (the few seconds a migration takes to run, or a
symlink swap) is generally acceptable and not worth the complexity of
avoiding entirely. If you need true zero-downtime:
- Run migrations *before* swapping code, and ensure new migrations are backward-compatible with the *previous* version of the code for the brief window both might be running (e.g., adding a nullable column is safe; renaming or dropping a column the old code still reads is not — do that in two separate deploys).
- This project hasn't needed this discipline yet (no column renames/drops across any phase) — but it's the right principle if you start needing one.

## Upgrading the Gemini integration

If Google changes their API (a new model name, a deprecated endpoint,
a wire-format change): all of that logic is isolated to
`GeminiProvider` (see `docs/GEMINI_INTEGRATION.md`) — no other file
should need touching for a same-provider API change. Confirm by
running the full AI chat flow against the updated provider in a
staging environment before deploying to production.

## Adding a payment gateway (Stripe or otherwise)

This is the most significant "upgrade" this platform is designed to
anticipate without a rewrite:
1. Implement `PaymentProviderInterface` in a new class.
2. Add one `match` arm in `PaymentProviderFactory`.
3. Set `BILLING_PROVIDER` to the new provider's name.
4. Existing `manual`-provider subscriptions keep working under their original provider value — only *new* subscriptions use the new provider. There is no automatic migration of existing manual subscriptions to a new gateway; that's a business decision, not something to automate blindly.

## Rolling back a bad deploy

1. Swap code back to the previous version (reverse of whatever deploy mechanism you used).
2. If the bad deploy included migrations that already ran: `php bin/migrate.php rollback` for the affected batch, **only if** no production data yet depends on the new schema (check timestamps — how long was the bad version live?).
3. If data *does* depend on the new schema already, don't blindly roll back the schema — fix forward instead (deploy a corrected version of the new code against the schema that's already there).
