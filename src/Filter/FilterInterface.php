<?php

declare(strict_types=1);

namespace M10c\ContentElements\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Metadata\FilterMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * A Filter takes potential results out of the query, depending on how the user
 * is limited.
 */
interface FilterInterface
{
    public function getKey(): string;

    public function getAttributeClass(): string;

    /**
     * Called once per request.
     */
    public function resolveValue(Request $request): mixed;

    /**
     * Add constraints to the shared identity subquery.
     *
     * All dimensions and filters contribute to the same subquery, ensuring
     * criteria are evaluated against the SAME variant row. Use
     * IdentityQueryRestrictor::VARIANT_ALIAS for the variant alias.
     *
     * @param QueryBuilder $queryBuilder    Main query builder (for parameters and additional clauses)
     * @param QueryBuilder $subQueryBuilder Shared subquery builder (for WHERE constraints on the variant)
     * @param string       $identityAlias   Alias of the Identity entity in the main query
     *
     * @return bool True if constraints were added to the subquery, false to skip
     */
    public function applyToIdentityQuery(
        QueryBuilder $queryBuilder,
        QueryBuilder $subQueryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        Identity $identityAttribute,
        FilterMetadata $filterMetadata,
        mixed $resolvedValue,
        string $identityAlias,
    ): bool;

    /**
     * Called once per Variant hydration.
     *
     * @todo Work out how to pass in QueryNameGeneratorInterface too
     */
    public function applyToVariant(QueryBuilder $queryBuilder, FilterMetadata $filterMetadata, mixed $resolvedValue): void;
}
