<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Api\ApiRequestHelper;
use M10c\ContentElements\Attribute\Dimension\Locale as AttributeLocale;
use M10c\ContentElements\Context\ContextResolver;
use M10c\ContentElements\Dimension\Locale;
use M10c\ContentElements\Metadata\MetadataRegistry;

final class LocaleExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly ApiRequestHelper $apiRequestHelper,
        private readonly ContextResolver $contextResolver,
        private readonly Locale $locale,
        private readonly MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * @param mixed[] $context
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    private function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation): void
    {
        // Skip filtering for sub-fetches (e.g., IRI resolution during deserialization)
        // This happens when the resource being queried differs from the main request's resource
        if ($this->apiRequestHelper->isSubFetch($resourceClass)) {
            return;
        }

        $identityAttribute = $this->metadataRegistry->getIdentityMetadata($resourceClass);
        if (!$identityAttribute) {
            return;
        }
        $localeMetadata = $this->metadataRegistry->getVariantDimensionMetadata($identityAttribute->variantClass, AttributeLocale::class);
        if (!$localeMetadata) {
            return;
        }

        $context = $this->contextResolver->resolve();
        $resolvedValue = $context->dimensionResolvedValues[Locale::KEY];
        $this->locale->applyToIdentity($queryBuilder, $queryNameGenerator, $identityAttribute, $localeMetadata, $resolvedValue);
    }
}
