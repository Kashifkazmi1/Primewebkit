# AI Chatbot SaaS Platform

A production-ready, multi-tenant AI chatbot SaaS backend — REST API
only, no frontend included. Build a Chatbase-style product on top of
it: bot creation, knowledge-base ingestion (text, Q&A, documents,
websites), an embeddable chat widget powered by Google Gemini with
retrieval-augmented generation, and a complete business layer
(subscriptions, plans, usage limits, teams, webhooks, admin
dashboard) on top.

PHP 8.3, hand-rolled MVC (no framework), MySQL, JWT authentication.
Built for Hostinger shared hosting as the primary deployment target,
with a documented path to a VPS or additional scale whenever a
specific limitation is actually hit.

## Status

All 6 planned build phases are complete. Every phase was verified
against real HTTP requests, a real MySQL instance, and a local server
replicating Google's Gemini API wire protocol exactly — not just
reviewed for plausibility. See `CHANGELOG.md` for what shipped in
each phase, and `SECURITY.md` for the Phase 6 security audit's
specific findings and fixes.

## Quick start

```bash
composer install
cp .env.example .env   # fill in DB_*, JWT_SECRET, GEMINI_API_KEY, MAIL_*
php bin/migrate.php
php bin/seed.php       # set SEED_SUPER_ADMIN_* in .env first
php -S localhost:8000 -t public
curl http://localhost:8000/api/v1/health
```

Full walkthrough: `INSTALL.md` (quick) or `docs/installation.md`
(detailed, with troubleshooting).

**Deploying to production?** Start with `DEPLOYMENT.md` and work
through `docs/PRODUCTION_CHECKLIST.md` before going live.

## What's included

- **Multi-tenant bot management** — each user creates and configures independent chatbots (system prompt, personality, tone, temperature, model settings)
- **Knowledge base ingestion** — plain text, Q&A pairs, document upload (PDF/DOCX/TXT/CSV/MD, real text extraction), and website crawling (cron-processed)
- **RAG-powered chat** — Gemini-backed responses grounded in each bot's knowledge base, with streaming, conversation memory, regenerate, and stop-generation
- **Embeddable public widget** — domain-restricted, no auth required, with lead capture and conversation ratings
- **Full JWT authentication** — access + rotating refresh tokens, account lockout, email verification, password reset
- **RBAC** — platform roles (super-admin/admin/user) plus fine-grained permissions, independent of team roles (owner/admin/editor/viewer)
- **Subscriptions & billing** — plans, trials, grace periods, renewals, coupons, invoices — Stripe-ready architecture, fully functional today without any payment gateway configured
- **Usage limits** — every plan limit and feature flag enforced by middleware, `402 Payment Required` on breach
- **Teams** — invitations, role-based collaboration, activity logging
- **Outgoing webhooks** — 9 event types, HMAC-signed, automatically retried
- **Analytics** — per-bot/day/month/year metrics, most-asked-questions, conversion rates, ratings
- **Super Admin dashboard** — platform-wide stats, user management, plan/billing administration, system health, cron monitoring, log viewing
- **Notifications, white-label branding, admin-editable settings**

## Documentation

| Start here | For |
|---|---|
| `INSTALL.md` | Getting it running locally |
| `DEPLOYMENT.md` | Hostinger shared hosting or VPS deployment |
| `API.md` -> `docs/api.md` | Every REST endpoint |
| `ARCHITECTURE.md` -> `docs/architecture.md` | How it's built and why |
| `SECURITY.md` | Threat model, audit findings, security posture |
| `docs/README.md` | Full documentation index (15+ focused guides) |

## Tech stack

PHP 8.3 - MySQL 8 / MariaDB - Google Gemini (chat + embeddings) -
JWT (firebase/php-jwt) - Monolog - PHPMailer - Composer, no framework.

## Requirements

PHP 8.3+ with `pdo_mysql`, `curl`, `mbstring`, `json`, `zip`,
`fileinfo`, `openssl` - MySQL 8+/MariaDB 10.6+ - a Google Gemini API
key (free tier available) - an SMTP provider for outgoing email.

## License

Proprietary — see `LICENSE.md`.

## Security

Found a vulnerability? See `SECURITY.md` for how to report it
responsibly.
