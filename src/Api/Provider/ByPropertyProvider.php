<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use M10c\ContentElements\Api\ApiRequestHelper;
use M10c\ContentElements\Finder\VariantHydratorInterface;
use M10c\ContentElements\Metadata\MetadataRegistry;

/**
 * Looks up an entity by a single URI variable that maps 1:1 to an entity property
 * of the same name — `{slug}` → `slug` column, `{path}` → `path` column, etc.
 *
 * If the class is an Identity (uses the Identity/Variant pattern), its variant
 * is also hydrated.
 *
 * @implements ProviderInterface<object>
 */
final class ByPropertyProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly VariantHydratorInterface $variantHydrator,
        private readonly MetadataRegistry $metadataRegistry,
        private readonly ApiRequestHelper $apiRequestHelper,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     * @param mixed[]                 $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $property = array_key_first($uriVariables);
        $class = $operation->getClass();
        if (!is_string($property) || null === $class) {
            return null;
        }

        $value = $uriVariables[$property];
        if (!is_string($value)) {
            return null;
        }

        $entity = $this->em->getRepository($class)->findOneBy([$property => $value]);

        if (null === $entity) {
            return null;
        }

        if (
            !$this->apiRequestHelper->isSubFetch($class)
            && null !== $this->metadataRegistry->getIdentityMetadata($class)
        ) {
            $this->variantHydrator->hydrate($entity);
        }

        return $entity;
    }
}
