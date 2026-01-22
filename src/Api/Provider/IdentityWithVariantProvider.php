<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Provider;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use M10c\ContentElements\Api\ApiRequestHelper;
use M10c\ContentElements\Finder\VariantHydratorInterface;

/**
 * @implements ProviderInterface<object>
 */
class IdentityWithVariantProvider implements ProviderInterface
{
    public function __construct(
        private readonly ApiRequestHelper $apiRequestHelper,
        private readonly CollectionProvider $collectionProvider,
        private readonly ItemProvider $itemProvider,
        private readonly VariantHydratorInterface $variantHydrator,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     * @param mixed[]                 $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $provided = $this->collectionProvider->provide($operation, $uriVariables, $context);

            // Skip variant hydration for sub-fetches (IRI resolution)
            if ($this->apiRequestHelper->isSubFetch($operation->getClass())) {
                return $provided;
            }

            foreach ($provided as $item) {
                $this->variantHydrator->hydrate($item);
            }
        } else {
            $provided = $this->itemProvider->provide($operation, $uriVariables, $context);

            // Skip variant hydration for sub-fetches (IRI resolution)
            if ($provided && !$this->apiRequestHelper->isSubFetch($operation->getClass())) {
                $this->variantHydrator->hydrate($provided);
            }
        }

        return $provided;
    }
}
