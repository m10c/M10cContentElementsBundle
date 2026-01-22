<?php

declare(strict_types=1);

namespace M10c\ContentElements\Finder;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Context\ContextResolver;
use M10c\ContentElements\Dimension\DimensionInterface;
use M10c\ContentElements\Filter\FilterInterface;
use M10c\ContentElements\Metadata\DimensionMetadata;
use M10c\ContentElements\Metadata\FilterMetadata;
use M10c\ContentElements\Metadata\MetadataRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Service to apply all registered dimensions and filters to Identity queries.
 *
 * Use this service when building custom DQL queries for Identity entities
 * to ensure they are properly restricted according to the current context
 * (e.g., locale, publishable status).
 */
final readonly class IdentityQueryRestrictor
{
    /**
     * @param iterable<DimensionInterface> $dimensions
     * @param iterable<FilterInterface>    $filters
     */
    public function __construct(
        private ContextResolver $contextResolver,
        #[AutowireIterator('m10c.content_elements.dimension')]
        private iterable $dimensions,
        #[AutowireIterator('m10c.content_elements.filter')]
        private iterable $filters,
        private MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * Applies all applicable dimensions and filters to a QueryBuilder for the given Identity class.
     *
     * @param class-string                     $identityClass      The Identity entity class (e.g., Content::class)
     * @param QueryNameGeneratorInterface|null $queryNameGenerator Optional query name generator (creates new one if not provided)
     */
    public function apply(
        QueryBuilder $queryBuilder,
        string $identityClass,
        ?QueryNameGeneratorInterface $queryNameGenerator = null,
    ): void {
        $identityAttribute = $this->metadataRegistry->getIdentityMetadata($identityClass);
        if (!$identityAttribute) {
            return;
        }

        $queryNameGenerator ??= new QueryNameGenerator();
        $context = $this->contextResolver->resolve();

        // Apply all registered dimensions
        foreach ($this->dimensions as $dimension) {
            $dimensionMetadata = $this->metadataRegistry->getVariantDimensionMetadata(
                $identityAttribute->variantClass,
                $dimension->getAttributeClass()
            );

            if ($dimensionMetadata instanceof DimensionMetadata) {
                $resolvedValue = $context->dimensionResolvedValues[$dimension->getKey()] ?? null;
                $dimension->applyToIdentity(
                    $queryBuilder,
                    $queryNameGenerator,
                    $identityAttribute,
                    $dimensionMetadata,
                    $resolvedValue
                );
            }
        }

        // Apply all registered filters
        foreach ($this->filters as $filter) {
            $filterMetadata = $this->metadataRegistry->getVariantFilterMetadata(
                $identityAttribute->variantClass,
                $filter->getAttributeClass()
            );

            if ($filterMetadata instanceof FilterMetadata) {
                $resolvedValue = $context->filterResolvedValues[$filter->getKey()] ?? null;
                $filter->applyToIdentity(
                    $queryBuilder,
                    $queryNameGenerator,
                    $identityAttribute,
                    $filterMetadata,
                    $resolvedValue
                );
            }
        }
    }
}
