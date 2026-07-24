<?php

declare(strict_types=1);

namespace M10c\ContentElements\Sitemap;

use DOMDocument;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class SitemapController
{
    public function __construct(
        private readonly SitemapGenerator $generator,
    ) {
    }

    public function __invoke(): Response
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $urlset = $doc->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $doc->appendChild($urlset);

        foreach ($this->generator->generate() as $entry) {
            $url = $doc->createElement('url');

            $loc = $doc->createElement('loc');
            $loc->appendChild($doc->createTextNode($entry->loc));
            $url->appendChild($loc);

            if (null !== $entry->lastmod) {
                $lastmod = $doc->createElement('lastmod');
                $lastmod->appendChild($doc->createTextNode($entry->lastmod->format('Y-m-d')));
                $url->appendChild($lastmod);
            }

            if (null !== $entry->changefreq) {
                $changefreq = $doc->createElement('changefreq');
                $changefreq->appendChild($doc->createTextNode($entry->changefreq->value));
                $url->appendChild($changefreq);
            }

            if (null !== $entry->priority) {
                $priority = $doc->createElement('priority');
                $priority->appendChild($doc->createTextNode(number_format($entry->priority, 1)));
                $url->appendChild($priority);
            }

            $urlset->appendChild($url);
        }

        $xml = $doc->saveXML();

        return new Response(
            false === $xml ? '' : $xml,
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }
}
