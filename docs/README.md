# AI Chatbot SaaS Platform — Documentation Index

This `docs/` folder is built up incrementally, phase by phase,
alongside the codebase. The root-level `README.md` is the primary
entry point; this index covers everything under `docs/`.

| Document | Purpose |
|---|---|
| [architecture.md](architecture.md) | Complete phase-by-phase build history and design rationale |
| [api.md](api.md) | Full REST API reference, every endpoint |
| [installation.md](installation.md) | Local setup walkthrough |
| [PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md) | Pre-launch checklist |
| [PHP_CONFIGURATION.md](PHP_CONFIGURATION.md) | Recommended `php.ini` settings |
| [BACKUP_RESTORE.md](BACKUP_RESTORE.md) | Backup and restore procedures |
| [DISASTER_RECOVERY.md](DISASTER_RECOVERY.md) | Incident response by scenario |
| [CRON_JOBS.md](CRON_JOBS.md) | The three scheduled jobs, in detail |
| [WEBHOOKS.md](WEBHOOKS.md) | Integrating with outgoing webhooks |
| [GEMINI_INTEGRATION.md](GEMINI_INTEGRATION.md) | How the Gemini AI integration works |
| [RAG_ARCHITECTURE.md](RAG_ARCHITECTURE.md) | Retrieval-augmented generation pipeline |
| [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) | Extending the codebase |
| [MAINTENANCE_GUIDE.md](MAINTENANCE_GUIDE.md) | Ongoing operational care |
| [UPGRADE_GUIDE.md](UPGRADE_GUIDE.md) | Safely deploying new versions |
| [DATABASE_ER.md](DATABASE_ER.md) | Entity-relationship reference |
| [FOLDER_STRUCTURE.md](FOLDER_STRUCTURE.md) | Codebase layout |
| [ENVIRONMENT_VARIABLES.md](ENVIRONMENT_VARIABLES.md) | Every `.env` variable explained |

Root-level documents (outside `docs/`): `README.md`, `INSTALL.md`,
`DEPLOYMENT.md`, `SECURITY.md`, `API.md`, `ARCHITECTURE.md`,
`CHANGELOG.md`, `LICENSE.md`.

## Project status

**All 6 planned phases complete.**

- [x] Phase 1 — Foundation
- [x] Phase 2 — Authentication & RBAC
- [x] Phase 3 — Core domain (Bots, Knowledge, Crawling, Conversations, Widgets, Leads, API Keys)
- [x] Phase 4 — AI provider integration (Gemini, RAG, streaming, usage tracking)
- [x] Phase 5 — SaaS platform (billing, plans, teams, webhooks, analytics, admin)
- [x] Phase 6 — Production hardening (security audit, performance audit, full documentation, final QA)
