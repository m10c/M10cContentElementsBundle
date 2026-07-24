<?php

declare(strict_types=1);

namespace M10c\ContentElements\Block;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class BlockTypeRegistry
{
    /** @var array<string, BlockTypeInterface> */
    private array $byKey = [];

    /**
     * @param iterable<BlockTypeInterface> $blockTypes
     */
    public function __construct(
        #[AutowireIterator('m10c.content_elements.block_type')]
        iterable $blockTypes,
    ) {
        foreach ($blockTypes as $blockType) {
            $this->byKey[$blockType->getKey()] = $blockType;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->byKey[$key]);
    }

    public function get(string $key): BlockTypeInterface
    {
        if (!isset($this->byKey[$key])) {
            throw new \InvalidArgumentException(sprintf('Unknown block type "%s".', $key));
        }

        return $this->byKey[$key];
    }

    /** @return array<string, BlockTypeInterface> */
    public function all(): array
    {
        return $this->byKey;
    }
}
