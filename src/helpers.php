<?php

declare(strict_types=1);

namespace Xpress;

use Psr\Http\Message\ResponseInterface;
use Xpress\Result\XResult;
use Xpress\Result\XError;

function xpress(): XRouter
{
    return XRouter::getInstance();
}

function json(mixed $data, int $status = 200): ResponseInterface
{
    return (new XResponse())->json($data, $status);
}

function text(string $content, int $status = 200): ResponseInterface
{
    return (new XResponse())->text($content, $status);
}

function html(string $content, int $status = 200): ResponseInterface
{
    return (new XResponse())->html($content, $status);
}

function redirect(string $uri, int $status = 302): ResponseInterface
{
    return (new XResponse())->redirect($uri, $status);
}

function notFound(string $message = 'Not Found'): ResponseInterface
{
    return (new XResponse())->notFound()->text($message);
}

function unauthorized(string $message = 'Unauthorized'): ResponseInterface
{
    return (new XResponse())->unauthorized($message);
}

function badRequest(string $message = 'Bad Request'): ResponseInterface
{
    return (new XResponse())->badRequest($message);
}

function error(string $message = 'Internal Server Error', int $status = 500): ResponseInterface
{
    return (new XResponse())->error($message, $status);
}

function result_ok(mixed $value = null): XResult
{
    return XResult::ok($value);
}

function result_fail(string $message, int $code = 500, array $data = []): XResult
{
    return XResult::fail($message, $code, $data);
}

function result_error(XError $error): XResult
{
    return XResult::error($error);
}
