<?php

declare(strict_types=1);

namespace M10c\ContentElements\Metadata;

use M10c\ContentElements\Attribute\DimensionInterface;

final class DimensionMetadata
{
    public function __construct(
        public readonly string $property,
        public readonly DimensionInterface $attribute,
    ) {
    }
}
