<?php

declare(strict_types=1);

namespace M10c\ContentElements\Metadata;

use M10c\ContentElements\Attribute\DimensionInterface;
use M10c\ContentElements\Attribute\FilterInterface;
use M10c\ContentElements\Attribute\Identity;

/**
 * Look up the ContentElements Attribute metadata from a class (returns null
 * if it doesn't have any).
 *
 * Uses in-memory caching to avoid repeated reflection calls within a request.
 */
final class MetadataRegistry
{
    /** @var array<class-string, Identity|null> */
    private array $identityCache = [];

    /** @var array<class-string, list<DimensionMetadata|FilterMetadata>> */
    private array $variantCache = [];

    /**
     * @param class-string $class
     */
    public function getIdentityMetadata(string $class): ?Identity
    {
        if (\array_key_exists($class, $this->identityCache)) {
            return $this->identityCache[$class];
        }

        $identityAttributes = (new \ReflectionClass($class))
            ->getAttributes(Identity::class);

        $result = \count($identityAttributes) > 0
            ? $identityAttributes[0]->newInstance()
            : null;

        return $this->identityCache[$class] = $result;
    }

    /**
     * @param class-string $class
     *
     * @return list<DimensionMetadata|FilterMetadata>
     */
    public function getVariantMetadata(string $class): array
    {
        if (\array_key_exists($class, $this->variantCache)) {
            return $this->variantCache[$class];
        }

        $metadata = [];
        $reflection = new \ReflectionClass($class);

        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes(DimensionInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $metadata[] = new DimensionMetadata(
                    property: $property->getName(),
                    attribute: $attribute->newInstance()
                );
            }

            foreach ($property->getAttributes(FilterInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $metadata[] = new FilterMetadata(
                    property: $property->getName(),
                    attribute: $attribute->newInstance()
                );
            }
        }

        return $this->variantCache[$class] = $metadata;
    }

    public function getVariantDimensionMetadata(string $variantClass, string $filterAttributeClass): ?DimensionMetadata
    {
        $variantMetadata = $this->getVariantMetadata($variantClass);
        foreach ($variantMetadata as $metadataItem) {
            if ($metadataItem instanceof DimensionMetadata && $metadataItem->attribute::class === $filterAttributeClass) {
                return $metadataItem;
            }
        }

        return null;
    }

    public function getVariantFilterMetadata(string $variantClass, string $filterAttributeClass): ?FilterMetadata
    {
        $variantMetadata = $this->getVariantMetadata($variantClass);
        foreach ($variantMetadata as $metadataItem) {
            if ($metadataItem instanceof FilterMetadata && $metadataItem->attribute::class === $filterAttributeClass) {
                return $metadataItem;
            }
        }

        return null;
    }
}
