<?php

declare(strict_types=1);

namespace M10c\ContentElements\Dimension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Metadata\DimensionMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * A Dimension is a field on a Variant, where you want multiple instances of the
 * variant for each value.
 */
interface DimensionInterface
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
    public function applyToIdentity(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, Identity $identityAttribute, DimensionMetadata $dimensionMetadata, mixed $resolvedValue): void;

    /**
     * Called once per Variant hydration.
     *
     * @todo Work out how to pass in QueryNameGeneratorInterface too
     */
    public function applyToVariant(QueryBuilder $queryBuilder, DimensionMetadata $dimensionMetadata, mixed $resolvedValue): void;
}
