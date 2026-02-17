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
     * Alias used for the variant table in the shared identity subquery.
     */
    public const VARIANT_ALIAS = 'v';

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
     * Uses a single EXISTS subquery where all dimensions and filters add their constraints,
     * ensuring all criteria are evaluated against the SAME variant row. This prevents
     * incorrect results when different variants satisfy different criteria independently.
     *
     * @param class-string                     $identityClass      The Identity entity class (e.g., Content::class)
     * @param QueryNameGeneratorInterface|null $queryNameGenerator Optional query name generator (creates new one if not provided)
     * @param string|null                      $alias              Optional alias for the Identity entity (defaults to root alias)
     */
    public function apply(
        QueryBuilder $queryBuilder,
        string $identityClass,
        ?QueryNameGeneratorInterface $queryNameGenerator = null,
        ?string $alias = null,
    ): void {
        $identityAttribute = $this->metadataRegistry->getIdentityMetadata($identityClass);
        if (!$identityAttribute) {
            return;
        }

        $queryNameGenerator ??= new QueryNameGenerator();
        $context = $this->contextResolver->resolve();
        $identityAlias = $alias ?? $queryBuilder->getRootAliases()[0];
        $variantAlias = self::VARIANT_ALIAS;

        // Resolve the identity's single ID field name so we can correlate using a
        // path expression (e.g. "o.id") instead of a bare alias ("o"). This is
        // necessary because API Platform's FilterEagerLoadingExtension rewrites
        // aliases using a regex that only matches "alias." patterns — a bare alias
        // at the end of an EXISTS subquery DQL string would not be rewritten,
        // causing invalid DQL when the query is wrapped in a pagination subquery.
        $em = $queryBuilder->getEntityManager();
        $identityIdField = $em->getClassMetadata($identityClass)->getSingleIdentifierFieldName();

        // Create a single shared subquery that all dimensions and filters will contribute to.
        // This ensures all criteria are evaluated against the SAME variant row.
        $subQb = $em->createQueryBuilder();
        $subQb->select('1')
            ->from($identityAttribute->variantClass, $variantAlias)
            ->where("IDENTITY({$variantAlias}.{$identityAttribute->identityProperty}) = {$identityAlias}.{$identityIdField}");

        $hasConstraints = false;

        // Apply all registered dimensions to the shared subquery
        foreach ($this->dimensions as $dimension) {
            $dimensionMetadata = $this->metadataRegistry->getVariantDimensionMetadata(
                $identityAttribute->variantClass,
                $dimension->getAttributeClass()
            );

            if ($dimensionMetadata instanceof DimensionMetadata) {
                $resolvedValue = $context->dimensionResolvedValues[$dimension->getKey()] ?? null;
                $applied = $dimension->applyToIdentityQuery(
                    $queryBuilder,
                    $subQb,
                    $queryNameGenerator,
                    $identityAttribute,
                    $dimensionMetadata,
                    $resolvedValue,
                    $identityAlias,
                );
                $hasConstraints = $hasConstraints || $applied;
            }
        }

        // Apply all registered filters to the shared subquery
        foreach ($this->filters as $filter) {
            $filterMetadata = $this->metadataRegistry->getVariantFilterMetadata(
                $identityAttribute->variantClass,
                $filter->getAttributeClass()
            );

            if ($filterMetadata instanceof FilterMetadata) {
                $resolvedValue = $context->filterResolvedValues[$filter->getKey()] ?? null;
                $applied = $filter->applyToIdentityQuery(
                    $queryBuilder,
                    $subQb,
                    $queryNameGenerator,
                    $identityAttribute,
                    $filterMetadata,
                    $resolvedValue,
                    $identityAlias,
                );
                $hasConstraints = $hasConstraints || $applied;
            }
        }

        // Only add the EXISTS clause if at least one dimension/filter added constraints
        if ($hasConstraints) {
            $queryBuilder->andWhere($queryBuilder->expr()->exists($subQb->getDQL()));
        }
    }
}
