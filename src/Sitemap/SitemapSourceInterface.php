<?php

declare(strict_types=1);

namespace M10c\ContentElements\Sitemap;

interface SitemapSourceInterface
{
    /**
     * @return iterable<SitemapEntry>
     */
    public function getSitemapEntries(): iterable;
}
