<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Dto;

/**
 * Output DTO containing translated field values.
 *
 * The frontend receives these values and can apply them to a form,
 * then submit via the existing PATCH endpoint.
 *
 * @see \M10c\ContentElements\Serializer\TranslateFieldsOutputNormalizer
 */
final class TranslateFieldsOutput
{
    /**
     * @param array<string, string|null> $translations Field name => translated value
     */
    public function __construct(
        public readonly array $translations,
    ) {
    }
}
