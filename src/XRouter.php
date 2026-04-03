<?php

declare(strict_types=1);

namespace Xpress;

use Psr\Http\Message\ResponseInterface;

final class XRouter
{
    private static ?XRouter $instance = null;

    private readonly XRouteCollector $collector;

    private array $globalMiddlewares = [];

    private string $basePath = '';

    public function __construct()
    {
        $this->collector = new XRouteCollector();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function setBasePath(string $basePath): self
    {
        $this->basePath = '/' . ltrim($basePath, '/');
        return $this;
    }

    public function use(callable|string $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    public function get(string $path, callable $handler, array $middlewares = []): self
    {
        $this->collector->get($this->prefixPath($path), $handler, $middlewares);
        return $this;
    }

    public function post(string $path, callable $handler, array $middlewares = []): self
    {
        $this->collector->post($this->prefixPath($path), $handler, $middlewares);
        return $this;
    }

    public function put(string $path, callable $handler, array $middlewares = []): self
    {
        $this->collector->put($this->prefixPath($path), $handler, $middlewares);
        return $this;
    }

    public function patch(string $path, callable $handler, array $middlewares = []): self
    {
        $this->collector->patch($this->prefixPath($path), $handler, $middlewares);
        return $this;
    }

    public function delete(string $path, callable $handler, array $middlewares = []): self
    {
        $this->collector->delete($this->prefixPath($path), $handler, $middlewares);
        return $this;
    }

    public function options(string $path, callable $handler, array $middlewares = []): self
    {
        $this->collector->options($this->prefixPath($path), $handler, $middlewares);
        return $this;
    }

    public function head(string $path, callable $handler, array $middlewares = []): self
    {
        $this->collector->head($this->prefixPath($path), $handler, $middlewares);
        return $this;
    }

    public function any(string|array $methods, string $path, callable $handler, array $middlewares = []): self
    {
        $this->collector->any($methods, $this->prefixPath($path), $handler, $middlewares);
        return $this;
    }

    public function registerControllers(array $controllers): self
    {
        $this->collector->registerControllers($controllers);
        return $this;
    }

    public function dispatch(?XRequest $request = null): ResponseInterface
    {
        $request = $request ?? XRequest::fromGlobals();
        $path = $request->getPath();
        $method = $request->getMethod();

        if ($this->basePath && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath)) ?: '/';
        }

        $route = $this->collector->findRoute($method, $path);

        if ($route === null) {
            return $this->handleNotFound($request);
        }

        $params = $route->matches($method, $path);
        $request->setRouteParams($params);

        $allMiddlewares = array_merge($this->globalMiddlewares, $route->middlewares);

        if (empty($allMiddlewares)) {
            return $route->execute($request);
        }

        $handler = new XMiddlewareHandler($allMiddlewares, function (XRequest $req) use ($route) {
            return $route->execute($req);
        });

        return $handler->handle($request);
    }

    public function dispatchFromUri(string $uri, string $method = 'GET', array $serverParams = []): ResponseInterface
    {
        $uri = parse_url($uri);
        $path = $uri['path'] ?? '/';
        $query = $uri['query'] ?? '';

        $serverParams = array_merge([
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $path . ($query ? '?' . $query : ''),
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => $query,
        ], $serverParams);

        $request = new XRequest(
            new \GuzzleHttp\Psr7\ServerRequest(
                $method,
                new \GuzzleHttp\Psr7\Uri($path),
                [],
                null,
                '1.1',
                $serverParams
            )
        );

        return $this->dispatch($request);
    }

    public function handleNotFound(XRequest $request): ResponseInterface
    {
        return (new XResponse())->json([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found',
            'path' => $request->getPath(),
            'method' => $request->getMethod()
        ], 404);
    }

    public function handleMethodNotAllowed(XRequest $request): ResponseInterface
    {
        return (new XResponse())->json([
            'error' => 'Method Not Allowed',
            'message' => 'The requested method is not allowed for this resource',
            'path' => $request->getPath(),
            'method' => $request->getMethod()
        ], 405);
    }

    public function handleError(\Throwable $e, XRequest $request): ResponseInterface
    {
        $status = $e instanceof \HttpException ? $e->getCode() : 500;
        $message = $e instanceof \HttpException ? $e->getMessage() : 'Internal Server Error';

        if (php_sapi_name() === 'cli') {
            throw $e;
        }

        return (new XResponse())->json([
            'error' => $message,
            'code' => $status,
            'trace' => $this->isDebugMode() ? $e->getTraceAsString() : null
        ], $status);
    }

    public function getRoutes(): array
    {
        return $this->collector->getRoutes();
    }

    public function getCollector(): XRouteCollector
    {
        return $this->collector;
    }

    private function prefixPath(string $path): string
    {
        if (empty($this->basePath) || $path === '/') {
            return $path;
        }
        return $this->basePath . '/' . ltrim($path, '/');
    }

    private function isDebugMode(): bool
    {
        return (bool) ($_ENV['APP_DEBUG'] ?? false);
    }
}

class HttpException extends \Exception {}
