<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Finder;

use M10c\ContentElements\Dimension\Locale;
use M10c\ContentElements\Finder\VariantFinder;
use M10c\ContentElements\Tests\Fixtures\Entity\Article;
use M10c\ContentElements\Tests\Fixtures\Entity\ArticleVariant;
use M10c\ContentElements\Tests\Functional\ContentElementsTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class VariantFinderTest extends ContentElementsTestCase
{
    use ClockSensitiveTrait;

    /**
     * One query must still resolve each identity separately: its own locale winner,
     * and nothing at all when no variant of it is published.
     */
    public function testFindOneForEach(): void
    {
        static::mockTime('2026-01-01 00:00:00');
        $this->pushRequest();

        $bilingual = $this->createArticle('article-bilingual', ['en' => '2025-01-01', 'es' => '2025-01-01']);
        $englishOnly = $this->createArticle('article-english-only', ['en' => '2025-01-01']);
        $unpublished = $this->createArticle('article-unpublished', ['en' => null]);
        $notYetPublished = $this->createArticle('article-not-yet-published', ['en' => '2027-01-01']);

        $finder = static::getContainer()->get(VariantFinder::class);

        // Spanish preferred, English as the fallback. The bilingual article is passed twice on
        // purpose: consumers flatten shared relations, so the same identity arrives more than once.
        $variants = $finder->findOneForEach(
            [$bilingual, $englishOnly, $unpublished, $notYetPublished, $bilingual],
            [Locale::KEY => ['value' => ['es', 'en']]],
        );

        $this->assertSame('es', $variants[0]->locale);
        $this->assertSame('en', $variants[1]->locale);
        $this->assertArrayNotHasKey(2, $variants);
        $this->assertArrayNotHasKey(3, $variants);
        $this->assertSame('es', $variants[4]->locale);

        // The single-identity branch is not exercised above
        $this->assertSame('es', $finder->findOne($bilingual, [Locale::KEY => ['value' => ['es', 'en']]])?->locale);
        $this->assertNull($finder->findOne($unpublished));

        // Nothing to look up, nothing queried
        $this->assertSame([], $finder->findOneForEach([]));
    }

    /**
     * Create an article with one variant per locale, unpublished where publishAt is null.
     *
     * @param array<string, string|null> $publishAtByLocale
     */
    private function createArticle(string $slug, array $publishAtByLocale): Article
    {
        $em = $this->getEm();

        $article = new Article();
        $article->slug = $slug;
        $em->persist($article);

        foreach ($publishAtByLocale as $locale => $publishAt) {
            $variant = new ArticleVariant();
            $variant->identity = $article;
            $variant->locale = $locale;
            $variant->title = "{$slug} {$locale}";
            $variant->body = 'body';
            $variant->publishAt = null === $publishAt ? null : new \DateTimeImmutable($publishAt);
            $em->persist($variant);
        }

        $em->flush();

        return $article;
    }
}
