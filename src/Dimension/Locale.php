<?php

declare(strict_types=1);

namespace M10c\ContentElements\Dimension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Metadata\DimensionMetadata;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

class Locale implements DimensionInterface
{
    public const KEY = 'locale';

    #[\Override]
    public function getKey(): string
    {
        return self::KEY;
    }

    #[\Override]
    public function getAttributeClass(): string
    {
        return \M10c\ContentElements\Attribute\Dimension\Locale::class;
    }

    /**
     * @return string[] Fallback order
     */
    #[\Override]
    public function resolveValue(Request $request): ?array
    {
        return ['en'];
    }

    #[\Override]
    public function applyToIdentity(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, Identity $identity, DimensionMetadata $dimensionMetadata, mixed $resolvedValue, ?string $identityAlias = null): void
    {
        if (null === $resolvedValue) {
            // Intentionally ignoring variants, e.g. for admin endpoints
            return;
        }
        Assert::allString($resolvedValue);
        $identityAlias ??= $queryBuilder->getRootAliases()[0];

        $positiveLocales = [];
        $negativeLocales = [];

        foreach ($resolvedValue as $locale) {
            if (str_starts_with($locale, '!')) {
                $negativeLocales[] = substr($locale, 1);
            } else {
                $positiveLocales[] = $locale;
            }
        }

        $orX = $queryBuilder->expr()->orX();

        // Use subqueries to avoid polluting Doctrine's collection loading
        // (JOINs on variants would filter the Identity.variants property)
        if ([] !== $positiveLocales) {
            $subQb = $queryBuilder->getEntityManager()->createQueryBuilder();
            $subQb->select('1')
                ->from($identity->variantClass, 'v_pos')
                ->where("v_pos.{$identity->identityProperty} = {$identityAlias}")
                ->andWhere($subQb->expr()->in("v_pos.{$dimensionMetadata->property}", ':locale_positive'));
            $queryBuilder->setParameter('locale_positive', $positiveLocales);
            $orX->add($queryBuilder->expr()->exists($subQb->getDQL()));
        }

        foreach ($negativeLocales as $index => $locale) {
            $parameterName = "locale_negative_{$index}";
            $subQb = $queryBuilder->getEntityManager()->createQueryBuilder();
            $subQb->select('1')
                ->from($identity->variantClass, "v_neg_{$index}")
                ->where("v_neg_{$index}.{$identity->identityProperty} = {$identityAlias}")
                ->andWhere("v_neg_{$index}.{$dimensionMetadata->property} = :{$parameterName}");
            $queryBuilder->setParameter($parameterName, $locale);
            $orX->add($queryBuilder->expr()->not($queryBuilder->expr()->exists($subQb->getDQL())));
        }

        if ($orX->count() > 0) {
            $queryBuilder->andWhere($orX);
        }
    }

    #[\Override]
    public function applyToVariant(QueryBuilder $queryBuilder, DimensionMetadata $dimensionMetadata, mixed $resolvedValue): void
    {
        Assert::allString($resolvedValue);
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $orX = $queryBuilder->expr()->orX();
        $caseSql = 'CASE';

        foreach ($resolvedValue as $index => $locale) {
            if (str_starts_with($locale, '!')) {
                $value = substr($locale, 1);
                $parameterName = "locale_negative_{$index}";
                $condition = $queryBuilder->expr()->neq("{$rootAlias}.{$dimensionMetadata->property}", ":{$parameterName}");
            } else {
                $value = $locale;
                $parameterName = "locale_positive_{$index}";
                $condition = $queryBuilder->expr()->eq("{$rootAlias}.{$dimensionMetadata->property}", ":{$parameterName}");
            }

            $queryBuilder->setParameter($parameterName, $value);
            $orX->add($condition);
            $caseSql .= " WHEN {$condition} THEN {$index}";
        }

        $caseSql .= ' ELSE '.count($resolvedValue).' END';

        if ($orX->count() > 0) {
            $queryBuilder->andWhere($orX)
                ->addOrderBy($caseSql, 'ASC');
        }
    }
}
