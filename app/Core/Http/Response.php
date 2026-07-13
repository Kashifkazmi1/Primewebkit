<?php

declare(strict_types=1);

namespace App\Core\Http;

class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        protected string $content = '',
        protected int $statusCode = 200,
        protected array $headers = []
    ) {
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }
}
