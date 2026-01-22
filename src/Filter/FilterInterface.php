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
     * Called once per Identity query.
     */
    public function applyToIdentity(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, Identity $identityAttribute, FilterMetadata $filterMetadata, mixed $resolvedValue): void;

    /**
     * Called once per Variant hydration.
     *
     * @todo Work out how to pass in QueryNameGeneratorInterface too
     */
    public function applyToVariant(QueryBuilder $queryBuilder, FilterMetadata $filterMetadata, mixed $resolvedValue): void;
}
