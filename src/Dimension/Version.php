<?php

declare(strict_types=1);

namespace M10c\ContentElements\Dimension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Metadata\DimensionMetadata;
use Symfony\Component\HttpFoundation\Request;

class Version implements DimensionInterface
{
    #[\Override]
    public function getKey(): string
    {
        return 'version';
    }

    #[\Override]
    public function getAttributeClass(): string
    {
        return \M10c\ContentElements\Attribute\Dimension\Version::class;
    }

    #[\Override]
    public function resolveValue(Request $request): mixed
    {
        return null;
    }

    #[\Override]
    public function applyToIdentityQuery(
        QueryBuilder $queryBuilder,
        QueryBuilder $subQueryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        Identity $identityAttribute,
        DimensionMetadata $dimensionMetadata,
        mixed $resolvedValue,
        string $identityAlias,
    ): bool {
        throw new \Exception('TODO');
    }

    #[\Override]
    public function applyToVariant(QueryBuilder $queryBuilder, DimensionMetadata $dimensionMetadata, mixed $resolvedValue): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->addOrderBy("{$rootAlias}.version", 'DESC');
    }
}
