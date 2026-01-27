<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Api\ApiRequestHelper;
use M10c\ContentElements\Finder\IdentityQueryRestrictor;
use M10c\ContentElements\Metadata\MetadataRegistry;

/**
 * Unified API Platform extension that applies all dimensions and filters
 * to Identity queries using a single EXISTS subquery to ensure all criteria
 * are evaluated against the SAME variant row.
 */
final class IdentityQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly ApiRequestHelper $apiRequestHelper,
        private readonly IdentityQueryRestrictor $identityQueryRestrictor,
        private readonly MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * @param mixed[] $context
     */
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->apply($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    /**
     * @param mixed[] $identifiers
     * @param mixed[] $context
     */
    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->apply($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    /**
     * @param class-string $resourceClass
     */
    private function apply(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
    ): void {
        // Skip filtering for sub-fetches (e.g., IRI resolution during deserialization)
        if ($this->apiRequestHelper->isSubFetch($resourceClass)) {
            return;
        }

        $identityAttribute = $this->metadataRegistry->getIdentityMetadata($resourceClass);
        if (!$identityAttribute) {
            return;
        }

        $this->identityQueryRestrictor->apply($queryBuilder, $resourceClass, $queryNameGenerator);
    }
}
