<?php

declare(strict_types=1);

use App\Core\Http\Request;

/** @var App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

$request = Request::capture();
$response = $app->handle($request);
$response->send();
