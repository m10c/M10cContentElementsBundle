<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Fixtures\BlockType;

use M10c\ContentElements\Block\BlockTypeInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

final class HeroBlockType implements BlockTypeInterface
{
    public function getKey(): string
    {
        return 'hero';
    }

    public function getDataConstraints(): Constraint
    {
        return new Assert\Collection([
            'fields' => [
                'headline' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 255)],
                'subhead' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 500)],
            ],
            'allowExtraFields' => false,
            'allowMissingFields' => false,
        ]);
    }

    public function getDefaultData(): array
    {
        return ['headline' => '', 'subhead' => ''];
    }

    public function getSchema(): array
    {
        return [
            'label' => 'Hero',
            'fields' => [
                'headline' => ['kind' => 'text', 'label' => 'Headline', 'maxLength' => 255],
                'subhead' => ['kind' => 'textarea', 'label' => 'Subhead', 'maxLength' => 500],
            ],
        ];
    }
}
