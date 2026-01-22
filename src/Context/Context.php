<?php

declare(strict_types=1);

namespace M10c\ContentElements\Context;

final readonly class Context
{
    public function __construct(
        /**
         * @var array<string, mixed[]> Dimension key -> resolvedValue
         */
        public array $dimensionResolvedValues,
        /**
         * @var array<string, mixed[]> Filter key -> resolvedValue
         */
        public array $filterResolvedValues,
    ) {
    }
}
