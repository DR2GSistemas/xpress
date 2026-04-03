<?php

declare(strict_types=1);

namespace Xpress\Result;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

final class XResult implements JsonSerializable, Countable, IteratorAggregate
{
    private mixed $value;
    private ?XError $error;
    private bool $success;

    private function __construct(mixed $value, ?XError $error, bool $success)
    {
        $this->value = $value;
        $this->error = $error;
        $this->success = $success;
    }

    public static function ok(mixed $value = null): self
    {
        return new self($value, null, true);
    }

    public static function fail(string $message, int $code = 500, array $data = []): self
    {
        return new self(null, new XError($message, $code, $data), false);
    }

    public static function error(XError $error): self
    {
        return new self(null, $error, false);
    }

    public static function fromThrowable(\Throwable $e, bool $hideInternal = true): self
    {
        $message = $hideInternal ? 'An error occurred' : $e->getMessage();
        $code = $e->getCode() ?: 500;

        return self::fail($message, $code, [
            'exception' => $hideInternal ? null : get_class($e),
            'file' => $hideInternal ? null : $e->getFile(),
            'line' => $hideInternal ? null : $e->getLine(),
        ]);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getValueOr(mixed $default): mixed
    {
        return $this->success ? $this->value : $default;
    }

    public function getError(): ?XError
    {
        return $this->error;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error?->getMessage();
    }

    public function getErrorCode(): int
    {
        return $this->error?->getCode() ?? 0;
    }

    public function getErrorData(): array
    {
        return $this->error?->getData() ?? [];
    }

    public function getCode(): int
    {
        return $this->success ? 200 : ($this->error?->getCode() ?? 500);
    }

    public function withCode(int $code): self
    {
        if ($this->isSuccess()) {
            return self::ok($this->value)->withHttpCode($code);
        }
        return new self(null, $this->error?->withCode($code), false);
    }

    public function withHttpCode(int $code): self
    {
        return new self($this->value, $this->error?->withCode($code), $this->success);
    }

    public function getMessage(): string
    {
        return $this->success ? 'OK' : ($this->error?->getMessage() ?? 'Unknown error');
    }

    public function unwrap(): mixed
    {
        if ($this->isFailure()) {
            throw new \RuntimeException(
                'Called unwrap on a failed Result: ' . $this->error?->getMessage()
            );
        }
        return $this->value;
    }

    public function unwrapOr(mixed $default): mixed
    {
        return $this->success ? $this->value : $default;
    }

    public function unwrapOrElse(callable $default): mixed
    {
        return $this->success ? $this->value : $default($this->error);
    }

    public function unwrapOrNull(): mixed
    {
        return $this->success ? $this->value : null;
    }

    public function expect(string $message): mixed
    {
        if ($this->isFailure()) {
            throw new \RuntimeException($message);
        }
        return $this->value;
    }

    public function map(callable $fn): self
    {
        if ($this->isFailure()) {
            return $this;
        }
        return self::ok($fn($this->value));
    }

    public function mapError(callable $fn): self
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return self::error($fn($this->error));
    }

    public function mapOr(mixed $default, callable $fn): mixed
    {
        if ($this->isSuccess()) {
            return $fn($this->value);
        }
        return $default;
    }

    public function and(self $result): self
    {
        if ($this->isFailure()) {
            return $this;
        }
        return $result;
    }

    public function andThen(callable $fn): self
    {
        if ($this->isFailure()) {
            return $this;
        }
        $result = $fn($this->value);
        if (!$result instanceof self) {
            return self::ok($result);
        }
        return $result;
    }

    public function or(self $result): self
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return $result;
    }

    public function orElse(callable $fn): self
    {
        if ($this->isSuccess()) {
            return $this;
        }
        $result = $fn($this->error);
        if (!$result instanceof self) {
            return self::ok($result);
        }
        return $result;
    }

    public function toArray(): array
    {
        if ($this->isSuccess()) {
            return [
                'success' => true,
                'data' => $this->value,
                'error' => null,
            ];
        }
        return [
            'success' => false,
            'data' => null,
            'error' => [
                'message' => $this->error?->getMessage(),
                'code' => $this->error?->getCode(),
                'data' => $this->error?->getData(),
            ],
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function count(): int
    {
        return $this->isSuccess() ? 1 : 0;
    }

    public function getIterator(): ArrayIterator
    {
        if ($this->isSuccess() && is_array($this->value)) {
            return new ArrayIterator($this->value);
        }
        return new ArrayIterator($this->isSuccess() ? [$this->value] : []);
    }

    public function toResponse(): \Xpress\XResponse
    {
        $response = new \Xpress\XResponse();

        if ($this->isSuccess()) {
            if (is_array($this->value)) {
                return $response->json($this->value, $this->getCode());
            }
            return $response->json(['data' => $this->value], $this->getCode());
        }

        $errorData = [
            'error' => [
                'message' => $this->getMessage(),
                'code' => $this->getErrorCode(),
            ],
        ];

        $errorData['error']['data'] = $this->getErrorData();

        return $response->json($errorData, $this->getCode());
    }

    public function send(): void
    {
        $this->toResponse()->send();
    }

    public function __toString(): string
    {
        if ($this->isSuccess()) {
            return 'XResult::ok(' . json_encode($this->value) . ')';
        }
        return 'XResult::fail("' . $this->getMessage() . '", ' . $this->getErrorCode() . ')';
    }

    public function isNotFound(): bool
    {
        return $this->isFailure() && $this->getErrorCode() === 404;
    }

    public function isUnauthorized(): bool
    {
        return $this->isFailure() && $this->getErrorCode() === 401;
    }

    public function isForbidden(): bool
    {
        return $this->isFailure() && $this->getErrorCode() === 403;
    }

    public function isBadRequest(): bool
    {
        return $this->isFailure() && $this->getErrorCode() === 400;
    }

    public function isServerError(): bool
    {
        return $this->isFailure() && $this->getErrorCode() >= 500;
    }
}
