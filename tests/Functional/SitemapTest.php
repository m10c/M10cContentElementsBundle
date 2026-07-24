<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Functional;

final class SitemapTest extends ContentElementsTestCase
{
    public function testSitemapXml(): void
    {
        $this->seedPage('home', 'Home', 'Home description', []);
        $this->seedPage('about-us', 'About us', 'About us description', []);

        $response = self::request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();
        $doc = new \DOMDocument();
        self::assertTrue($doc->loadXML($xml), 'Sitemap response is well-formed XML.');

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $locs = [];
        foreach ($xpath->query('//s:url/s:loc') ?: [] as $node) {
            $locs[] = $node->textContent;
        }
        sort($locs);
        self::assertSame(
            ['https://example.test/about-us', 'https://example.test/home'],
            $locs,
        );

        // Optional fields render when set.
        self::assertSame(2, $xpath->query('//s:url/s:changefreq[. = "weekly"]')?->length);
        self::assertSame(2, $xpath->query('//s:url/s:priority[. = "0.8"]')?->length);
    }
}
