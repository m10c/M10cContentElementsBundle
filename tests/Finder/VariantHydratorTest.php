<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Finder;

use M10c\ContentElements\Dimension\Locale;
use M10c\ContentElements\Finder\VariantHydrator;
use M10c\ContentElements\Tests\Fixtures\Entity\Article;
use M10c\ContentElements\Tests\Fixtures\Entity\ArticleVariant;
use M10c\ContentElements\Tests\Functional\ContentElementsTestCase;

final class VariantHydratorTest extends ContentElementsTestCase
{
    public function testHydrateAll(): void
    {
        // Hydrating a list costs one query per identity class, however many identities it holds
        $this->pushRequest();

        $page = $this->seedPage('page-one', 'Page One', 'A page', [])->identity;
        $articles = [
            $this->createArticle('article-one', 'en', '2025-01-01'),
            $this->createArticle('article-two', 'en', '2025-01-01'),
            $this->createArticle('article-three', 'en', '2025-01-01'),
        ];

        $hydrator = static::getContainer()->get(VariantHydrator::class);

        // Three articles, one query
        $this->resetQueryLog();
        $hydrator->hydrateAll($articles);

        $this->assertSame(1, $this->countQueriesFor('test_article_variant'));
        $this->assertSame('article-one en', $articles[0]->variant?->title);
        $this->assertSame('article-two en', $articles[1]->variant?->title);
        $this->assertSame('article-three en', $articles[2]->variant?->title);

        // Two classes in one call, one query each
        $this->resetQueryLog();
        $hydrator->hydrateAll([$articles[0], $page]);

        $this->assertSame(1, $this->countQueriesFor('test_article_variant'));
        $this->assertSame(1, $this->countQueriesFor('test_page_variant'));
        $this->assertSame('Page One', $page->variant?->seoTitle);
    }

    public function testMissingVariant(): void
    {
        // An identity with no published variant is skipped by the try methods and fatal to hydrateAll
        $this->pushRequest();

        $published = $this->createArticle('article-published', 'en', '2025-01-01');
        $spanish = $this->createArticle('article-spanish', 'es', '2025-01-01');
        $unpublished = $this->createArticle('article-unpublished', 'en', null);

        $hydrator = static::getContainer()->get(VariantHydrator::class);

        $hydrator->tryHydrateAll([$published, $unpublished]);
        $this->assertSame('article-published en', $published->variant?->title);
        $this->assertNull($unpublished->variant);

        // Dropped identities aside, the caller's order survives the query's own ordering
        $this->assertSame(
            [$published, $spanish],
            $hydrator->tryHydrateAllFiltered(
                [$published, $unpublished, $spanish],
                [Locale::KEY => ['value' => ['es', 'en']]],
            ),
        );

        $this->expectExceptionMessage(Article::class." {$unpublished->id} found no variant");
        $hydrator->hydrateAll([$published, $unpublished]);
    }

    private function createArticle(string $slug, string $locale, ?string $publishAt): Article
    {
        $em = $this->getEm();

        $article = new Article();
        $article->slug = $slug;
        $em->persist($article);

        $variant = new ArticleVariant();
        $variant->identity = $article;
        $variant->locale = $locale;
        $variant->title = "{$slug} {$locale}";
        $variant->body = 'body';
        $variant->publishAt = null === $publishAt ? null : new \DateTimeImmutable($publishAt);
        $em->persist($variant);

        $em->flush();

        return $article;
    }
}
