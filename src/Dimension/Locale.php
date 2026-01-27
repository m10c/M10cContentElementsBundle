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

        // Build OR conditions for the locale fallback chain within the shared subquery
        // e.g. ['en', '!de'] becomes: v.locale = 'en' OR v.locale != 'de'
        // This ensures we find Identities that have at least one variant matching the fallback chain
        $orX = $subQueryBuilder->expr()->orX();

        foreach ($resolvedValue as $locale) {
            if (str_starts_with($locale, '!')) {
                // Negative locale: fallback to any variant NOT matching this locale
                $value = substr($locale, 1);
                $paramName = $queryNameGenerator->generateParameterName('locale_neg');
                $orX->add(
                    $subQueryBuilder->expr()->neq("{$variantAlias}.{$dimensionMetadata->property}", ":{$paramName}")
                );
            } else {
                // Positive locale: prefer variant matching this locale
                $value = $locale;
                $paramName = $queryNameGenerator->generateParameterName('locale');
                $orX->add(
                    $subQueryBuilder->expr()->eq("{$variantAlias}.{$dimensionMetadata->property}", ":{$paramName}")
                );
            }
            $queryBuilder->setParameter($paramName, $value);
        }

        if ($orX->count() > 0) {
            $subQueryBuilder->andWhere($orX);
            return true;
        }

        return false;
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
