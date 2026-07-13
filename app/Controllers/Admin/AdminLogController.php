<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\ValidationException;
use SplFileObject;

/**
 * Exposes the tail of the platform's rotating log files
 * (storage/Logs/*.log) to the admin panel — "Error Logs" reads the
 * `system` channel (where the central exception handler logs every
 * 5xx), "Security Logs" reads the `auth` channel.
 */
final class AdminLogController
{
    private const CHANNEL_FILES = [
        'system' => 'system.log',
        'auth' => 'auth.log',
        'api' => 'api.log',
        'app' => 'app.log',
        'activity' => 'activity.log',
        'ai' => 'ai.log',
    ];

    public function errorLogs(Request $request): Response
    {
        return $this->tail($request, 'system');
    }

    public function securityLogs(Request $request): Response
    {
        return $this->tail($request, 'auth');
    }

    public function channel(Request $request, string $channel): Response
    {
        return $this->tail($request, $channel);
    }

    private function tail(Request $request, string $channel): Response
    {
        if (!isset(self::CHANNEL_FILES[$channel])) {
            throw new ValidationException(['channel' => ['Unknown log channel.']]);
        }

        $lines = max(10, min(1000, (int) $request->query('lines', 200)));
        $path = storage_path('Logs/' . self::CHANNEL_FILES[$channel]);

        if (!is_file($path)) {
            return JsonResponse::success(['lines' => []], 'No log entries yet.');
        }

        return JsonResponse::success(['lines' => $this->tailFile($path, $lines)], 'Log entries retrieved successfully.');
    }

    /**
     * @return list<string>
     */
    private function tailFile(string $path, int $lineCount): array
    {
        $file = new SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $start = max(0, $totalLines - $lineCount);
        $file->seek($start);

        $lines = [];

        while (!$file->eof()) {
            $line = $file->fgets();

            if ($line !== false && trim($line) !== '') {
                $lines[] = rtrim($line);
            }
        }

        return $lines;
    }
}
