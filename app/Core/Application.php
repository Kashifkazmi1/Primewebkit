<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Router;
use App\Exceptions\Handler;
use Throwable;

/**
 * The application kernel: owns the container and router, wraps the
 * entire request lifecycle in the central exception handler so that
 * no unhandled Throwable ever produces raw HTML/stack-trace output.
 */
final class Application
{
    private readonly Container $container;
    private readonly Router $router;
    private readonly Handler $exceptionHandler;

    public function __construct()
    {
        $this->container = Container::getInstance();
        $this->container->instance(Container::class, $this->container);

        $this->router = new Router($this->container);
        $this->container->instance(Router::class, $this->router);

        $this->exceptionHandler = new Handler();
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function handle(Request $request): Response
    {
        $this->container->instance(Request::class, $request);

        try {
            return $this->router->dispatch($request);
        } catch (Throwable $e) {
            return $this->exceptionHandler->handle($e);
        }
    }
}
