# RAG Architecture Guide

How retrieval-augmented generation works in this platform — from a
knowledge source being uploaded to a chunk of it appearing in a
Gemini prompt.

## The pipeline, end to end

```
Knowledge source added (text / Q&A / document / website)
        │
        ▼
TextChunkerService: paragraph/sentence-boundary chunking with overlap
        │
        ▼
documents table: one row per chunk (bot_id, knowledge_source_id, content, token_count)
        │
        ▼
EmbeddingService: batches chunks (50/request) through the bot's embedding provider
        │
        ▼
documents.embedding: JSON-encoded float array, stored per chunk
        │
        │   ... later, at chat time ...
        ▼
User sends a message
        │
        ▼
RagPipelineService.retrieve():
  1. Normalize the query (trim, collapse whitespace)
  2. Embed the query (same provider/model as the knowledge base)
  3. Vector search (cosine similarity, scoped to bot_id, filtered by min_score)
  4. Rank + deduplicate near-identical chunks (>85% text similarity)
  5. Return top-K chunks with their source name
        │
        ▼
PromptEngineService.buildContextBlock(): assembles retrieved chunks into
  a token-budgeted context block, explicitly delimited as untrusted
  reference data (not instructions)
        │
        ▼
PromptEngineService.buildSystemPrompt(): global prompt → personality/tone/
  language → bot's own system prompt → context block
        │
        ▼
ConversationMemoryService.recall(): last N messages, token-trimmed
        │
        ▼
Gemini chat() / chatStream() call
        │
        ▼
Response persisted, logged, returned to the visitor
```

## Chunking strategy

`TextChunkerService` splits on paragraph boundaries first, falling
back to sentence boundaries for paragraphs that exceed the target
chunk size, with a configurable overlap between consecutive chunks
(so a fact split across a chunk boundary is still likely to appear
intact in at least one chunk). This is a heuristic, not semantic
chunking — it doesn't understand document structure beyond
paragraph/sentence punctuation, which is a reasonable tradeoff for a
system with no dependency on a second AI call just to chunk text.

## Vector storage and search

`MySqlVectorSearchRepository` stores each chunk's embedding as a
JSON-encoded array of floats directly in `documents.embedding`, and
computes cosine similarity **in PHP**, scoped to one bot at a time
(`WHERE bot_id = ? AND embedding IS NOT NULL`).

**This is a deliberate scope decision, not an oversight.** A real
vector database (Qdrant, Pinecone, Weaviate, pgvector) scales to
millions of vectors with sub-linear search; this approach scales to
what one chatbot's knowledge base actually needs — hundreds to low
thousands of chunks — which is the realistic ceiling for a single
bot's documentation/FAQ/product-catalog content, and doesn't require
provisioning a separate service most Hostinger-shared-hosting-scale
deployments have no easy way to run.

**The upgrade path exists and costs one file.**
`VectorSearchRepositoryInterface` (`upsert`, `search`, `delete`,
`deleteForBot`, `hasVector`) is the seam — `RagPipelineService` and
`EmbeddingService` depend on the interface, never the concrete MySQL
implementation. Adding a real vector database later means writing one
class implementing this interface and changing one line in
`bootstrap/bindings.php`:

```php
$container->singleton(VectorSearchRepositoryInterface::class, fn () => new QdrantVectorSearchRepository(...));
```

## Retrieval parameters

Configured via `config/ai.php` / environment variables:

| Setting | Default | Effect |
|---|---|---|
| `AI_RAG_TOP_K` | 5 | Max chunks included in the final context |
| `AI_RAG_MIN_SCORE` | 0.55 | Minimum cosine similarity to consider a chunk relevant at all |
| `AI_RAG_MAX_CONTEXT_TOKENS` | 2000 | Token budget for the assembled context block (trimmed oldest/lowest-ranked first if exceeded) |

Tuning guidance: raising `MIN_SCORE` reduces irrelevant chunks being
included at the cost of sometimes retrieving nothing for a loosely-
worded question; raising `TOP_K` or `MAX_CONTEXT_TOKENS` gives the
model more context at the cost of prompt size (and therefore latency
and per-request token cost).

## Deduplication

Overlapping chunks (common when chunk boundaries land mid-concept, or
when the same fact appears in multiple source documents) are
deduplicated by comparing normalized text similarity (`similar_text()`,
>85% threshold) after ranking by score — the highest-scoring instance
of a near-duplicate chunk is kept, others are dropped before the
top-K cut, so the model doesn't see the same fact three times at the
expense of a fourth, genuinely different, relevant chunk.

## Graceful degradation

If retrieval fails entirely (embedding API down, no embeddings yet
for a brand-new bot, etc.), the chat turn does **not** fail — it's
logged as a warning, and the assistant responds using its system
prompt and conversation memory alone, with no knowledge-base context.
A knowledge-base outage degrades response *quality*, not
*availability*.

## Prompt injection defense for retrieved content

Retrieved chunks are the higher-risk injection surface (a crawled or
uploaded document could contain text specifically crafted to hijack
the assistant, with no legitimate reason such phrasing would appear
in real content) — `PromptSecurityService` strips sentences matching
injection patterns out of retrieved chunks before they reach the
prompt, and the context block itself is explicitly delimited in the
system prompt as untrusted reference data, never instructions. See
`docs/architecture.md`'s Phase 4 section and `SECURITY.md` for the
full detail, including the specific pattern-matching approach used
and why an early exact-phrase version of it failed during testing.

## Re-embedding

If you change `GEMINI_EMBEDDING_MODEL` (or add a second embedding
provider), existing vectors are **not** comparable to queries embedded
with a different model — cosine similarity between vectors from two
different embedding spaces is meaningless. `POST /bots/{uuid}/reembed`
wipes and regenerates every vector for a bot against the currently
configured model.
