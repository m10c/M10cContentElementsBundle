<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

interface VariantOrderSelectorInterface
{
    /**
     * Return a DQL condition fragment for the variant LEFT JOIN's WITH clause,
     * and set any parameters on the QueryBuilder.
     *
     * Return null if this dimension is in "all" mode (variant sorting should be skipped).
     *
     * @param array<string|int, mixed> $resolvedValue The resolved dimension value (e.g. ['es'] or ['en', '!en'])
     * @param string                   $joinAlias     The alias for the variant entity in the JOIN
     */
    public function getVariantOrderJoinCondition(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $joinAlias,
        array $resolvedValue,
    ): ?string;
}
