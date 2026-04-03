<?php

declare(strict_types=1);

namespace Xpress\Attributes;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
final class XRoute
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';
    public const OPTIONS = 'OPTIONS';
    public const HEAD = 'HEAD';

    public readonly array $methods;

    public function __construct(
        public readonly string $path,
        string|array $method = self::GET,
        public readonly ?string $name = null,
        public readonly ?string $summary = null
    ) {
        $this->methods = array_map('strtoupper', (array) $method);
    }
}
