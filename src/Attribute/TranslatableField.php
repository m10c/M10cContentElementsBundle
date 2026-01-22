<?php

declare(strict_types=1);

namespace M10c\ContentElements\Attribute;

/**
 * Marks a property as translatable.
 *
 * Use this attribute on string properties in Variant entities
 * to indicate they can be translated via the CMS translation feature.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class TranslatableField
{
}
