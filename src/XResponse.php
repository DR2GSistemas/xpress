<?php

declare(strict_types=1);

namespace Xpress;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Utils;

final class XResponse implements ResponseInterface
{
    private Response $response;

    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENT_REDIRECT = 308;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PAYLOAD_TOO_LARGE = 413;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;

    public function __construct(
        int $status = 200,
        array $headers = [],
        ?string $body = null
    ) {
        $this->response = new Response($status, $headers, $body);
    }

    public static function ok(): self
    {
        return new self(self::HTTP_OK);
    }

    public static function created(?array $data = null): self
    {
        $instance = new self(self::HTTP_CREATED);
        if ($data !== null) {
            return $instance->json($data);
        }
        return $instance;
    }

    public static function noContent(): self
    {
        return new self(self::HTTP_NO_CONTENT);
    }

    public static function notFound(): self
    {
        return new self(self::HTTP_NOT_FOUND);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(self::HTTP_UNAUTHORIZED)->text($message);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(self::HTTP_FORBIDDEN)->text($message);
    }

    public static function badRequest(string $message = 'Bad Request'): self
    {
        return new self(self::HTTP_BAD_REQUEST)->text($message);
    }

    public static function error(string $message = 'Internal Server Error', int $status = 500): self
    {
        return (new self($status))->json(['error' => $message]);
    }

    public function json(mixed $data, ?int $status = null): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $new = $this
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody(Utils::streamFor($body));
        
        if ($status !== null) {
            return $new->withStatus($status);
        }
        
        return $new;
    }

    public function text(string $content, ?int $status = null): self
    {
        $new = $this
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withBody(Utils::streamFor($content));
        
        if ($status !== null) {
            return $new->withStatus($status);
        }
        
        return $new;
    }

    public function html(string $content, ?int $status = null): self
    {
        $new = $this
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(Utils::streamFor($content));
        
        if ($status !== null) {
            return $new->withStatus($status);
        }
        
        return $new;
    }

    public function xml(string $content, ?int $status = null): self
    {
        $new = $this
            ->withHeader('Content-Type', 'application/xml; charset=utf-8')
            ->withBody(Utils::streamFor($content));
        
        if ($status !== null) {
            return $new->withStatus($status);
        }
        
        return $new;
    }

    public function redirect(string $uri, int $status = 302): self
    {
        return $this
            ->withStatus($status)
            ->withHeader('Location', $uri);
    }

    public function cookie(string $name, string $value, array $options = []): self
    {
        $options = array_merge([
            'expires' => time() + 86400,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax'
        ], $options);

        $cookie = sprintf(
            '%s=%s; Expires=%s; Path=%s%s%s; SameSite=%s',
            $name,
            urlencode($value),
            gmdate('D, d M Y H:i:s T', $options['expires']),
            $options['path'],
            $options['domain'] ? '; Domain=' . $options['domain'] : '',
            $options['httponly'] ? '; HttpOnly' : '',
            $options['samesite']
        );

        return $this->withAddedHeader('Set-Cookie', $cookie);
    }

    public function cors(array $options = []): self
    {
        $defaults = [
            'origin' => '*',
            'methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD',
            'headers' => 'Content-Type, Authorization, X-Requested-With',
            'expose_headers' => '',
            'max_age' => 86400,
            'credentials' => false
        ];

        $options = array_merge($defaults, $options);

        $response = $this
            ->withHeader('Access-Control-Allow-Origin', $options['origin'])
            ->withHeader('Access-Control-Allow-Methods', $options['methods'])
            ->withHeader('Access-Control-Allow-Headers', $options['headers']);

        if ($options['expose_headers']) {
            $response = $response->withHeader('Access-Control-Expose-Headers', $options['expose_headers']);
        }

        $response = $response
            ->withHeader('Access-Control-Max-Age', (string) $options['max_age']);

        if ($options['credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->response = $this->response->withStatus($code, $reasonPhrase);
        return $new;
    }

    public function withHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->response = $this->response->withHeader($name, $value);
        return $new;
    }

    public function withAddedHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->response = $this->response->withAddedHeader($name, $value);
        return $new;
    }

    public function withoutHeader(string $name): self
    {
        $new = clone $this;
        $new->response = $this->response->withoutHeader($name);
        return $new;
    }

    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->response = $this->response->withBody($body);
        return $new;
    }

    public function withProtocolVersion(string $version): self
    {
        $new = clone $this;
        $new->response = $this->response->withProtocolVersion($version);
        return $new;
    }

    public function toPsrResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function send(): void
    {
        $response = $this->response;

        if (headers_sent()) {
            return;
        }

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        echo $response->getBody();
    }
}
