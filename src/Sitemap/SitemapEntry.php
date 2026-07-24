<?php

declare(strict_types=1);

namespace M10c\ContentElements\Sitemap;

final class SitemapEntry
{
    public function __construct(
        public string $loc,
        public ?\DateTimeInterface $lastmod = null,
        public ?SitemapChangeFreq $changefreq = null,
        /** 0.0 to 1.0 per the sitemaps.org protocol; out-of-range values are clamped. */
        public ?float $priority = null {
            set (?float $value) => null === $value ? null : max(0.0, min(1.0, $value));
        },
    ) {
    }
}
