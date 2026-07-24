<?php

declare(strict_types=1);

namespace M10c\ContentElements\Block\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class AllBlocksValid extends Constraint
{
    public string $messageShape = 'Block at position {{ index }} must be an array with "type" and "data" keys.';
    public string $messageUnknownType = 'Block at position {{ index }} has unknown type "{{ type }}".';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
