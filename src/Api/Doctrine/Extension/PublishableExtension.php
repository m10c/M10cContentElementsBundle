<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Api\ApiRequestHelper;
use M10c\ContentElements\Attribute\Filter\Publishable as AttributePublishable;
use M10c\ContentElements\Context\ContextResolver;
use M10c\ContentElements\Filter\Publishable;
use M10c\ContentElements\Metadata\MetadataRegistry;

final class PublishableExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly ApiRequestHelper $apiRequestHelper,
        private readonly ContextResolver $contextResolver,
        private readonly Publishable $publishable,
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
        $publishableMetadata = $this->metadataRegistry->getVariantFilterMetadata($identityAttribute->variantClass, AttributePublishable::class);
        if (!$publishableMetadata) {
            return;
        }

        $context = $this->contextResolver->resolve();
        $resolvedValue = $context->filterResolvedValues[Publishable::KEY];
        $this->publishable->applyToIdentity($queryBuilder, $queryNameGenerator, $identityAttribute, $publishableMetadata, $resolvedValue);
    }
}
