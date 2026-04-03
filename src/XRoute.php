<?php

declare(strict_types=1);

namespace Xpress;

use Psr\Http\Message\ResponseInterface;

final class XRoute
{
    private string $pattern;
    private array $paramNames = [];

    public function __construct(
        public readonly string $path,
        public readonly array $methods,
        public readonly callable|string|array $handler,
        public readonly ?string $name = null,
        public readonly ?string $summary = null,
        public readonly array $middlewares = []
    ) {
        $this->compilePattern();
    }

    private function compilePattern(): void
    {
        $this->pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function ($matches) {
                $this->paramNames[] = $matches[1];
                return '(?<' . $matches[1] . '>[^/]+)';
            },
            $this->path
        );

        $this->pattern = '#^' . $this->pattern . '$#';
    }

    public function matches(string $method, string $path): ?array
    {
        if (!in_array($method, $this->methods, true)) {
            return null;
        }

        if (preg_match($this->pattern, $path, $matches)) {
            $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
            return $params;
        }

        return null;
    }

    public function getParamNames(): array
    {
        return $this->paramNames;
    }

    public function execute(XRequest $request): ResponseInterface
    {
        if (is_callable($this->handler)) {
            $handler = $this->handler;
            $result = $handler($request, $request->getRouteParams());
            return $this->ensureResponse($result);
        }

        if (is_string($this->handler)) {
            [$class, $method] = $this->handler;
            if (is_array($this->handler)) {
                $instance = $class;
            } else {
                $instance = new $class();
            }
            return $instance->{$method}($request, $request->getRouteParams());
        }

        throw new \RuntimeException('Invalid route handler');
    }

    private function ensureResponse(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($result === null) {
            return XResponse::noContent();
        }

        if (is_array($result)) {
            return (new XResponse())->json($result);
        }

        if (is_string($result)) {
            return (new XResponse())->text($result);
        }

        return (new XResponse())->json(['data' => $result]);
    }
}
