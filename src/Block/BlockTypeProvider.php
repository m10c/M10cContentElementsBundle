<?php

declare(strict_types=1);

namespace M10c\ContentElements\Block;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

/**
 * @implements ProviderInterface<BlockTypeResource>
 */
final class BlockTypeProvider implements ProviderInterface
{
    public function __construct(
        private readonly BlockTypeRegistry $registry,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     * @param mixed[]                 $context
     *
     * @return iterable<BlockTypeResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $items = [];
        foreach ($this->registry->all() as $blockType) {
            $schema = $blockType->getSchema();
            $items[] = new BlockTypeResource(
                key: $blockType->getKey(),
                label: $schema['label'],
                fields: $schema['fields'],
            );
        }

        return $items;
    }
}
