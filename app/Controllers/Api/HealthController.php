<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database\Connection;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Throwable;

/**
 * Unauthenticated health-check endpoint used for uptime monitoring
 * and load-balancer/hosting-panel checks.
 */
final class HealthController
{
    public function index(Request $request): Response
    {
        $databaseOk = true;
        $databaseError = null;

        try {
            Connection::get()->query('SELECT 1');
        } catch (Throwable $e) {
            $databaseOk = false;
            $databaseError = 'unavailable';
        }

        $data = [
            'status' => $databaseOk ? 'ok' : 'degraded',
            'timestamp' => now_utc()->format(DATE_ATOM),
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'version' => config('app.api_version'),
            ],
            'checks' => [
                'database' => $databaseOk ? 'ok' : $databaseError,
            ],
        ];

        return JsonResponse::success($data, 'Service is healthy.', $databaseOk ? 200 : 503);
    }
}
