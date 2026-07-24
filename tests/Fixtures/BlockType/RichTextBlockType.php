<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Fixtures\BlockType;

use M10c\ContentElements\Block\BlockTypeInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

final class RichTextBlockType implements BlockTypeInterface
{
    public function getKey(): string
    {
        return 'rich-text';
    }

    public function getDataConstraints(): Constraint
    {
        return new Assert\Collection([
            'fields' => [
                'body' => [new Assert\NotBlank(), new Assert\Type('string')],
            ],
            'allowExtraFields' => false,
            'allowMissingFields' => false,
        ]);
    }

    public function getDefaultData(): array
    {
        return ['body' => ''];
    }

    public function getSchema(): array
    {
        return [
            'label' => 'Rich text',
            'fields' => [
                'body' => [
                    'kind' => 'markdown',
                    'label' => 'Body',
                    'features' => ['bold', 'italic', 'lists', 'links'],
                ],
            ],
        ];
    }
}
