<?php

declare(strict_types=1);

namespace M10c\ContentElements\Attribute\Filter;

use M10c\ContentElements\Attribute\FilterInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Archivable implements FilterInterface
{
}
