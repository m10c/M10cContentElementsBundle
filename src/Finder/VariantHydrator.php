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
        if (!$this->doHydrate($identity, $extraDimensionContext)) {
            $id = property_exists($identity, 'id') ? $identity->id : '?';
            throw new \Exception(sprintf('%s %s found no variant', $identity::class, $id));
        }
    }

    #[\Override]
    public function tryHydrate(object $identity, array $extraDimensionContext = []): bool
    {
        return $this->doHydrate($identity, $extraDimensionContext);
    }

    #[\Override]
    public function hydrateAll(iterable $identities, array $extraDimensionContext = []): void
    {
        foreach ($identities as $identity) {
            $this->hydrate($identity, $extraDimensionContext);
        }
    }

    #[\Override]
    public function tryHydrateAll(iterable $identities, array $extraDimensionContext = []): void
    {
        foreach ($identities as $identity) {
            $this->tryHydrate($identity, $extraDimensionContext);
        }
    }

    #[\Override]
    public function tryHydrateAllFiltered(array|Collection $identities, array $extraDimensionContext = []): array
    {
        $items = $identities instanceof Collection ? $identities->getValues() : $identities;

        return array_values(array_filter($items, fn (object $identity) => $this->tryHydrate($identity, $extraDimensionContext)));
    }

    /**
     * @return bool Whether a variant was found and set
     */
    private function doHydrate(object $identity, array $extraDimensionContext): bool
    {
        $identityClass = ClassUtils::getClass($identity);
        $identityAttribute = $this->metadataRegistry->getIdentityMetadata($identityClass);
        Assert::notNull($identityAttribute, sprintf("Class {$identityClass} is missing identity metadata"));

        $variant = $this->variantFinder->findOne($identity, $extraDimensionContext);
        if ($variant) {
            $identity->{$identityAttribute->variantProperty} = $variant;

            return true;
        }

        return false;
    }
}
