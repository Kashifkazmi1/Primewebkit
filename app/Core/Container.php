<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\ContainerException;
use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * A minimal, dependency-free service container with:
 *  - explicit binding (bind/singleton/instance)
 *  - automatic constructor injection via reflection
 *  - singleton resolution caching
 *
 * This intentionally avoids pulling in a full framework container
 * so the platform stays portable on constrained shared hosting.
 */
final class Container implements ContainerInterface
{
    private static ?self $instance = null;

    /**
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * @var array<string, bool>
     */
    private array $shared = [];

    /**
     * @var array<string, object>
     */
    private array $resolved = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Bind an abstract type to a concrete factory. Resolved fresh every time.
     */
    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
        unset($this->shared[$abstract], $this->resolved[$abstract]);
    }

    /**
     * Bind an abstract type to a factory resolved once and cached.
     */
    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
        $this->shared[$abstract] = true;
        unset($this->resolved[$abstract]);
    }

    /**
     * Register an already-constructed instance as a singleton.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->resolved[$abstract] = $instance;
        $this->shared[$abstract] = true;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->resolved[$id]) || class_exists($id);
    }

    /**
     * @template T
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    /**
     * @template T
     * @param class-string<T>|string $abstract
     * @return ($abstract is class-string<T> ? T : mixed)
     */
    public function resolve(string $abstract): mixed
    {
        if (isset($this->resolved[$abstract])) {
            return $this->resolved[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $object = ($this->bindings[$abstract])($this);

            if (isset($this->shared[$abstract])) {
                $this->resolved[$abstract] = $object;
            }

            return $object;
        }

        return $this->autowire($abstract);
    }

    /**
     * Build a class instance via reflection, recursively resolving
     * constructor parameters that are themselves type-hinted classes.
     *
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new ContainerException("Cannot resolve unknown identifier [{$class}].");
        }

        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ContainerException("Reflection failed for [{$class}]: {$e->getMessage()}", 0, $e);
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Class [{$class}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = array_map(
            fn (ReflectionParameter $param) => $this->resolveParameter($class, $param),
            $constructor->getParameters()
        );

        return $reflection->newInstanceArgs($dependencies);
    }

    private function resolveParameter(string $class, ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            /** @var class-string $typeName */
            $typeName = $type->getName();

            return $this->resolve($typeName);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($type !== null && $type->allowsNull()) {
            return null;
        }

        throw new ContainerException(
            "Cannot resolve parameter [\${$param->getName()}] for class [{$class}]: no type hint or default value."
        );
    }
}
