<?php

declare(strict_types=1);

namespace M10c\ContentElements\Finder;

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
    public function hydrate(object $identity): void
    {
        $identityClass = ClassUtils::getClass($identity);
        $identityAttribute = $this->metadataRegistry->getIdentityMetadata($identityClass);
        Assert::notNull($identityAttribute, sprintf("Class {$identityClass} is missing identity metadata"));

        $variant = $this->variantFinder->findOne($identity);
        if ($variant) {
            $identity->{$identityAttribute->variantProperty} = $variant;
        }
    }

    #[\Override]
    public function hydrateAll(iterable $identities): void
    {
        foreach ($identities as $identity) {
            $this->hydrate($identity);
        }
    }
}
