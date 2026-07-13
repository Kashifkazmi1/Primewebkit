<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Exceptions\ApiException;
use stdClass;

/**
 * Standard JSON API response envelope used by every endpoint:
 *
 * {
 *   "status": 200,
 *   "success": true,
 *   "message": "OK",
 *   "data": {...},
 *   "errors": {},
 *   "pagination": null
 * }
 */
final class JsonResponse extends Response
{
    private function __construct(
        int $statusCode,
        bool $success,
        string $message,
        mixed $data,
        array $errors,
        ?array $pagination
    ) {
        $payload = [
            'status' => $statusCode,
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => empty($errors) ? new stdClass() : $errors,
            'pagination' => $pagination,
        ];

        parent::__construct(
            (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            $statusCode,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $statusCode = 200,
        ?array $pagination = null
    ): self {
        return new self($statusCode, true, $message, $data, [], $pagination);
    }

    public static function created(mixed $data = null, string $message = 'Resource created successfully.'): self
    {
        return new self(201, true, $message, $data, [], null);
    }

    public static function noContent(string $message = 'No content.'): self
    {
        return new self(204, true, $message, null, [], null);
    }

    /**
     * @param array<string, list<string>>|array<int, string> $errors
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        array $errors = []
    ): self {
        return new self($statusCode, false, $message, null, $errors, null);
    }

    public static function fromException(ApiException $exception): self
    {
        return new self(
            $exception->getStatusCode(),
            false,
            $exception->getMessage(),
            null,
            $exception->getErrors(),
            null
        );
    }
}
