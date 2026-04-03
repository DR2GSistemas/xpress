<?php

declare(strict_types=1);

namespace Xpress\Attributes;

#[Attribute(Attribute::TARGET_CLASS)]
final class XGroup
{
    public function __construct(
        public readonly string $prefix = '',
        public readonly string|array $middlewares = []
    ) {}
}
