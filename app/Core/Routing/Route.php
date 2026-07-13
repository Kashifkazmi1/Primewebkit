<?php

declare(strict_types=1);

namespace App\Core\Routing;

/**
 * Represents a single compiled route definition.
 */
final class Route
{
    private string $pattern;

    /**
     * @var list<string>
     */
    private array $paramNames = [];

    /**
     * @param array{0: class-string, 1: string}|\Closure $action Controller [class, method] tuple or closure.
     * @param list<string> $middleware Each entry is a class-string, optionally suffixed with ":param1,param2".
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array|\Closure $action,
        private array $middleware = []
    ) {
        $this->pattern = $this->compile($uri);
    }

    private function compile(string $uri): string
    {
        $uri = rtrim($uri, '/') ?: '/';

        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(:[^}]+)?\}/',
            function (array $matches) {
                $this->paramNames[] = $matches[1];
                $constraint = isset($matches[2]) ? substr($matches[2], 1) : '[^/]+';

                return '(' . $constraint . ')';
            },
            $uri
        );

        return '#^' . $pattern . '$#';
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function matches(string $method, string $path): bool
    {
        return $this->method === $method && preg_match($this->pattern, $path) === 1;
    }

    /**
     * @return array<string, string>
     */
    public function extractParams(string $path): array
    {
        preg_match($this->pattern, $path, $matches);
        array_shift($matches);

        return array_combine($this->paramNames, $matches) ?: [];
    }

    public function action(): array|\Closure
    {
        return $this->action;
    }

    /**
     * @return list<string>
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * @param list<string> $middleware
     */
    public function withMiddleware(array $middleware): self
    {
        $this->middleware = [...$this->middleware, ...$middleware];

        return $this;
    }
}
