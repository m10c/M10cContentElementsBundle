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
        string $identityAlias,
    ): bool {
        if (null === $resolvedValue) {
            // Intentionally ignoring variants, e.g. for admin endpoints
            return false;
        }
        Assert::allString($resolvedValue);

        $variantAlias = IdentityQueryRestrictor::VARIANT_ALIAS;

        $positiveLocales = [];
        $negativeLocale = null;

        foreach ($resolvedValue as $locale) {
            if (str_starts_with($locale, '!')) {
                $negativeLocale = substr($locale, 1);
            } else {
                $positiveLocales[] = $locale;
            }
        }

        // Validate: negative can only be combined with its equivalent positive (e.g. en,!en)
        if (null !== $negativeLocale && [] !== $positiveLocales) {
            if (1 !== \count($positiveLocales) || $positiveLocales[0] !== $negativeLocale) {
                throw new \InvalidArgumentException(
                    'Negative locale can only be combined with its equivalent positive locale (e.g. "en,!en"). '
                    .'Mixed combinations like "en,!de" are not supported.'
                );
            }
            // en,!en means "all identities" - no filtering needed at identity level
            return false;
        }

        // Positive only: include identities with a variant matching one of these locales
        if ([] !== $positiveLocales) {
            $paramName = $queryNameGenerator->generateParameterName('locale');
            $subQueryBuilder->andWhere(
                $subQueryBuilder->expr()->in("{$variantAlias}.{$dimensionMetadata->property}", ":{$paramName}")
            );
            $queryBuilder->setParameter($paramName, $positiveLocales);

            return true;
        }

        // Negative only: exclude identities that have ANY variant with this locale
        if (null !== $negativeLocale) {
            $paramName = $queryNameGenerator->generateParameterName('locale_neg');
            $negSubQb = $queryBuilder->getEntityManager()->createQueryBuilder();
            $negSubQb->select('1')
                ->from($identity->variantClass, 'v_neg')
                ->where("v_neg.{$identity->identityProperty} = {$identityAlias}")
                ->andWhere("v_neg.{$dimensionMetadata->property} = :{$paramName}");
            $queryBuilder->setParameter($paramName, $negativeLocale);
            $queryBuilder->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($negSubQb->getDQL())));

            // Return false because we didn't add to the shared subquery
            return false;
        }

        return false;
    }

    #[\Override]
    public function applyToVariant(QueryBuilder $queryBuilder, DimensionMetadata $dimensionMetadata, mixed $resolvedValue, array $extraContext = []): void
    {
        if (isset($extraContext['value'])) {
            $resolvedValue = $extraContext['value'];
        }
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
