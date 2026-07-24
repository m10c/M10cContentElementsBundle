<?php

declare(strict_types=1);

namespace M10c\ContentElements\Sitemap;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class SitemapGenerator
{
    /**
     * @param iterable<SitemapSourceInterface> $sources
     */
    public function __construct(
        #[AutowireIterator('m10c.content_elements.sitemap_source')]
        private readonly iterable $sources,
    ) {
    }

    /**
     * @return iterable<SitemapEntry>
     */
    public function generate(): iterable
    {
        foreach ($this->sources as $source) {
            yield from $source->getSitemapEntries();
        }
    }
}
