<?php

declare(strict_types=1);

namespace Xpress;

use Xpress\Attributes\XGroup;
use Xpress\Attributes\XMiddleware;
use Xpress\Attributes\XRoute;

final class XRouteCollector
{
    private array $routes = [];

    public function registerControllers(array $controllers): void
    {
        foreach ($controllers as $controller) {
            $this->registerController($controller);
        }
    }

    public function registerController(string|object $controller): void
    {
        $class = is_object($controller) ? $controller::class : $controller;
        $reflection = new \ReflectionClass($class);

        $groupAttr = $this->getGroupAttribute($reflection);
        $groupPrefix = $groupAttr?->prefix ?? '';
        $groupMiddlewares = $this->flattenMiddlewares($groupAttr?->middlewares ?? []);

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $routeAttr = $this->getRouteAttribute($method);
            if ($routeAttr === null) {
                continue;
            }

            $methodMiddlewares = $this->getMethodMiddlewares($method);
            $allMiddlewares = array_merge($groupMiddlewares, $methodMiddlewares);

            $fullPath = $this->normalizePath($groupPrefix . $routeAttr->path);

            $this->routes[] = new XRoute(
                path: $fullPath,
                methods: $routeAttr->methods,
                handler: [$class, $method->getName()],
                name: $routeAttr->name,
                summary: $routeAttr->summary,
                middlewares: $allMiddlewares
            );
        }
    }

    public function addRoute(
        string $path,
        string|array $method,
        callable $handler,
        array $middlewares = [],
        ?string $name = null,
        ?string $summary = null
    ): self {
        $methods = array_map('strtoupper', (array) $method);

        $this->routes[] = new XRoute(
            path: $this->normalizePath($path),
            methods: $methods,
            handler: $handler,
            name: $name,
            summary: $summary,
            middlewares: $middlewares
        );

        return $this;
    }

    public function get(string $path, callable $handler, array $middlewares = []): self
    {
        return $this->addRoute($path, 'GET', $handler, $middlewares);
    }

    public function post(string $path, callable $handler, array $middlewares = []): self
    {
        return $this->addRoute($path, 'POST', $handler, $middlewares);
    }

    public function put(string $path, callable $handler, array $middlewares = []): self
    {
        return $this->addRoute($path, 'PUT', $handler, $middlewares);
    }

    public function patch(string $path, callable $handler, array $middlewares = []): self
    {
        return $this->addRoute($path, 'PATCH', $handler, $middlewares);
    }

    public function delete(string $path, callable $handler, array $middlewares = []): self
    {
        return $this->addRoute($path, 'DELETE', $handler, $middlewares);
    }

    public function options(string $path, callable $handler, array $middlewares = []): self
    {
        return $this->addRoute($path, 'OPTIONS', $handler, $middlewares);
    }

    public function head(string $path, callable $handler, array $middlewares = []): self
    {
        return $this->addRoute($path, 'HEAD', $handler, $middlewares);
    }

    public function any(string|array $methods, string $path, callable $handler, array $middlewares = []): self
    {
        return $this->addRoute($path, $methods, $handler, $middlewares);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function findRoute(string $method, string $path): ?XRoute
    {
        foreach ($this->routes as $route) {
            if ($route->matches($method, $path) !== null) {
                return $route;
            }
        }
        return null;
    }

    private function getRouteAttribute(\ReflectionMethod $method): ?XRoute
    {
        $attributes = $method->getAttributes(XRoute::class);
        if (empty($attributes)) {
            return null;
        }
        return $attributes[0]->newInstance();
    }

    private function getGroupAttribute(\ReflectionClass $class): ?XGroup
    {
        $attributes = $class->getAttributes(XGroup::class);
        if (empty($attributes)) {
            return null;
        }
        return $attributes[0]->newInstance();
    }

    private function getMethodMiddlewares(\ReflectionMethod $method): array
    {
        $middlewares = [];
        $attributes = $method->getAttributes(XMiddleware::class);

        foreach ($attributes as $attr) {
            $middlewareAttr = $attr->newInstance();
            $middlewares = array_merge($middlewares, $this->flattenMiddlewares($middlewareAttr->middlewares));
        }

        return $middlewares;
    }

    private function flattenMiddlewares(string|array $middlewares): array
    {
        if (is_string($middlewares)) {
            return [$middlewares];
        }
        return $middlewares;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
