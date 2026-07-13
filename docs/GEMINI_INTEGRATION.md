# Gemini Integration Guide

How this platform talks to Google's Generative Language API, and how
to extend or replace it.

## Setup

1. Get a key from [Google AI Studio](https://aistudio.google.com/app/apikey) (free tier available).
2. Set in `.env`:
   ```
   GEMINI_API_KEY=your-key-here
   GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
   GEMINI_MODEL=gemini-1.5-flash
   GEMINI_EMBEDDING_MODEL=text-embedding-004
   GEMINI_EMBEDDING_DIMENSIONS=768
   GEMINI_MAX_OUTPUT_TOKENS=2048
   GEMINI_TIMEOUT_SECONDS=30
   ```
3. No further setup — embeddings, chat, and streaming all work through the same key.

## Architecture

`GeminiProvider` (`app/Services/AI/Providers/GeminiProvider.php`)
implements two interfaces:

- `AIChatProviderInterface` — `chat()`, `chatStream()`
- `AIEmbeddingProviderInterface` — `embed()`, `embeddingDimensions()`

Nothing else in the codebase references `GeminiProvider` directly —
every consumer (`ChatOrchestratorService`, `EmbeddingService`,
`RagPipelineService`, etc.) depends on the interfaces, resolved
through `AIProviderFactory`. This is the seam for adding a second
provider (OpenAI, Claude, etc.): write a new class implementing the
same two interfaces, add one `match` arm in
`AIProviderFactory::resolve()`, done.

## Authentication

The API key is sent via the `x-goog-api-key` request header, **not**
as a URL query parameter. This was a deliberate Phase 6 security fix
— a key embedded in a URL leaks into error messages, retry logs, and
any intermediary that logs request URLs, none of which happens with
a header. See `SECURITY.md`.

## Request/response mapping

Gemini's wire format differs from a "standard" chat API in a few
ways this codebase translates:

| Gemini concept | This app's concept |
|---|---|
| `contents: [{role: "user"\|"model", parts: [{text}]}]` | `ChatRequest::$messages` (role `user`/`assistant`, translated to `user`/`model`) |
| `systemInstruction: {parts: [{text}]}` | `ChatRequest::$systemPrompt` — a separate field, not a message in `contents` |
| `generationConfig: {temperature, maxOutputTokens, topP, topK}` | `Bot`'s corresponding fields, passed straight through |
| `safetySettings: [{category, threshold}]` | `Bot::$safetySettings`, falls back to `config('ai.default_safety_settings')` |
| `?alt=sse` streaming, `data: {...}\n\n` frames | `chatStream()`'s `$onChunk` callback, one call per frame |
| `candidates[0].finishReason === "SAFETY"` | `ChatResponse::$wasBlocked = true`, mid-generation block |
| `promptFeedback.blockReason` | `ChatResponse::$wasBlocked = true`, blocked before generating anything |

## Streaming

`chatStream()` uses `ExternalHttpClient::stream()`, which drives cURL
with `CURLOPT_WRITEFUNCTION` — each chunk of the response body is
handed to a callback as it arrives over the wire, rather than waiting
for the full response. The callback buffers partial SSE frames (a
frame can arrive split across multiple TCP reads) and emits a
`StreamChunk` for each complete `data: {...}` frame it assembles.

The `$shouldStop` callback (checked between chunks) is how
`POST /widget/{botUuid}/conversations/{id}/stop` actually interrupts
an in-progress generation — it polls a DB flag
(`conversations.cancel_requested_at`) and returns `false` from cURL's
write callback to abort the transfer immediately.

## Retry behavior

`ExternalHttpClient::request()` (non-streaming calls) retries up to 3
times with exponential backoff + jitter on timeouts, connection
failures, `5xx`, and `429`. It does **not** retry on `4xx` (e.g. an
invalid API key) — that class of error won't resolve itself by
retrying, so failing fast avoids wasting time and quota. Streaming
requests are never retried mid-stream — a partially-delivered stream
can't be safely replayed to a client that's already received part of
it.

## Safety settings

`config/ai.php`'s `default_safety_settings` apply to every bot unless
it sets its own `safety_settings` (see `docs/API.md`). Categories:
`HARM_CATEGORY_HARASSMENT`, `HARM_CATEGORY_HATE_SPEECH`,
`HARM_CATEGORY_SEXUALLY_EXPLICIT`, `HARM_CATEGORY_DANGEROUS_CONTENT`.
Thresholds: `BLOCK_NONE` | `BLOCK_ONLY_HIGH` | `BLOCK_MEDIUM_AND_ABOVE`
| `BLOCK_LOW_AND_ABOVE`. When Gemini blocks a response, the platform
persists a polite fallback message rather than the empty/blocked
content, and logs `status: blocked` in `ai_usage_logs`.

## Cost tracking

Every call — chat, streaming, embeddings, title generation, suggested
questions — is logged to `ai_usage_logs` with token counts and an
estimated cost from `CostEstimatorService` × `config/ai_pricing.php`
(all `PRICING_GEMINI_*` env vars default to `0`, matching Gemini's
free tier; set real per-1k-token prices once you're on a paid tier).

## Testing without hitting the real API

During this project's own development, a local PHP server
(`mock-gemini/index.php`, not part of the shipped codebase) replicated
Gemini's exact wire protocol — request/response shapes, SSE framing,
error codes, even deterministic mock embedding vectors — so the full
pipeline could be tested against real HTTP calls without incurring
API costs or requiring a live key. Point `GEMINI_BASE_URL` at such a
mock server (`http://127.0.0.1:8899` or similar) for local development
or CI if you build one; the codebase has no awareness of whether it's
talking to the real API or a compatible stand-in.

## Adding a second provider

1. `app/Services/AI/Providers/YourProvider.php` implementing `AIChatProviderInterface` and/or `AIEmbeddingProviderInterface`.
2. A `config/yourprovider.php` for its credentials/defaults.
3. One new `match` arm in `AIProviderFactory::resolve()`.
4. Set the bot's `ai_provider` field to your new provider's name.

Nothing in `RagPipelineService`, `PromptEngineService`,
`ChatOrchestratorService`, `EmbeddingService`, or any controller needs
to change.
