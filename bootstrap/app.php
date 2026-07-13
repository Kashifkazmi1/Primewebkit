<?php

declare(strict_types=1);

use App\Core\Application;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// ---------------------------------------------------------------
// Load environment variables from .env (never committed to VCS).
// ---------------------------------------------------------------
$envPath = dirname(__DIR__);

if (is_file($envPath . '/.env')) {
    Dotenv::createImmutable($envPath)->load();
}

// ---------------------------------------------------------------
// Core PHP runtime configuration.
// ---------------------------------------------------------------
date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

$debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);

ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

mb_internal_encoding('UTF-8');

// ---------------------------------------------------------------
// Boot the application container + router.
// ---------------------------------------------------------------
$app = new Application();

require dirname(__DIR__) . '/bootstrap/bindings.php';
require dirname(__DIR__) . '/routes/api.php';

return $app;
