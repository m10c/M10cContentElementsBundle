<?php

declare(strict_types=1);

namespace M10c\ContentElements\Sitemap;

/**
 * Allowed `<changefreq>` values, per the sitemaps.org protocol.
 *
 * @see https://www.sitemaps.org/protocol.html#changefreqdef
 */
enum SitemapChangeFreq: string
{
    case Always = 'always';
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Never = 'never';
}
