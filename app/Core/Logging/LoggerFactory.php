<?php

declare(strict_types=1);

namespace App\Core\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;

/**
 * Produces PSR-3 loggers for named channels (app, api, auth, activity,
 * system), each writing to its own daily-rotated file under
 * storage/Logs. Rotation/retention keeps shared-hosting disk usage
 * bounded.
 */
final class LoggerFactory
{
    /**
     * @var array<string, LoggerInterface>
     */
    private static array $channels = [];

    public static function channel(string $name = 'app'): LoggerInterface
    {
        if (isset(self::$channels[$name])) {
            return self::$channels[$name];
        }

        $path = config("logging.channels.{$name}.path") ?? storage_path("Logs/{$name}.log");
        $level = self::resolveLevel((string) config('logging.level', 'info'));
        $retentionDays = (int) config('logging.retention_days', 14);

        $handler = new RotatingFileHandler($path, $retentionDays, $level);
        $handler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        ));

        $logger = new Monolog($name);
        $logger->pushHandler($handler);

        return self::$channels[$name] = $logger;
    }

    private static function resolveLevel(string $level): Level
    {
        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Info,
        };
    }
}
