<?php

declare(strict_types=1);

namespace Xpress\Result;

use Xpress\XRequest;
use Xpress\XResponse;

trait XResultController
{
    protected function ok(mixed $data = null, int $code = 200): XResult
    {
        return XResult::ok($data)->withCode($code);
    }

    protected function created(mixed $data = null): XResult
    {
        return XResult::ok($data)->withCode(201);
    }

    protected function noContent(): XResult
    {
        return XResult::ok(null)->withCode(204);
    }

    protected function fail(string $message, int $code = 500, array $data = []): XResult
    {
        return XResult::fail($message, $code, $data);
    }

    protected function badRequest(string $message = 'Bad Request', array $data = []): XResult
    {
        return XResult::fail($message, 400, $data);
    }

    protected function unauthorized(string $message = 'Unauthorized', array $data = []): XResult
    {
        return XResult::fail($message, 401, $data);
    }

    protected function forbidden(string $message = 'Forbidden', array $data = []): XResult
    {
        return XResult::fail($message, 403, $data);
    }

    protected function notFound(string $message = 'Not Found', array $data = []): XResult
    {
        return XResult::fail($message, 404, $data);
    }

    protected function conflict(string $message = 'Conflict', array $data = []): XResult
    {
        return XResult::fail($message, 409, $data);
    }

    protected function validationError(array $errors): XResult
    {
        return XResult::fail('Validation failed', 422, ['errors' => $errors]);
    }

    protected function error(string $message = 'Internal Server Error', int $code = 500): XResult
    {
        return XResult::fail($message, $code);
    }

    protected function fromThrowable(\Throwable $e, bool $hideInternal = true): XResult
    {
        return XResult::fromThrowable($e, $hideInternal);
    }

    protected function try(callable $callback): XResult
    {
        try {
            $result = $callback();
            if ($result instanceof XResult) {
                return $result;
            }
            return XResult::ok($result);
        } catch (\Throwable $e) {
            return XResult::fromThrowable($e);
        }
    }
}
