<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

/**
 * Middleware receives the request and a $next closure that continues
 * the pipeline. It must return a Response — either by calling
 * $next($request) or by short-circuiting with its own Response
 * (e.g. a 401 from an auth middleware).
 */
interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next, string ...$params): Response;
}
