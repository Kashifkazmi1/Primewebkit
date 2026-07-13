<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Core\Contracts\AIChatProviderInterface;
use App\Core\Contracts\AIEmbeddingProviderInterface;
use App\Core\Http\ExternalHttpClient;
use App\DTO\AI\ChatRequest;
use App\DTO\AI\ChatResponse;
use App\DTO\AI\EmbeddingRequest;
use App\DTO\AI\EmbeddingResponse;
use App\DTO\AI\StreamChunk;
use App\Exceptions\ExternalServiceException;
use Closure;
use JsonException;

/**
 * Google Gemini implementation of both AI provider interfaces.
 *
 * Wire format notes (Gemini REST v1beta):
 *  - Roles are "user"/"model", not "user"/"assistant" — translated here.
 *  - System prompts are a separate `systemInstruction` field, not a
 *    message in the `contents` array.
 *  - Streaming uses Server-Sent Events (`alt=sse`): a sequence of
 *    `data: {...}\n\n` frames, each a partial GenerateContentResponse.
 *  - Usage metadata (token counts) arrives on the *final* frame of a
 *    stream, and on the single response for non-streaming calls.
 */
final class GeminiProvider implements AIChatProviderInterface, AIEmbeddingProviderInterface
{
    private const NAME = 'gemini';

    private readonly ExternalHttpClient $http;
    private readonly ExternalHttpClient $streamHttp;
    private readonly string $apiKey;
    private readonly string $baseUrl;
    private readonly string $embeddingModel;
    private readonly int $embeddingDimensions;

    public function __construct()
    {
        $this->apiKey = (string) config('gemini.api_key');
        $this->baseUrl = rtrim((string) config('gemini.base_url'), '/');
        $this->embeddingModel = (string) config('gemini.embedding_model', 'text-embedding-004');
        $this->embeddingDimensions = (int) config('gemini.embedding_dimensions', 768);

        $timeout = (int) config('gemini.timeout_seconds', 30);

        $this->http = new ExternalHttpClient(timeoutSeconds: $timeout, maxAttempts: 3);
        // Streaming responses can legitimately take longer overall
        // (many small frames over one long-lived connection).
        $this->streamHttp = new ExternalHttpClient(timeoutSeconds: max($timeout, 60), maxAttempts: 1);

        if ($this->apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY is not configured.');
        }
    }

    public function providerName(): string
    {
        return self::NAME;
    }

    public function embeddingDimensions(): int
    {
        return $this->embeddingDimensions;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $start = microtime(true);
        $payload = $this->buildGenerateContentPayload($request);

        $url = "{$this->baseUrl}/models/{$request->model}:generateContent";

        $response = $this->http->request('POST', $url, $this->authHeaders(), $this->encode($payload));

        $data = $this->decode($response['body']);
        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        return $this->mapGenerateContentResponse($data, $request->model, $latencyMs);
    }

    public function chatStream(ChatRequest $request, Closure $onChunk, ?Closure $shouldStop = null): ChatResponse
    {
        $start = microtime(true);
        $payload = $this->buildGenerateContentPayload($request);

        $url = "{$this->baseUrl}/models/{$request->model}:streamGenerateContent?alt=sse";

        $buffer = '';
        $fullText = '';
        $finishReason = 'STOP';
        $usage = ['promptTokenCount' => null, 'candidatesTokenCount' => null, 'totalTokenCount' => null];
        $blocked = false;
        $blockReason = null;
        $rawSample = '';

        $status = $this->streamHttp->stream('POST', $url, $this->authHeaders(), $this->encode($payload), function (string $chunk) use (&$buffer, &$fullText, &$finishReason, &$usage, &$blocked, &$blockReason, &$rawSample, $onChunk, $shouldStop) {
            if ($shouldStop !== null && $shouldStop()) {
                return false; // aborts the cURL transfer
            }

            // Keep the first couple of KB of raw output — when Gemini
            // rejects the request (429 rate limit, 503 overloaded, …)
            // the body is a JSON error, not SSE, and this is the only
            // way to surface its message.
            if (strlen($rawSample) < 2048) {
                $rawSample .= substr($chunk, 0, 2048 - strlen($rawSample));
            }

            // Gemini's SSE stream uses CRLF (\r\n\r\n) frame separators;
            // normalize to LF so the framing below works for either
            // convention. Re-normalizing the whole buffer each chunk
            // also heals a \r\n pair split across two chunks.
            $buffer = str_replace("\r\n", "\n", $buffer . $chunk);

            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $frame = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                if (!str_starts_with($frame, 'data:')) {
                    continue;
                }

                $json = trim(substr($frame, 5));

                if ($json === '' || $json === '[DONE]') {
                    continue;
                }

                try {
                    $parsed = $this->decode($json);
                } catch (ExternalServiceException) {
                    continue; // an incomplete/malformed frame boundary — skip rather than abort the whole stream
                }

                [$deltaText, $isBlocked, $reason] = $this->extractDelta($parsed);

                if ($isBlocked) {
                    $blocked = true;
                    $blockReason = $reason;
                }

                if ($deltaText !== '') {
                    $fullText .= $deltaText;
                    $onChunk(new StreamChunk($deltaText));
                }

                if (isset($parsed['candidates'][0]['finishReason'])) {
                    $finishReason = $parsed['candidates'][0]['finishReason'];
                }

                if (isset($parsed['usageMetadata'])) {
                    $usage = [
                        'promptTokenCount' => $parsed['usageMetadata']['promptTokenCount'] ?? null,
                        'candidatesTokenCount' => $parsed['usageMetadata']['candidatesTokenCount'] ?? null,
                        'totalTokenCount' => $parsed['usageMetadata']['totalTokenCount'] ?? null,
                    ];
                }
            }

            return true;
        });

        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        if ($status >= 400) {
            $detail = $this->extractErrorMessage($rawSample) ?? "HTTP {$status}";

            throw new ExternalServiceException(self::NAME, "Gemini stream request failed ({$status}): {$detail}");
        }

        if ($blocked && $fullText === '') {
            throw new ExternalServiceException(self::NAME, 'SAFETY_BLOCKED: ' . ($blockReason ?? 'blocked by safety filter'));
        }

        if ($fullText === '') {
            // A "successful" stream with zero text is a provider fault
            // (empty candidates, truncated stream). Treat it as an error
            // so the client shows a retry message instead of silence.
            throw new ExternalServiceException(self::NAME, 'Gemini returned an empty streamed response (finish reason: ' . $finishReason . ').');
        }

        $onChunk(new StreamChunk(
            '',
            isFinal: true,
            promptTokens: $usage['promptTokenCount'],
            completionTokens: $usage['candidatesTokenCount'],
            totalTokens: $usage['totalTokenCount'],
            finishReason: $finishReason
        ));

        return new ChatResponse(
            content: $fullText,
            provider: self::NAME,
            model: $request->model,
            promptTokens: $usage['promptTokenCount'],
            completionTokens: $usage['candidatesTokenCount'],
            totalTokens: $usage['totalTokenCount'],
            finishReason: $finishReason,
            latencyMs: $latencyMs,
            wasBlocked: $blocked,
            blockReason: $blockReason
        );
    }

    /**
     * Pull `error.message` out of a (possibly truncated) Gemini JSON
     * error body. Returns null when the sample isn't parseable.
     */
    private function extractErrorMessage(string $rawSample): ?string
    {
        $decoded = json_decode(trim($rawSample), true);

        if (is_array($decoded) && isset($decoded['error']['message'])) {
            return (string) $decoded['error']['message'];
        }

        // Truncated JSON — fall back to a regex grab of the message field.
        if (preg_match('/"message"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $rawSample, $m) === 1) {
            return stripcslashes($m[1]);
        }

        return null;
    }

    public function embed(EmbeddingRequest $request): EmbeddingResponse
    {
        $start = microtime(true);
        $model = $request->model !== '' ? $request->model : $this->embeddingModel;

        if (count($request->texts) === 1) {
            $vectors = [$this->embedSingle($request->texts[0], $model)];
        } else {
            $vectors = $this->embedBatch($request->texts, $model);
        }

        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        return new EmbeddingResponse(
            vectors: $vectors,
            provider: self::NAME,
            model: $model,
            totalTokens: null, // Gemini's embedding endpoints do not return token usage
            latencyMs: $latencyMs
        );
    }

    /**
     * @return list<float>
     */
    private function embedSingle(string $text, string $model): array
    {
        $url = "{$this->baseUrl}/models/{$model}:embedContent";

        $payload = [
            'model' => "models/{$model}",
            'content' => ['parts' => [['text' => $text]]],
        ];

        $response = $this->http->request('POST', $url, $this->authHeaders(), $this->encode($payload));
        $data = $this->decode($response['body']);

        if (!isset($data['embedding']['values']) || !is_array($data['embedding']['values'])) {
            throw new ExternalServiceException('Gemini', 'Embedding response did not contain a values array.');
        }

        return array_map('floatval', $data['embedding']['values']);
    }

    /**
     * @param list<string> $texts
     * @return list<list<float>>
     */
    private function embedBatch(array $texts, string $model): array
    {
        $url = "{$this->baseUrl}/models/{$model}:batchEmbedContents";

        $requests = array_map(
            fn (string $text) => [
                'model' => "models/{$model}",
                'content' => ['parts' => [['text' => $text]]],
            ],
            $texts
        );

        $response = $this->http->request('POST', $url, $this->authHeaders(), $this->encode(['requests' => $requests]));
        $data = $this->decode($response['body']);

        if (!isset($data['embeddings']) || !is_array($data['embeddings'])) {
            throw new ExternalServiceException('Gemini', 'Batch embedding response did not contain an embeddings array.');
        }

        return array_map(
            fn (array $embedding) => array_map('floatval', $embedding['values'] ?? []),
            $data['embeddings']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGenerateContentPayload(ChatRequest $request): array
    {
        $contents = [];

        foreach ($request->messages as $message) {
            $contents[] = [
                'role' => $message->role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message->content]],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $request->temperature,
                'maxOutputTokens' => $request->maxOutputTokens,
                'topP' => $request->topP,
                'topK' => $request->topK,
            ],
        ];

        if ($request->systemPrompt !== null && $request->systemPrompt !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $request->systemPrompt]]];
        }

        if (!empty($request->safetySettings)) {
            $payload['safetySettings'] = $request->safetySettings;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mapGenerateContentResponse(array $data, string $model, int $latencyMs): ChatResponse
    {
        [$text, $blocked, $blockReason] = $this->extractDelta($data);

        $finishReason = $data['candidates'][0]['finishReason'] ?? ($blocked ? 'SAFETY' : 'STOP');
        $usage = $data['usageMetadata'] ?? [];

        return new ChatResponse(
            content: $text,
            provider: self::NAME,
            model: $model,
            promptTokens: $usage['promptTokenCount'] ?? null,
            completionTokens: $usage['candidatesTokenCount'] ?? null,
            totalTokens: $usage['totalTokenCount'] ?? null,
            finishReason: $finishReason,
            latencyMs: $latencyMs,
            wasBlocked: $blocked,
            blockReason: $blockReason
        );
    }

    /**
     * Extracts the text delta/content and safety-block status from a
     * single GenerateContentResponse (whole or partial/streamed).
     *
     * @param array<string, mixed> $data
     * @return array{0: string, 1: bool, 2: ?string}
     */
    private function extractDelta(array $data): array
    {
        if (isset($data['promptFeedback']['blockReason'])) {
            return ['', true, (string) $data['promptFeedback']['blockReason']];
        }

        $candidate = $data['candidates'][0] ?? null;

        if ($candidate === null) {
            return ['', false, null];
        }

        if (($candidate['finishReason'] ?? null) === 'SAFETY') {
            $ratings = $candidate['safetyRatings'] ?? [];
            $blockedCategory = null;

            foreach ($ratings as $rating) {
                if (($rating['blocked'] ?? false) === true) {
                    $blockedCategory = $rating['category'] ?? 'UNKNOWN';

                    break;
                }
            }

            return ['', true, $blockedCategory ?? 'SAFETY'];
        }

        $parts = $candidate['content']['parts'] ?? [];
        $text = '';

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        return [$text, false, null];
    }

    /**
     * Sends the API key via header rather than as a URL query
     * parameter. Query-string secrets are far more likely to leak —
     * into server access logs, proxy logs, browser history equivalents,
     * and (critically here) into our own error messages and retry
     * logs whenever a request fails, since those often include the
     * request URL. Google's Generative Language API supports both;
     * this platform only ever uses the header form.
     *
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encode(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ExternalServiceException('Gemini', "Failed to encode request payload: {$e->getMessage()}", $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ExternalServiceException('Gemini', "Failed to decode response payload: {$e->getMessage()}", $e);
        }

        if (!is_array($decoded)) {
            throw new ExternalServiceException('Gemini', 'Response payload was not a JSON object.');
        }

        if (isset($decoded['error'])) {
            $message = $decoded['error']['message'] ?? 'Unknown Gemini API error.';

            throw new ExternalServiceException('Gemini', (string) $message);
        }

        return $decoded;
    }
}
