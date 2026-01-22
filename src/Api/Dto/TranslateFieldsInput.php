<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for the translate fields endpoint.
 */
final class TranslateFieldsInput
{
    /**
     * List of field names to translate. If empty, all translatable fields will be translated.
     *
     * @var list<string>
     */
    #[Assert\Type('array')]
    #[Groups(['ContentElements:Translation:Input'])]
    public array $fields = [];

    /**
     * The target locale to translate to.
     */
    #[Assert\NotBlank]
    #[Groups(['ContentElements:Translation:Input'])]
    public string $targetLocale;
}
