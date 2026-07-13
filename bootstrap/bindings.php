<?php

declare(strict_types=1);

use App\Core\Contracts\VectorSearchRepositoryInterface;
use App\Core\Security\RateLimiter;
use App\Services\AI\VectorSearch\MySqlVectorSearchRepository;

/** @var App\Core\Application $app */
$container = $app->container();

$container->singleton(RateLimiter::class, fn () => new RateLimiter());

// Vector search backend. Swapping to a dedicated vector database
// later (Qdrant, Pinecone, Weaviate, Supabase Vector) means writing a
// new class implementing VectorSearchRepositoryInterface and changing
// only this one binding — RagPipelineService and EmbeddingService
// never reference MySqlVectorSearchRepository directly.
$container->singleton(VectorSearchRepositoryInterface::class, fn () => new MySqlVectorSearchRepository());
