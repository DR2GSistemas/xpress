<?php

declare(strict_types=1);

namespace Xpress;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;

final class XRequest
{
    private ?array $routeParams = null;

    public function __construct(
        private readonly ServerRequestInterface $request
    ) {}

    public static function fromGlobals(): self
    {
        return new self(ServerRequest::fromGlobals());
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    public function getPath(): string
    {
        return $this->request->getUri()->getPath();
    }

    public function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->request->getQueryParams()[$key] ?? $default;
    }

    public function getBody(): string
    {
        return (string) $this->request->getBody();
    }

    public function getParsedBody(): mixed
    {
        return $this->request->getParsedBody();
    }

    public function getJson(): ?array
    {
        $content = (string) $this->request->getBody();
        if (empty($content)) {
            return null;
        }
        return json_decode($content, true) ?? null;
    }

    public function getHeader(string $name): ?string
    {
        $header = $this->request->getHeader($name);
        return $header[0] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    public function getContentType(): ?string
    {
        return $this->getHeader('Content-Type');
    }

    public function isJson(): bool
    {
        return str_contains($this->getContentType() ?? '', 'application/json');
    }

    public function isXml(): bool
    {
        return str_contains($this->getContentType() ?? '', 'application/xml');
    }

    public function getRouteParams(): array
    {
        return $this->routeParams ?? [];
    }

    public function getRouteParam(string $name, mixed $default = null): mixed
    {
        return $this->routeParams[$name] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->request->getAttribute($name, $default);
    }

    public function withAttribute(string $name, mixed $value): self
    {
        return new self($this->request->withAttribute($name, $value));
    }

    public function getServerParams(): array
    {
        return $this->request->getServerParams();
    }

    public function getUploadedFiles(): array
    {
        return $this->request->getUploadedFiles();
    }

    public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): self
    {
        return new self($this->request->withProtocolVersion($version));
    }

    public function getCookies(): array
    {
        return $this->request->getCookieParams();
    }

    public function getCookie(string $name, mixed $default = null): mixed
    {
        return $this->getCookies()[$name] ?? $default;
    }

    public function getRequestTarget(): string
    {
        return $this->request->getRequestTarget();
    }

    public function withRequestTarget(string $requestTarget): self
    {
        return new self($this->request->withRequestTarget($requestTarget));
    }

    public function withMethod(string $method): self
    {
        return new self($this->request->withMethod($method));
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): self
    {
        return new self($this->request->withUri($uri, $preserveHost));
    }

    public function withBody($body): self
    {
        return new self($this->request->withBody($body));
    }

    public function withQueryParams(array $query): self
    {
        return new self($this->request->withQueryParams($query));
    }

    public function withHeader(string $name, $value): self
    {
        return new self($this->request->withHeader($name, $value));
    }

    public function withAddedHeader(string $name, $value): self
    {
        return new self($this->request->withAddedHeader($name, $value));
    }

    public function withoutHeader(string $name): self
    {
        return new self($this->request->withoutHeader($name));
    }

    public function withParsedBody($data): self
    {
        return new self($this->request->withParsedBody($data));
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        return new self($this->request->withUploadedFiles($uploadedFiles));
    }

    public function withCookieParams(array $cookies): self
    {
        return new self($this->request->withCookieParams($cookies));
    }

    public function getOriginal(): ServerRequestInterface
    {
        return $this->request;
    }
}
