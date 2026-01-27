<?php

declare(strict_types=1);

namespace M10c\ContentElements\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Finder\IdentityQueryRestrictor;
use M10c\ContentElements\Metadata\FilterMetadata;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\Request;

class Publishable implements FilterInterface
{
    public const KEY = 'publishable';

    #[\Override]
    public function getKey(): string
    {
        return self::KEY;
    }

    #[\Override]
    public function getAttributeClass(): string
    {
        return \M10c\ContentElements\Attribute\Filter\Publishable::class;
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
    ): bool {
        if (false === $resolvedValue) {
            // No filtering requested (e.g. admin operation)
            return false;
        }

        $variantAlias = IdentityQueryRestrictor::VARIANT_ALIAS;
        $paramName = $queryNameGenerator->generateParameterName('publishable_now');

        $subQueryBuilder
            ->andWhere("{$variantAlias}.{$filterMetadata->property} IS NOT NULL")
            ->andWhere("{$variantAlias}.{$filterMetadata->property} <= :{$paramName}");
        $queryBuilder->setParameter($paramName, new DatePoint());

        return true;
    }

    #[\Override]
    public function applyToVariant(QueryBuilder $queryBuilder, FilterMetadata $filterMetadata, mixed $resolvedValue): void
    {
        if (false === $resolvedValue) {
            // No filtering requested (e.g. admin operation)
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere("{$rootAlias}.{$filterMetadata->property} IS NOT NULL")
            ->andWhere("{$rootAlias}.{$filterMetadata->property} <= :now")
            ->setParameter('now', new DatePoint());
    }
}
