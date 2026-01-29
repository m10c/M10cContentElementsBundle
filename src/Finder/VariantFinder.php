<?php

declare(strict_types=1);

namespace M10c\ContentElements\Finder;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use M10c\ContentElements\Context\ContextResolver;
use M10c\ContentElements\Metadata\DimensionMetadata;
use M10c\ContentElements\Metadata\FilterMetadata;
use M10c\ContentElements\Metadata\MetadataRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class VariantFinder
{
    public function __construct(
        private readonly ContextResolver $contextResolver,
        #[AutowireIterator('m10c.content_elements.dimension')]
        private readonly iterable $dimensions,
        private readonly EntityManagerInterface $em,
        #[AutowireIterator('m10c.content_elements.filter')]
        private readonly iterable $filters,
        private readonly MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * @param array<string, mixed> $extraDimensionContext Extra context keyed by dimension key, merged into resolved values
     */
    public function findOne(object $identity, array $extraDimensionContext = []): ?object
    {
        $identityClass = ClassUtils::getClass($identity);
        $identityAttribute = $this->metadataRegistry->getIdentityMetadata($identityClass);
        if (!$identityAttribute) {
            throw new \Exception("Class {$identityClass} doesn't have an Identity attribute");
        }
        $variantMetadata = $this->metadataRegistry->getVariantMetadata($identityAttribute->variantClass);

        $context = $this->contextResolver->resolve();

        $qb = $this->em
            ->createQueryBuilder()
            ->select('v')
            ->from($identityAttribute->variantClass, 'v')
            ->where("v.{$identityAttribute->identityProperty} = :identity")
            ->setParameter('identity', $identity)
            ->setMaxResults(1);

        foreach ($variantMetadata as $metadataItem) {
            if ($metadataItem instanceof DimensionMetadata) {
                foreach ($this->dimensions as $dimension) {
                    if ($metadataItem->attribute::class === $dimension->getAttributeClass()) {
                        $resolvedValues = $context->dimensionResolvedValues[$dimension->getKey()];
                        $extra = $extraDimensionContext[$dimension->getKey()] ?? [];
                        $dimension->applyToVariant($qb, $metadataItem, $resolvedValues, $extra);
                    }
                }
            }

            if ($metadataItem instanceof FilterMetadata) {
                foreach ($this->filters as $filter) {
                    if ($metadataItem->attribute::class === $filter->getAttributeClass()) {
                        $resolvedValues = $context->filterResolvedValues[$filter->getKey()];
                        $filter->applyToVariant($qb, $metadataItem, $resolvedValues);
                    }
                }
            }
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
