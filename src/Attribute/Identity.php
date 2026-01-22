<?php

declare(strict_types=1);

namespace M10c\ContentElements\Attribute;

/**
 * Marks an entity as an "Identity" that can have multiple Variants.
 *
 * The Identity/Variant pattern allows a single logical entity to have multiple
 * database rows for different Dimensions (e.g., locale, version).
 *
 * This attribute is composable - a Variant class can itself be an Identity with
 * its own Variants, enabling multi-level hierarchies like:
 *   ContentIdentity -> ContentVersion -> ContentLocalised
 *
 * @example Single-level (locale variants):
 *   #[Identity(variantClass: ContentVariant::class)]
 *   class Content { ... }
 * @example Multi-level (versions with locale variants):
 *   #[Identity(variantClass: ContentVersion::class, variantsProperty: 'versions')]
 *   class ContentIdentity { ... }
 *
 *   #[Identity(variantClass: ContentLocalised::class)]
 *   class ContentVersion {
 *       #[ManyToOne(inversedBy: 'versions')]
 *       public ContentIdentity $identity;
 *   }
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Identity
{
    public function __construct(
        /**
         * The class that holds the variant data for this identity.
         */
        public string $variantClass,

        /**
         * The property name on this Identity that holds the collection of variants.
         * Default: 'variants'. If the Identity doesn't have this property, the
         * VariantDeleteProcessor will not be usable (for read-only use cases).
         */
        public string $variantsProperty = 'variants',

        /**
         * The property name on the Variant class that references back to this Identity.
         * Default: 'identity'.
         */
        public string $identityProperty = 'identity',

        /**
         * The property name on this Identity where the resolved variant is hydrated.
         * Default: 'variant'.
         */
        public string $variantProperty = 'variant',
    ) {
    }
}
