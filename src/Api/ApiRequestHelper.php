<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api;

use ApiPlatform\Metadata\Util\AttributesExtractor;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helper for API Platform request handling.
 */
final readonly class ApiRequestHelper
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Detects if this is a "sub-fetch" - i.e., fetching an entity as a reference
     * during deserialization of another resource (IRI resolution).
     *
     * When the main request is POST /expert-variants and we're querying Expert,
     * this is a sub-fetch and we should skip filtering.
     *
     * @param string $resourceClass The resource class being queried/provided
     */
    public function isSubFetch(string $resourceClass): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $attributes = AttributesExtractor::extractAttributes($request->attributes->all());
        $requestResourceClass = $attributes['resource_class'] ?? null;

        // If the resource being queried differs from the main request's resource,
        // this is a sub-fetch (e.g., resolving an IRI during deserialization)
        return null !== $requestResourceClass && $requestResourceClass !== $resourceClass;
    }
}
