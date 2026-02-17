<?php

declare(strict_types=1);

namespace M10c\ContentElements\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Metadata\FilterMetadata;
use Symfony\Component\HttpFoundation\Request;

class Archivable implements FilterInterface
{
    #[\Override]
    public function getKey(): string
    {
        return 'archivable';
    }

    #[\Override]
    public function getAttributeClass(): string
    {
        return \M10c\ContentElements\Attribute\Filter\Archivable::class;
    }

    #[\Override]
    public function resolveValue(Request $request): mixed
    {
        return true;
    }

    #[\Override]
    public function applyToIdentityQuery(
        QueryBuilder $queryBuilder,
        QueryBuilder $subQueryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        Identity $identityAttribute,
        FilterMetadata $filterMetadata,
        mixed $resolvedValue,
        string $identityAlias,
    ): bool {
        throw new \Exception('TODO');
    }

    #[\Override]
    public function applyToVariant(QueryBuilder $queryBuilder, FilterMetadata $filterMetadata, mixed $resolvedValue): void
    {
        if (false === $resolvedValue) {
            // No filtering requested (e.g. admin operation)
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere("{$rootAlias}.archivedAt IS NOT NULL");
    }
}
