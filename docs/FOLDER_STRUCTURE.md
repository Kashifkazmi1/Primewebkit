# Folder Structure Guide

```
ai-chatbot-saas/
├── app/
│   ├── Controllers/
│   │   ├── Admin/          Super-admin endpoints (dashboard, users, plans, billing, settings, logs)
│   │   ├── Api/            Authenticated account-owner endpoints (bots, subscriptions, webhooks, ...)
│   │   ├── Auth/           Registration, login, password reset, email verification
│   │   ├── Public/         Unauthenticated widget endpoints (chat, lead capture, ratings)
│   │   └── Team/           Team management endpoints
│   ├── Core/               Framework internals — see below
│   ├── DTO/                Typed data-transfer objects (AI requests/responses, payment provider types)
│   ├── Exceptions/         Every custom exception type, all extending ApiException
│   ├── Helpers/            Global helper functions (env(), config(), now_utc(), str_uuid4(), ...)
│   ├── Middlewares/        Route middleware (auth, RBAC, permissions, rate limits, usage limits, CORS, ...)
│   ├── Models/             Plain readonly value objects (Bot, User, Plan, Subscription, ...) — not ORM entities
│   ├── Repositories/       One per table (or closely related group), all extending BaseRepository
│   ├── Requests/           FormRequest validators, one per endpoint/action, grouped by resource
│   ├── Resources/          Response-shaping classes (toPublicArray() equivalents for list responses)
│   └── Services/           Business logic — see below
│       ├── AI/             Chat orchestration, RAG pipeline, embeddings, prompt engineering, providers/
│       ├── Admin/          Admin-only aggregation services (dashboard stats, user management)
│       ├── Notifications/  Notification delivery channels (in-app, email)
│       └── Payment/        PaymentProviderInterface + ManualPaymentProvider + factory
├── bin/                    CLI entry points — migrations, seeders, and the three cron scripts
├── bootstrap/              app.php (kernel bootstrap) + bindings.php (DI container interface bindings)
├── config/                 One file per concern (app, database, jwt, cors, gemini, billing, ...)
├── database/
│   ├── Migrations/         43 files, one class each, strictly additive
│   └── Seeds/              Roles, permissions, super-admin, default plans
├── docs/                   Everything referenced from README.md's documentation index
├── public/                 Web server document root — index.php (front controller) + .htaccess only
├── resources/
│   └── mail-templates/     HTML email templates (verify-email, reset-password)
├── routes/
│   └── api.php             Every route, grouped by prefix/middleware
├── storage/
│   ├── Cache/              File-based rate-limit state
│   ├── KnowledgeBase/      Uploaded documents, one subdirectory per bot — never web-accessible
│   ├── Logs/               Monolog output, one file per channel, daily-rotated
│   └── Uploads/            Reserved for future general file uploads
├── tests/
│   ├── Unit/               Pure-logic tests, no DB dependency (SsrfGuard, Validator)
│   └── Feature/            Reserved for HTTP-level tests — see tests/Feature/README.md
├── .env.example            Every environment variable, documented inline
├── composer.json
├── phpunit.xml
└── (root docs — see README.md's index)
```

## `app/Core/` in detail

The hand-rolled micro-framework everything else is built on:

```
app/Core/
├── Application.php          Kernel: resolves the router, dispatches, catches exceptions
├── Container.php             PSR-11 DI container with reflection-based autowiring
├── Contracts/                Every interface: MiddlewareInterface, AIChatProviderInterface,
│                             AIEmbeddingProviderInterface, VectorSearchRepositoryInterface,
│                             PaymentProviderInterface, NotificationChannelInterface
├── Database/
│   ├── Connection.php        PDO singleton
│   ├── QueryBuilder.php      Fluent, parameterized, soft-delete-aware
│   ├── Migration.php / MigrationRunner.php
│   ├── Seeder.php / SeedRunner.php
│   └── Schema/               Blueprint (create table) / AlterBlueprint (modify table) DDL DSL
├── Exceptions/
│   └── Handler.php           Central exception → HTTP response mapping, debug-gated detail
├── Http/
│   ├── Request.php / Response.php / JsonResponse.php / StreamedResponse.php
│   └── ExternalHttpClient.php Shared outbound HTTP client (retry/backoff, streaming)
├── Logging/
│   └── LoggerFactory.php     Monolog, one channel per concern
├── Routing/
│   └── Router.php / Route.php Route registration, middleware pipeline, param extraction
├── Security/
│   ├── PasswordHasher.php
│   ├── RateLimiter.php       File-based, per-key
│   └── SsrfGuard.php         Validates user-supplied URLs before any outbound request (Phase 6)
└── Support/
    ├── Config.php            Dot-notation config file reader
    └── TokenEstimator.php    Rough token-count heuristic (char/4) for budget calculations
```

## Naming conventions

- **Repositories** are named `{Table}Repository` and extend `BaseRepository`, which provides `find`, `create`, `update`, `delete`, `all`, and a `query()` escape hatch returning a `QueryBuilder` for anything more specific.
- **Services** contain business logic and are the only layer that should be injected into controllers — controllers never talk to repositories directly.
- **Requests** are named `{Action}{Resource}Request` and expose a static `validate(Request $request): array` — the only way validated data should reach a service.
- **Resources** shape data for API responses, named `{Resource}Resource`, with static `make()` (single item) and `collection()` (list) methods.

## Current file counts

| Category | Count |
|---|---|
| Controllers | 23 |
| Services | 47 |
| Repositories | 35 |
| Middlewares | 10 |
| Models | 12 |
| Requests | 31 |
| Migrations | 43 |

(See the final report at the end of this project's build for the
complete, verified statistics across the whole codebase.)
