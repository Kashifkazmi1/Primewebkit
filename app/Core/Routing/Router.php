<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\ApiException;
use App\Exceptions\NotFoundException;
use Closure;

/**
 * Minimal, fast HTTP router with route groups (prefix + middleware
 * stacking) and a middleware pipeline resolved through the container.
 */
final class Router
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    /**
     * @var list<string> stack of active group prefixes
     */
    private array $groupPrefixStack = [];

    /**
     * @var list<list<class-string>> stack of active group middleware
     */
    private array $groupMiddlewareStack = [];

    public function __construct(private readonly Container $container)
    {
    }

    public function get(string $uri, array|Closure $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, array|Closure $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, array|Closure $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, array|Closure $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, array|Closure $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function options(string $uri, array|Closure $action): Route
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * @param array{prefix?: string, middleware?: list<class-string>} $attributes
     */
    public function group(array $attributes, Closure $callback): void
    {
        $this->groupPrefixStack[] = trim($attributes['prefix'] ?? '', '/');
        $this->groupMiddlewareStack[] = $attributes['middleware'] ?? [];

        $callback($this);

        array_pop($this->groupPrefixStack);
        array_pop($this->groupMiddlewareStack);
    }

    private function addRoute(string $method, string $uri, array|Closure $action): Route
    {
        $prefix = implode('/', array_filter($this->groupPrefixStack));
        $fullUri = '/' . trim($prefix . '/' . ltrim($uri, '/'), '/');
        $fullUri = $fullUri === '' ? '/' : $fullUri;

        $middleware = empty($this->groupMiddlewareStack) ? [] : array_merge(...$this->groupMiddlewareStack);

        $route = new Route($method, $fullUri, $action, $middleware);
        $this->routes[] = $route;

        return $route;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();
        $path = $request->uri();

        $pathMatchedAnyMethod = false;
        $matchedRouteForPath = null;

        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                $request->setRouteParams($route->extractParams($path));

                return $this->runPipeline($route, $request);
            }

            if ($route->matches($route->method(), $path)) {
                $pathMatchedAnyMethod = true;
                $matchedRouteForPath ??= $route;
            }
        }

        // CORS preflight: browsers send a real OPTIONS request before
        // the actual cross-origin call, but no route is ever
        // registered for the OPTIONS method itself (routes are
        // registered per real HTTP verb only) — so without this
        // check, every preflight would fall through to the 405 below
        // and CorsMiddleware (which is what actually answers
        // preflights) would never run at all.
        //
        // This deliberately runs *only* CorsMiddleware, never the
        // matched route's full middleware stack — a preflight request
        // never carries the Authorization header, request body, or
        // anything else that JwtAuthMiddleware/RoleMiddleware/
        // UsageLimiterMiddleware/etc. depend on, so running those for
        // a preflight would make every authenticated endpoint fail
        // CORS with a 401 instead of succeeding with a 204. Per the
        // CORS spec, preflight is a permissions handshake the browser
        // does unilaterally — it has nothing to do with whether the
        // real request will ultimately be authorized.
        if ($request->method() === 'OPTIONS' && $matchedRouteForPath !== null) {
            /** @var \App\Middlewares\CorsMiddleware $cors */
            $cors = $this->container->resolve(\App\Middlewares\CorsMiddleware::class);

            return $cors->handle(
                $request,
                fn (Request $req): Response => \App\Core\Http\JsonResponse::success(null, 'Preflight OK', 204)
            );
        }

        if ($pathMatchedAnyMethod) {
            throw new class ("The [{$method}] method is not supported for this route.") extends ApiException {
                public function __construct(string $message)
                {
                    parent::__construct($message, 405, 'METHOD_NOT_ALLOWED');
                }
            };
        }

        throw new NotFoundException("Route [{$method} {$path}] not found.");
    }

    private function runPipeline(Route $route, Request $request): Response
    {
        $pipeline = array_reduce(
            array_reverse($route->middleware()),
            function (Closure $next, string $middlewareSpec) {
                return function (Request $request) use ($middlewareSpec, $next): Response {
                    [$middlewareClass, $params] = $this->parseMiddlewareSpec($middlewareSpec);

                    /** @var \App\Core\Contracts\MiddlewareInterface $middleware */
                    $middleware = $this->container->resolve($middlewareClass);

                    return $middleware->handle($request, $next, ...$params);
                };
            },
            fn (Request $request): Response => $this->callAction($route, $request)
        );

        return $pipeline($request);
    }

    /**
     * @return array{0: class-string, 1: list<string>}
     */
    private function parseMiddlewareSpec(string $spec): array
    {
        if (!str_contains($spec, ':')) {
            return [$spec, []];
        }

        [$class, $paramString] = explode(':', $spec, 2);

        return [$class, array_map('trim', explode(',', $paramString))];
    }

    private function callAction(Route $route, Request $request): Response
    {
        $action = $route->action();
        $params = array_values($route->extractParams($request->uri()));

        if ($action instanceof Closure) {
            return $action($request, ...$params);
        }

        [$controllerClass, $methodName] = $action;

        /** @var object $controller */
        $controller = $this->container->resolve($controllerClass);

        return $controller->{$methodName}($request, ...$params);
    }
}