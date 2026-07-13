# Database ER Diagram

38 application tables (plus `migrations`, which tracks schema version
and isn't part of the domain model). Grouped by subsystem, with every
foreign-key relationship shown. `1—N` means one row on the left can
have many matching rows on the right; `1—1` means at most one.

## Identity & access (Phase 2)

```
roles ─┬─1—N─→ users
       └─N—N─→ permissions   (via role_permission)

users ─1—N─→ sessions              (refresh tokens)
users ─1—N─→ password_resets        (by email, not FK — see note)
users ─1—N─→ email_verifications
users ─1—N─→ audit_logs             (security-relevant events)
users ─1—N─→ activity_logs          (general activity feed)
users ─N—N─→ roles                  (legacy role_user pivot — superseded by users.role_id; kept for backward compatibility)
```

`password_resets`/`email_verifications` key on `email`, not `user_id`
— intentional, so a reset request works even before an account is
confirmed to exist (see `SECURITY.md`'s note on enumeration).

## Core domain (Phase 3)

```
users ─1—N─→ bots ─1—N─→ knowledge_sources ─1—N─→ documents
                │                │
                │                └─1—N─→ website_crawl_jobs
                │
                ├─1—N─→ conversations ─1—N─→ messages
                │              │
                │              └─N—1─→ visitors
                │
                ├─1—N─→ leads
                ├─1—1─→ widgets
                └─1—N─→ api_keys

teams ─1—N─→ bots            (bots.team_id, nullable — optional team ownership)
```

`documents` is the chunked-and-embedded knowledge base — every row is
one chunk of one knowledge source, with `embedding` (JSON float array)
added in Phase 4.

## AI layer (Phase 4)

```
bots ─1—N─→ ai_usage_logs ─N—1─→ conversations (nullable)
                          ─N—1─→ messages (nullable)
```

`ai_usage_logs` is append-only — every chat/embedding/title/suggestion
call logs one row here regardless of success/failure/block status.

## Business & billing (Phase 5)

```
users ─1—N─→ subscriptions ─N—1─→ plans
                           ─N—1─→ coupons (nullable)

users ─1—N─→ invoices ─N—1─→ subscriptions (nullable)
users ─1—N─→ transactions ─N—1─→ invoices (nullable)

coupons ─1—N─→ coupon_redemptions ─N—1─→ users
                                  ─N—1─→ subscriptions (nullable)

users ─1—N─→ teams (as owner) ─1—N─→ team_members ─N—1─→ users
                              ─1—N─→ team_invitations

users ─1—N─→ webhooks ─1—N─→ webhook_logs

users ─1—N─→ usage_counters       (one row per user+metric+period)
users ─1—N─→ notifications
users ─1—1─→ white_label_settings

settings                           (global, not user-scoped — key/value)
cron_job_runs                      (global, not user-scoped — monitoring)
```

## Full table → primary purpose reference

| Table | Purpose | Phase |
|---|---|---|
| `roles`, `permissions`, `role_permission`, `role_user` | Platform-level RBAC | 2 |
| `users` | Accounts | 2 |
| `sessions` | Refresh tokens | 2 |
| `password_resets`, `email_verifications` | Auth flows | 2 |
| `audit_logs`, `activity_logs` | Security & activity history | 2 |
| `bots` | Chatbot configuration | 3 (+4, +5 additive columns) |
| `knowledge_sources`, `documents`, `website_crawl_jobs` | Knowledge base | 3 (+4 embedding column) |
| `visitors`, `conversations`, `messages` | Chat sessions | 3 (+5 rating columns) |
| `leads`, `widgets`, `api_keys` | Lead capture, widget config, API access | 3 (+5 scopes columns) |
| `ai_usage_logs` | AI call logging | 4 |
| `plans`, `subscriptions`, `invoices`, `transactions`, `coupons`, `coupon_redemptions` | Billing | 5 |
| `teams`, `team_members`, `team_invitations` | Team collaboration | 5 |
| `webhooks`, `webhook_logs` | Outgoing event notifications | 5 |
| `usage_counters` | Plan limit enforcement | 5 |
| `notifications` | In-app/email notifications | 5 |
| `settings` | Admin-editable global config | 5 |
| `white_label_settings` | Branding customization | 5 |
| `cron_job_runs` | Cron health monitoring | 5 |

## Soft deletes

Tables with a `deleted_at` column (excluded from queries by default,
recoverable): `users`, `bots`, `knowledge_sources`, `teams`, `plans`.
Everything else uses hard deletes — chosen per-table based on whether
"undo a delete" is a realistic operational need (accounts and bots:
yes; a single chat message or webhook log entry: no).

## Generating a real visual diagram

This document is a readable text representation, not a substitute for
a proper ERD tool if you want one. Generate an accurate visual any
time schema changes:

```bash
mysqldump --no-data ai_chatbot_saas > schema.sql
# then feed schema.sql to a tool like dbdiagram.io, MySQL Workbench's
# reverse-engineer feature, or similar.
```
