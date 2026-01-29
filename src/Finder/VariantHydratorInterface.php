<?php

declare(strict_types=1);

namespace M10c\ContentElements\Finder;

use Doctrine\Common\Collections\Collection;

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
     * @param array<string, mixed> $extraDimensionContext Extra context keyed by dimension key
     *
     * @throws \Exception If no variant is found
     */
    public function hydrate(object $identity, array $extraDimensionContext = []): void;

    /**
     * Try to hydrate a single identity with its variant.
     *
     * @param array<string, mixed> $extraDimensionContext Extra context keyed by dimension key
     *
     * @return bool Whether a variant was found and set
     */
    public function tryHydrate(object $identity, array $extraDimensionContext = []): bool;

    /**
     * Hydrate multiple identities with their variants.
     *
     * @param iterable<object> $identities
     * @param array<string, mixed> $extraDimensionContext Extra context keyed by dimension key
     */
    public function hydrateAll(iterable $identities, array $extraDimensionContext = []): void;

    /**
     * Try to hydrate all identities, removing those without a variant.
     *
     * @template T of object
     *
     * @param array<T>|Collection<int, T> $identities
     * @param array<string, mixed>        $extraDimensionContext Extra context keyed by dimension key
     *
     * @return array<T>|Collection<int, T>
     */
    public function tryHydrateAllPruned(array|Collection $identities, array $extraDimensionContext = []): array|Collection;
}
