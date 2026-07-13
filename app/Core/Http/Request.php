<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Immutable-ish representation of the incoming HTTP request.
 *
 * Wraps PHP superglobals so the rest of the application never touches
 * $_GET/$_POST/$_SERVER directly, which keeps controllers/services
 * testable and centralises input sanitisation.
 */
final class Request
{
    private array $query;
    private array $body;
    private array $server;
    private array $files;
    private array $headers;
    private ?array $jsonCache = null;
    private array $routeParams = [];
    private array $attributes = [];

    public function __construct(
        array $query,
        array $body,
        array $server,
        array $files,
        private readonly string $rawBody = ''
    ) {
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->files = $files;
        $this->headers = $this->parseHeaders($server);
    }

    public static function capture(): self
    {
        $rawBody = file_get_contents('php://input') ?: '';

        return new self($_GET, $_POST, $_SERVER, $_FILES, $rawBody);
    }

    private function parseHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }

        return $headers;
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        // Allow method override for clients that cannot send PUT/PATCH/DELETE natively.
        $override = $this->header('x-http-method-override');

        return $override !== null ? strtoupper($override) : $method;
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        return $path !== false && $path !== null ? rtrim($path, '/') ?: '/' : '/';
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . ($this->server['REQUEST_URI'] ?? '/');
    }

    public function isSecure(): bool
    {
        return (($this->server['HTTPS'] ?? '') !== '' && ($this->server['HTTPS'] ?? 'off') !== 'off')
            || ($this->server['SERVER_PORT'] ?? null) === '443'
            || strtolower($this->header('x-forwarded-proto') ?? '') === 'https';
    }

    public function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($this->server[$key])) {
                $value = $this->server[$key];

                return trim(explode(',', (string) $value)[0]);
            }
        }

        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return (string) ($this->server['HTTP_USER_AGENT'] ?? '');
    }

    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization');

        if ($header !== null && preg_match('/^Bearer\s+(.*)$/i', (string) $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Unified access to parsed JSON body, form-encoded body, or query
     * string params (for GET-style reads), depending on content type.
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        $data = $this->allInput();

        if ($key === null) {
            return $data;
        }

        return array_dot_get($data, $key, $default);
    }

    public function allInput(): array
    {
        $contentType = (string) $this->header('content-type', '');

        if (str_contains($contentType, 'application/json')) {
            return $this->json();
        }

        if (!empty($this->body)) {
            return $this->body;
        }

        // No form body and no JSON content type — but the raw body may
        // still be JSON: the embeddable widget deliberately posts as
        // text/plain so browsers treat it as a CORS "simple request"
        // (no OPTIONS preflight, which strict WAFs sometimes block).
        $json = $this->json();

        if ($json !== []) {
            return $json;
        }

        return $this->query;
    }

    public function json(): array
    {
        if ($this->jsonCache !== null) {
            return $this->jsonCache;
        }

        if (trim($this->rawBody) === '') {
            return $this->jsonCache = [];
        }

        $decoded = json_decode($this->rawBody, true);

        return $this->jsonCache = is_array($decoded) ? $decoded : [];
    }

    public function only(array $keys): array
    {
        $data = $this->allInput();

        return array_intersect_key($data, array_flip($keys));
    }

    public function except(array $keys): array
    {
        $data = $this->allInput();

        return array_diff_key($data, array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_dot_get($this->allInput(), $key, '__missing__') !== '__missing__';
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && ($this->files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Arbitrary per-request attributes set by middleware (e.g. the
     * authenticated user, JWT claims) and read downstream by controllers.
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
