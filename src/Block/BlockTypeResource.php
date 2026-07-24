<?php

declare(strict_types=1);

namespace M10c\ContentElements\Block;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ApiResource(
    shortName: 'BlockType',
    normalizationContext: ['groups' => ['ContentElements:BlockType:V']],
    operations: [
        new GetCollection(
            uriTemplate: '/block-types',
            provider: BlockTypeProvider::class,
        ),
    ],
)]
final class BlockTypeResource
{
    /**
     * @param array<mixed> $fields
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Serializer\Groups(['ContentElements:BlockType:V'])]
        public string $key,
        #[Serializer\Groups(['ContentElements:BlockType:V'])]
        public string $label,
        #[Serializer\Groups(['ContentElements:BlockType:V'])]
        public array $fields,
    ) {
    }
}
