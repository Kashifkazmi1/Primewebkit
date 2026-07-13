# API

This is a REST API overview. **Full endpoint-by-endpoint documentation
— request/response shapes, validation rules, permissions, examples —
lives in `docs/api.md`.** This file is the map; that one is the detail.

## Base URL & versioning

```
https://yourdomain.com/api/v1
```

## Authentication

Two methods, depending on the endpoint:

- **JWT Bearer token** (`Authorization: Bearer <token>`) — for every authenticated dashboard/account endpoint. Obtained via `POST /auth/login`, short-lived (15 min default), paired with a rotating refresh token (`POST /auth/refresh`).
- **None** — for public widget endpoints (`/widget/*`), gated instead by a per-bot domain allow-list (`WidgetOriginMiddleware`) checked against the request's `Origin` header.

Personal API keys (`X-API-Key` header) are supported at the
middleware level (`ApiKeyAuthMiddleware`) for future server-to-server
integrations but are not currently wired to any route by default.

## Response envelope

Every response, success or failure, has this shape:

```json
{
  "status": 200,
  "success": true,
  "message": "Human-readable summary.",
  "data": {},
  "errors": {},
  "pagination": null
}
```

`pagination` is populated (`total`, `page`, `per_page`, `last_page`)
for any paginated list endpoint.

## Status codes with specific meaning in this API

| Code | Meaning |
|---|---|
| `200` / `201` | Success |
| `204` | No content (CORS preflight) |
| `401` | Not authenticated (missing/invalid/expired token) |
| `403` | Authenticated, but not authorized (wrong role, or lacks a specific permission) |
| `402` | **Plan limit or feature-flag gate** — distinct from 403; means "upgrade your plan," not "you're not allowed" |
| `404` | Resource not found, **or** found but not owned by the authenticated user (ownership is never leaked via a 403) |
| `422` | Validation failed — `errors` contains per-field messages |
| `429` | Rate limited |
| `500` | Server error (detail only included when `APP_DEBUG=true`) |

## Endpoint groups (see `docs/api.md` for full detail on each)

| Group | Auth | Covers |
|---|---|---|
| `/auth/*` | Mixed | Register, login, refresh, password reset, email verification |
| `/bots/*` | JWT | Bot CRUD, knowledge sources, widget config, conversations, leads, usage, analytics |
| `/widget/*` | Domain allow-list | Public chat (streaming + non-streaming), lead capture, ratings |
| `/subscriptions/*` | JWT | Plan browsing, subscribe, cancel, invoices |
| `/teams/*` | JWT | Team creation, invitations, member management |
| `/webhooks/*` | JWT | Your own outgoing webhook registrations |
| `/notifications/*` | JWT | In-app notifications |
| `/white-label` | JWT | Branding customization (plan-gated) |
| `/api-keys/*` | JWT | Personal API key management, rotation |
| `/admin/*` | JWT + role + permission | Super Admin dashboard, user/plan/billing/settings management |

## Rate limits

Three independent layers — see `SECURITY.md` for the full rationale:
1. General per-IP throttle (`SECURITY_RATE_LIMIT_*` env vars, default 60/min)
2. AI-specific per-bot+visitor throttle on chat endpoints (`AI_CHAT_RATE_LIMIT_*`, default 20/min)
3. Plan usage limits (messages/month, bots, storage, etc. — returns `402`, not `429`)

## Webhooks (outgoing)

See `docs/WEBHOOKS.md` for the full integration guide — registration,
signature verification, retry behavior, event payloads.

## Further reading

- `docs/api.md` — every endpoint, in full detail
- `docs/GEMINI_INTEGRATION.md` — how AI chat/embedding calls work under the hood
- `docs/RAG_ARCHITECTURE.md` — how knowledge-base retrieval works
- `SECURITY.md` — authentication, authorization, and the Phase 6 security audit
