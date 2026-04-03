<?php

declare(strict_types=1);

namespace Xpress\Attributes;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class XMiddleware
{
    public function __construct(
        public readonly string|array $middlewares
    ) {}
}
