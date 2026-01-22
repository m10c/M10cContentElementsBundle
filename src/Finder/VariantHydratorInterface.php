<?php

declare(strict_types=1);

namespace M10c\ContentElements\Finder;

/**
 * Interface for hydrating Identity entities with their Variant data.
 *
 * Implement this interface to customize how variants are attached to identities.
 * The default implementation sets the variant property; project-specific implementations
 * can add additional logic such as object mapping for backward compatibility.
 *
 * NB: Make sure the Doctrine entities are initialised before calling this,
 * not lazy proxies.
 */
interface VariantHydratorInterface
{
    /**
     * Hydrate a single identity with its variant.
     *
     * @param object $identity The identity to hydrate
     */
    public function hydrate(object $identity): void;

    /**
     * Hydrate multiple identities with their variants.
     *
     * @param iterable<object> $identities
     */
    public function hydrateAll(iterable $identities): void;
}
