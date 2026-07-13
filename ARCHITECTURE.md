# Architecture

High-level overview. **Full detail, phase by phase, including every
design decision's rationale and the bugs found while building each
piece, lives in `docs/architecture.md`.**

## What this is

A multi-tenant AI chatbot SaaS backend — REST API only, no frontend.
PHP 8.3, hand-rolled MVC (no framework), MySQL, JWT auth, Google
Gemini for chat/embeddings. Built for Hostinger shared hosting as the
primary deployment target, with a clean upgrade path to a VPS or more
scalable infrastructure whenever a specific limitation is actually
hit — none of that is pre-built speculatively.

## Layered structure

```
Controllers  →  validate input via FormRequests, call one service, shape the response
Services     →  all business logic; the only layer that spans multiple repositories
Repositories →  one per table, the only layer that talks SQL
Models       →  plain readonly value objects, not an ORM
```

See `docs/DEVELOPER_GUIDE.md` for the principles this layering
enforces (thin controllers, no mass assignment, ownership checks in
services, etc.).

## The interface-and-factory pattern

Three subsystems follow an identical shape, deliberately: an
interface defines the contract, a factory resolves which concrete
implementation is active by name, and every consumer depends on the
interface, never the concrete class.

| Subsystem | Interface | Factory | Shipped implementation |
|---|---|---|---|
| AI chat/embeddings | `AIChatProviderInterface` / `AIEmbeddingProviderInterface` | `AIProviderFactory` | `GeminiProvider` |
| Billing | `PaymentProviderInterface` | `PaymentProviderFactory` | `ManualPaymentProvider` |
| Notifications | `NotificationChannelInterface` | (iterated directly by `NotificationService`) | `InAppNotificationChannel`, `EmailNotificationChannel` |
| Vector search | `VectorSearchRepositoryInterface` | (bound once in `bootstrap/bindings.php`) | `MySqlVectorSearchRepository` |

Adding a second AI provider, a real payment gateway, a push
notification channel, or a real vector database all follow the same
recipe: implement the interface, register it, done — see
`docs/GEMINI_INTEGRATION.md` for the pattern worked through in detail.

## Request lifecycle

```
HTTP request
  -> public/index.php (front controller)
  -> Application::handle()
  -> Router::dispatch() -- matches route, builds middleware pipeline
  -> Middleware chain (CORS, security headers, throttle, auth, RBAC, permissions, usage limits, ...)
  -> Controller action
  -> Service method(s)
  -> Repository/QueryBuilder
  -> JsonResponse
  -> Exception Handler (if anything threw) -> mapped to the right HTTP status + envelope
```

## The AI chat pipeline specifically

```
User message -> PromptSecurityService (sanitize) -> RagPipelineService (retrieve
knowledge) -> PromptEngineService (assemble prompt) -> ConversationMemoryService
(recall history) -> GeminiProvider (generate) -> persisted + logged -> response
```

Full detail in `docs/RAG_ARCHITECTURE.md`.

## Data model

38 tables across 5 build phases — identity/RBAC, core chatbot domain,
AI usage tracking, and the full business/billing/team/webhook layer.
See `docs/DATABASE_ER.md` for the complete entity-relationship
breakdown.

## Security posture

`SECURITY.md` covers this in full — SQL injection, XSS, SSRF, CSRF
applicability, authentication/authorization, rate limiting, and the
specific findings from this project's own Phase 6 audit.

## Why no framework (Laravel, Symfony)?

A deliberate constraint from the project's outset, not an oversight:
portability to shared hosting with minimal dependency footprint and
full visibility into every layer's behavior. The tradeoff is more
code written by hand; the benefit is nothing about this platform's
behavior is hidden behind a framework's own abstractions, magic, or
version-upgrade churn.

## Further reading

- `docs/architecture.md` — the complete phase-by-phase build history, with every design rationale and bug found along the way
- `docs/FOLDER_STRUCTURE.md` — where everything lives
- `docs/DATABASE_ER.md` — full schema relationships
- `docs/GEMINI_INTEGRATION.md`, `docs/RAG_ARCHITECTURE.md` — the AI layer in depth
- `docs/DEVELOPER_GUIDE.md` — extending the codebase
