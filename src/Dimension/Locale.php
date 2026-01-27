<?php

declare(strict_types=1);

namespace M10c\ContentElements\Dimension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Finder\IdentityQueryRestrictor;
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
    public function applyToIdentityQuery(
        QueryBuilder $queryBuilder,
        QueryBuilder $subQueryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        Identity $identity,
        DimensionMetadata $dimensionMetadata,
        mixed $resolvedValue,
    ): bool {
        if (null === $resolvedValue) {
            // Intentionally ignoring variants, e.g. for admin endpoints
            return false;
        }
        Assert::allString($resolvedValue);

        $variantAlias = IdentityQueryRestrictor::VARIANT_ALIAS;
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $positiveLocales = [];
        $negativeLocales = [];

        foreach ($resolvedValue as $locale) {
            if (str_starts_with($locale, '!')) {
                $negativeLocales[] = substr($locale, 1);
            } else {
                $positiveLocales[] = $locale;
            }
        }

        // Positive locales: add to the shared subquery (v.locale IN ('en', 'fr'))
        if ([] !== $positiveLocales) {
            $paramName = $queryNameGenerator->generateParameterName('locale');
            $subQueryBuilder->andWhere(
                $subQueryBuilder->expr()->in("{$variantAlias}.{$dimensionMetadata->property}", ":{$paramName}")
            );
            $queryBuilder->setParameter($paramName, $positiveLocales);
        }

        // Negative locales: separate NOT EXISTS per locale (excludes Identity entirely if ANY variant has that locale)
        foreach ($negativeLocales as $locale) {
            $paramName = $queryNameGenerator->generateParameterName('locale_neg');
            $negSubQb = $queryBuilder->getEntityManager()->createQueryBuilder();
            $negSubQb->select('1')
                ->from($identity->variantClass, 'v_neg')
                ->where("v_neg.{$identity->identityProperty} = {$rootAlias}")
                ->andWhere("v_neg.{$dimensionMetadata->property} = :{$paramName}");
            $queryBuilder->setParameter($paramName, $locale);
            $queryBuilder->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($negSubQb->getDQL())));
        }

        return [] !== $positiveLocales;
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
