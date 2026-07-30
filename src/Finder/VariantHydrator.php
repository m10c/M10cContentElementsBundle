<?php

declare(strict_types=1);

namespace M10c\ContentElements\Finder;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use M10c\ContentElements\Metadata\MetadataRegistry;
use Webmozart\Assert\Assert;

/**
 * Hydrates Identity entities with their appropriate Variant data based on the current context.
 *
 * This is useful for providers that query Identity entities directly (e.g. via DQL)
 * rather than using IdentityWithVariantProvider.
 *
 * This base implementation only sets the variant property. For additional behavior
 * like object mapping (BC layer), decorate this service in your project.
 */
class VariantHydrator implements VariantHydratorInterface
{
    public function __construct(
        private readonly MetadataRegistry $metadataRegistry,
        private readonly VariantFinder $variantFinder,
    ) {
    }

    #[\Override]
    public function hydrate(object $identity, array $extraDimensionContext = []): void
    {
        if (!$this->tryHydrate($identity, $extraDimensionContext)) {
            throw $this->noVariantException($identity);
        }
    }

    #[\Override]
    public function tryHydrate(object $identity, array $extraDimensionContext = []): bool
    {
        return [] !== $this->doHydrateAll([$identity], $extraDimensionContext);
    }

    #[\Override]
    public function hydrateAll(iterable $identities, array $extraDimensionContext = []): void
    {
        $identities = $this->toList($identities);
        $hydrated = $this->doHydrateAll($identities, $extraDimensionContext);

        foreach ($identities as $key => $identity) {
            if (!isset($hydrated[$key])) {
                throw $this->noVariantException($identity);
            }
        }
    }

    #[\Override]
    public function tryHydrateAll(iterable $identities, array $extraDimensionContext = []): void
    {
        $this->doHydrateAll($this->toList($identities), $extraDimensionContext);
    }

    #[\Override]
    public function tryHydrateAllFiltered(array|Collection $identities, array $extraDimensionContext = []): array
    {
        return array_values($this->doHydrateAll($this->toList($identities), $extraDimensionContext));
    }

    /**
     * Sets the variant on every identity that has one, in one query per identity class.
     *
     * @template TKey of array-key
     * @template T of object
     *
     * @param array<TKey, T>       $identities
     * @param array<string, mixed> $extraDimensionContext
     *
     * @return array<TKey, T> The identities that got a variant
     */
    private function doHydrateAll(array $identities, array $extraDimensionContext): array
    {
        $identitiesByClass = [];
        foreach ($identities as $key => $identity) {
            $identitiesByClass[ClassUtils::getClass($identity)][$key] = $identity;
        }

        $hydrated = [];
        foreach ($identitiesByClass as $identityClass => $group) {
            $identityAttribute = $this->metadataRegistry->getIdentityMetadata($identityClass);
            Assert::notNull($identityAttribute, "Class {$identityClass} is missing identity metadata");

            foreach ($this->variantFinder->findOneForEach($group, $extraDimensionContext) as $key => $variant) {
                $group[$key]->{$identityAttribute->variantProperty} = $variant;
                $hydrated[$key] = $group[$key];
            }
        }

        // Grouping and the query both reorder, so put the caller's order back
        ksort($hydrated);

        return $hydrated;
    }

    /**
     * @template T of object
     *
     * @param iterable<T> $identities
     *
     * @return list<T>
     */
    private function toList(iterable $identities): array
    {
        return \is_array($identities) ? array_values($identities) : iterator_to_array($identities, false);
    }

    private function noVariantException(object $identity): \Exception
    {
        $id = property_exists($identity, 'id') ? $identity->id : '?';
        Assert::scalar($id);

        return new \Exception(sprintf('%s %s found no variant', $identity::class, $id));
    }
}
