<?php

declare(strict_types=1);

namespace M10c\ContentElements\Context;

use M10c\ContentElements\Dimension\DimensionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\RequestStack;

final class ContextResolver
{
    private ?Context $context = null;

    /**
     * @param iterable<DimensionInterface> $dimensions
     */
    public function __construct(
        #[AutowireIterator('m10c.content_elements.dimension')]
        private readonly iterable $dimensions,
        #[AutowireIterator('m10c.content_elements.filter')]
        private readonly iterable $filters,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function resolve(): Context
    {
        if (null !== $this->context) {
            return $this->context;
        }

        $request = $this->requestStack->getCurrentRequest();

        $dimensionResolvedValues = [];
        foreach ($this->dimensions as $dimension) {
            $dimensionResolvedValues[$dimension->getKey()] = $dimension->resolveValue($request);
        }

        $filterResolvedValues = [];
        foreach ($this->filters as $filter) {
            $filterResolvedValues[$filter->getKey()] = $filter->resolveValue($request);
        }

        return $this->context = new Context(
            dimensionResolvedValues: $dimensionResolvedValues,
            filterResolvedValues: $filterResolvedValues,
        );
    }
}
