<?php

declare(strict_types=1);

namespace Xpress;

use Psr\Http\Message\ResponseInterface;

final class XMiddlewareHandler
{
    private int $index = 0;

    public function __construct(
        private readonly array $middlewares,
        private readonly callable $finalHandler
    ) {}

    public function handle(XRequest $request): ResponseInterface
    {
        if ($this->index >= count($this->middlewares)) {
            return ($this->finalHandler)($request);
        }

        $middleware = $this->middlewares[$this->index++];
        $middlewareInstance = $this->resolveMiddleware($middleware);

        $next = function (XRequest $req) use ($request) {
            return $this->handle($req);
        };

        return $middlewareInstance->handle($request, $next);
    }

    private function resolveMiddleware(string|callable|object $middleware): object
    {
        if (is_object($middleware)) {
            return $middleware;
        }

        if (is_callable($middleware)) {
            return new class($middleware) {
                public function __construct(private readonly callable $callback) {}

                public function handle(XRequest $request, callable $next): ResponseInterface
                {
                    $result = ($this->callback)($request, $next);
                    return $result instanceof ResponseInterface
                        ? $result
                        : $next($request);
                }
            };
        }

        if (is_string($middleware) && class_exists($middleware)) {
            return new $middleware();
        }

        throw new \RuntimeException('Invalid middleware: ' . (is_string($middleware) ? $middleware : gettype($middleware)));
    }
}
