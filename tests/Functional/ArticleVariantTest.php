<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Functional;

use M10c\ContentElements\Tests\Fixtures\Entity\Article;
use M10c\ContentElements\Tests\Fixtures\Entity\ArticleVariant;

class ArticleVariantTest extends ContentElementsTestCase
{
    public function testListVariantsViaIdentity(): void
    {
        // Create test data
        $article = $this->createArticleWithVariants();

        static::request('GET', "/articles/{$article->id}/variants");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'member' => [
                ['locale' => 'en'],
                ['locale' => 'es'],
            ],
        ]);
    }

    public function testCreateVariant(): void
    {
        $article = $this->createArticle();

        // Missing required fields
        static::request('POST', '/article_variants', [
            'identity' => "/articles/{$article->id}",
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                ['propertyPath' => 'title'],
            ],
        ]);

        // Valid creation
        $res = static::request('POST', '/article_variants', [
            'identity' => "/articles/{$article->id}",
            'locale' => 'fr',
            'title' => 'French Title',
            'body' => 'French body content',
        ])->toArray();
        $this->assertResponseIsSuccessful();
        $variantId = $res['id'];

        static::request('GET', "/article_variants/{$variantId}");
        $this->assertJsonContains([
            'title' => 'French Title',
            'body' => 'French body content',
        ]);
    }

    public function testPublishUnpublish(): void
    {
        $article = $this->createArticleWithVariants();
        $em = $this->getEm();

        // Get a variant
        $variant = $em->getRepository(ArticleVariant::class)
            ->findOneBy(['identity' => $article, 'locale' => 'en']);
        $this->assertNotNull($variant);

        // Initially unpublished - Identity should not be found via provider
        static::request('GET', "/articles/{$article->id}");
        $this->assertResponseStatusCodeSame(404);

        // Publish the variant
        static::request('POST', "/article_variants/{$variant->id}/publish", [
            'publishAt' => '2023-01-01T15:30:00+00:00',
        ]);
        $this->assertResponseIsSuccessful();

        // Now Identity should be found with hydrated variant
        static::request('GET', "/articles/{$article->id}");
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'slug' => 'test-slug',
            'variant' => [
                'title' => 'English Title',
            ],
        ]);

        // Unpublish
        static::request('POST', "/article_variants/{$variant->id}/unpublish");
        $this->assertResponseIsSuccessful();

        // Identity should not be found again
        static::request('GET', "/articles/{$article->id}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteLastVariantDeletesIdentity(): void
    {
        $article = $this->createArticle();
        $em = $this->getEm();

        // Create single variant
        $variant = new ArticleVariant();
        $variant->identity = $article;
        $variant->locale = 'en';
        $variant->title = 'Only Variant';
        $variant->body = 'Body';
        $em->persist($variant);
        $em->flush();
        $em->clear();

        $articleId = $article->id;
        $variantId = $variant->id;

        // Deleting the last variant should succeed and also delete the identity
        static::request('DELETE', "/article_variants/{$variantId}");
        $this->assertResponseStatusCodeSame(204);

        // Verify both variant and identity are deleted
        $em->clear();
        $this->assertNull($em->find(ArticleVariant::class, $variantId));
        $this->assertNull($em->find(Article::class, $articleId));
    }

    public function testDeleteVariantSucceedsWhenNotLast(): void
    {
        $article = $this->createArticleWithVariants();
        $em = $this->getEm();

        $variant = $em->getRepository(ArticleVariant::class)
            ->findOneBy(['identity' => $article, 'locale' => 'en']);
        $this->assertNotNull($variant);

        // Deleting one of multiple variants should succeed
        static::request('DELETE', "/article_variants/{$variant->id}");
        $this->assertResponseStatusCodeSame(204);

        // Verify only one variant remains
        $remainingVariants = $em->getRepository(ArticleVariant::class)
            ->findBy(['identity' => $article->id]);
        $this->assertCount(1, $remainingVariants);
    }

    private function createArticle(): Article
    {
        $em = $this->getEm();

        $article = new Article();
        $article->slug = 'test-slug';
        $em->persist($article);
        $em->flush();

        return $article;
    }

    private function createArticleWithVariants(): Article
    {
        $em = $this->getEm();

        $article = new Article();
        $article->slug = 'test-slug';
        $em->persist($article);

        $variantEn = new ArticleVariant();
        $variantEn->identity = $article;
        $variantEn->locale = 'en';
        $variantEn->title = 'English Title';
        $variantEn->body = 'English body';
        $em->persist($variantEn);

        $variantEs = new ArticleVariant();
        $variantEs->identity = $article;
        $variantEs->locale = 'es';
        $variantEs->title = 'Spanish Title';
        $variantEs->body = 'Spanish body';
        $em->persist($variantEs);

        $em->flush();
        $em->clear();

        return $article;
    }
}
