<?php

declare(strict_types=1);

namespace Xpress;

use Psr\Http\Message\ResponseInterface;

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
