<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Fixtures\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use M10c\ContentElements\Sitemap\SitemapChangeFreq;
use M10c\ContentElements\Sitemap\SitemapEntry;
use M10c\ContentElements\Sitemap\SitemapSourceInterface;
use M10c\ContentElements\Tests\Fixtures\Entity\Page;

final class PageSitemapSource implements SitemapSourceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getSitemapEntries(): iterable
    {
        foreach ($this->em->getRepository(Page::class)->findAll() as $page) {
            yield new SitemapEntry(
                loc: "https://example.test/{$page->slug}",
                changefreq: SitemapChangeFreq::Weekly,
                priority: 0.8,
            );
        }
    }
}
