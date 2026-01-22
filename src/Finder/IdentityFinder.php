<?php

declare(strict_types=1);

namespace M10c\ContentElements\Finder;

use Doctrine\Common\Util\ClassUtils;
use M10c\ContentElements\Metadata\MetadataRegistry;

/**
 * Finds the Identity entity for a given Variant.
 *
 * This is the inverse of VariantFinder - given a variant entity, it discovers
 * which Identity owns it by examining the variant's properties for references
 * to entities with the #[Identity] attribute.
 */
final readonly class IdentityFinder
{
    public function __construct(
        private MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * Finds the identity entity for a given variant.
     *
     * Examines the variant's properties to find one that references an entity
     * with an #[Identity] attribute that declares this variant class.
     */
    public function findIdentity(object $variant): ?object
    {
        $variantClass = ClassUtils::getClass($variant);
        $reflection = new \ReflectionClass($variant);

        foreach ($reflection->getProperties() as $property) {
            $value = $property->getValue($variant);
            if (!\is_object($value)) {
                continue;
            }

            $identityClass = ClassUtils::getClass($value);
            $identityMetadata = $this->metadataRegistry->getIdentityMetadata($identityClass);

            if (null === $identityMetadata) {
                continue;
            }

            // Verify this Identity declares our variant class and the property name matches
            if ($identityMetadata->variantClass === $variantClass
                && $identityMetadata->identityProperty === $property->getName()) {
                return $value;
            }
        }

        return null;
    }
}
