<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Functional;

use M10c\ContentElements\Tests\Fixtures\Entity\Article;
use M10c\ContentElements\Tests\Fixtures\Entity\ArticleVariant;

class VariantOrderFilterTest extends ContentElementsTestCase
{
    public function testOrderByVariantPublishAtDesc(): void
    {
        $this->createPublishedArticles();

        $response = static::request('GET', '/articles?order[variant.publishAt]=desc');
        $this->assertResponseIsSuccessful();

        $ids = array_map(
            static fn (array $item) => $item['slug'],
            $response->toArray()['member'],
        );

        // newest publishAt first
        $this->assertSame(['article-newer', 'article-older'], $ids);
    }

    public function testOrderByVariantPublishAtAsc(): void
    {
        $this->createPublishedArticles();

        $response = static::request('GET', '/articles?order[variant.publishAt]=asc');
        $this->assertResponseIsSuccessful();

        $ids = array_map(
            static fn (array $item) => $item['slug'],
            $response->toArray()['member'],
        );

        // oldest publishAt first
        $this->assertSame(['article-older', 'article-newer'], $ids);
    }

    public function testOrderByVariantPublishAtWithUnpublishedArticle(): void
    {
        $this->createPublishedArticles();

        // Add a third article with a published variant that has the newest date
        $em = $this->getEm();
        $article = new Article();
        $article->slug = 'article-newest';
        $em->persist($article);

        $variant = new ArticleVariant();
        $variant->identity = $article;
        $variant->locale = 'en';
        $variant->title = 'Newest';
        $variant->body = 'body';
        $variant->publishAt = new \DateTimeImmutable('2025-06-01');
        $em->persist($variant);
        $em->flush();
        $em->clear();

        $response = static::request('GET', '/articles?order[variant.publishAt]=desc');
        $this->assertResponseIsSuccessful();

        $slugs = array_map(
            static fn (array $item) => $item['slug'],
            $response->toArray()['member'],
        );

        $this->assertSame(['article-newest', 'article-newer', 'article-older'], $slugs);
    }

    public function testOrderByVariantPublishAtOnlyMatchesLocale(): void
    {
        $em = $this->getEm();

        // Article A: en variant published old, es variant published newest
        $articleA = new Article();
        $articleA->slug = 'article-a';
        $em->persist($articleA);

        $variantAen = new ArticleVariant();
        $variantAen->identity = $articleA;
        $variantAen->locale = 'en';
        $variantAen->title = 'A English';
        $variantAen->body = 'body';
        $variantAen->publishAt = new \DateTimeImmutable('2025-01-01');
        $em->persist($variantAen);

        $variantAes = new ArticleVariant();
        $variantAes->identity = $articleA;
        $variantAes->locale = 'es';
        $variantAes->title = 'A Spanish';
        $variantAes->body = 'body';
        $variantAes->publishAt = new \DateTimeImmutable('2025-12-01');
        $em->persist($variantAes);

        // Article B: en variant published newer than A's en variant
        $articleB = new Article();
        $articleB->slug = 'article-b';
        $em->persist($articleB);

        $variantBen = new ArticleVariant();
        $variantBen->identity = $articleB;
        $variantBen->locale = 'en';
        $variantBen->title = 'B English';
        $variantBen->body = 'body';
        $variantBen->publishAt = new \DateTimeImmutable('2025-06-01');
        $em->persist($variantBen);

        $em->flush();
        $em->clear();

        // Default locale resolution is 'en', so ordering should use
        // the en variants only: A=2025-01-01, B=2025-06-01
        // The es variant with 2025-12-01 should NOT influence sorting
        $response = static::request('GET', '/articles?order[variant.publishAt]=desc');
        $this->assertResponseIsSuccessful();

        $slugs = array_map(
            static fn (array $item) => $item['slug'],
            $response->toArray()['member'],
        );

        // B's en variant (June) is newer than A's en variant (Jan)
        $this->assertSame(['article-b', 'article-a'], $slugs);
    }

    public function testNonVariantOrderParamIsIgnored(): void
    {
        $this->createPublishedArticles();

        // order[slug] is not configured on the VariantOrderFilter, should not error
        $response = static::request('GET', '/articles?order[slug]=asc');
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($response->toArray()['member']);
    }

    public function testCollectionWithoutOrderParamStillWorks(): void
    {
        $this->createPublishedArticles();

        $response = static::request('GET', '/articles');
        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $response->toArray()['member']);
    }

    /**
     * Create two articles, each with a published en variant at different dates.
     */
    private function createPublishedArticles(): void
    {
        $em = $this->getEm();

        $articleOlder = new Article();
        $articleOlder->slug = 'article-older';
        $em->persist($articleOlder);

        $variantOlder = new ArticleVariant();
        $variantOlder->identity = $articleOlder;
        $variantOlder->locale = 'en';
        $variantOlder->title = 'Older Article';
        $variantOlder->body = 'body';
        $variantOlder->publishAt = new \DateTimeImmutable('2025-01-15');
        $em->persist($variantOlder);

        $articleNewer = new Article();
        $articleNewer->slug = 'article-newer';
        $em->persist($articleNewer);

        $variantNewer = new ArticleVariant();
        $variantNewer->identity = $articleNewer;
        $variantNewer->locale = 'en';
        $variantNewer->title = 'Newer Article';
        $variantNewer->body = 'body';
        $variantNewer->publishAt = new \DateTimeImmutable('2025-03-20');
        $em->persist($variantNewer);

        $em->flush();
        $em->clear();
    }
}
