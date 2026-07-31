<?php

declare(strict_types=1);

namespace M10c\ContentElements\Finder;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use M10c\ContentElements\Context\ContextResolver;
use M10c\ContentElements\Metadata\DimensionMetadata;
use M10c\ContentElements\Metadata\FilterMetadata;
use M10c\ContentElements\Metadata\MetadataRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Webmozart\Assert\Assert;

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
        return $this->findOneForEach([$identity], $extraDimensionContext)[0] ?? null;
    }

    /**
     * Resolves one variant per identity in a single query.
     *
     * @template TKey of array-key
     *
     * @param array<TKey, object>  $identities            All identities must be of the same class
     * @param array<string, mixed> $extraDimensionContext Extra context keyed by dimension key, merged into resolved values
     *
     * @return array<TKey, object> Variants keyed by their identity's key in $identities,
     *                             absent for identities with no matching variant
     *
     * @throws \Exception If the identities' class has no Identity attribute
     */
    public function findOneForEach(array $identities, array $extraDimensionContext = []): array
    {
        if ([] === $identities) {
            return [];
        }

        $identityClass = ClassUtils::getClass(reset($identities));
        Assert::classExists($identityClass);

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
            ->where("v.{$identityAttribute->identityProperty} IN (:identities)")
            ->setParameter('identities', $identities);

        // With one identity the first ordered row is already the answer
        if (1 === \count($identities)) {
            $qb->setMaxResults(1);
        }

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

        $identityMetadata = $this->em->getClassMetadata($identityClass);

        // The same identity can appear twice, e.g. a tag shared by two contents
        $keysByIdentityId = [];
        foreach ($identities as $key => $identity) {
            $keysByIdentityId[$this->identityId($identityMetadata, $identity)][] = $key;
        }

        $candidates = $qb->getQuery()->getResult();
        Assert::isIterable($candidates);

        $variants = [];
        foreach ($candidates as $variant) {
            Assert::object($variant);
            $identity = $variant->{$identityAttribute->identityProperty};
            Assert::object($identity);

            foreach ($keysByIdentityId[$this->identityId($identityMetadata, $identity)] as $key) {
                // Dimensions rank variants on their own columns only, so an identity's first row is its winner
                $variants[$key] ??= $variant;
            }
        }

        return $variants;
    }

    /**
     * @param ClassMetadata<object> $identityMetadata
     */
    private function identityId(ClassMetadata $identityMetadata, object $identity): string
    {
        $id = $identityMetadata->getIdentifierValues($identity)[$identityMetadata->getSingleIdentifierFieldName()];
        Assert::scalar($id);

        return (string) $id;
    }
}
