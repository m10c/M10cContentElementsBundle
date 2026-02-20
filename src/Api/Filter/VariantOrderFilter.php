<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use M10c\ContentElements\Context\ContextResolver;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class VariantOrderFilter implements FilterInterface
{
    /**
     * @param iterable<VariantOrderSelectorInterface> $variantSelectors Tagged services implementing VariantOrderSelectorInterface
     * @param array<string, string>|null              $properties       Map of query param name => variant field name
     */
    public function __construct(
        private readonly ContextResolver $contextResolver,
        #[AutowireIterator('m10c.content_elements.variant_order_selector')]
        private readonly iterable $variantSelectors,
        private ?array $properties = null,
    ) {
    }

    public function apply(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $orderParams = $context['filters']['order'] ?? [];

        if (!\is_array($orderParams)) {
            return;
        }

        foreach ($orderParams as $property => $direction) {
            if (!isset($this->properties[$property])) {
                continue;
            }

            $variantField = $this->properties[$property];
            $direction = strtoupper((string) $direction) === 'DESC' ? 'DESC' : 'ASC';
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $joinAlias = $queryNameGenerator->generateJoinAlias('variant_order');

            $resolvedContext = $this->contextResolver->resolve();

            // Collect JOIN conditions from all dimension selectors
            $conditions = [];
            foreach ($this->variantSelectors as $selector) {
                $dimensionKey = $this->getDimensionKey($selector);
                $resolvedValue = $resolvedContext->dimensionResolvedValues[$dimensionKey] ?? null;

                if ($resolvedValue === null) {
                    continue;
                }

                $condition = $selector->getVariantOrderJoinCondition(
                    $queryBuilder,
                    $queryNameGenerator,
                    $joinAlias,
                    $resolvedValue,
                );

                if ($condition === null) {
                    // Dimension is in "all" mode — variant sorting not applicable
                    continue 2; // Skip this sort property entirely
                }

                $conditions[] = $condition;
            }

            if (empty($conditions)) {
                continue;
            }

            // Build LEFT JOIN with combined conditions from all dimensions
            $withClause = implode(' AND ', $conditions);
            $queryBuilder
                ->leftJoin("{$rootAlias}.variants", $joinAlias, 'WITH', $withClause)
                ->addOrderBy("{$joinAlias}.{$variantField}", $direction);
        }
    }

    private function getDimensionKey(VariantOrderSelectorInterface $selector): string
    {
        if (\defined($selector::class . '::KEY')) {
            return constant($selector::class . '::KEY');
        }

        throw new \LogicException(sprintf(
            'VariantOrderSelector "%s" must define a KEY constant.',
            $selector::class,
        ));
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach (array_keys($this->properties ?? []) as $property) {
            $description["order[{$property}]"] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'schema' => ['type' => 'string', 'enum' => ['asc', 'desc']],
            ];
        }

        return $description;
    }
}
