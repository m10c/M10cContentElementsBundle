<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Translation;

use M10c\ContentElements\Tests\Fixtures\Entity\ArticleVariant;
use M10c\ContentElements\Translation\TranslatableVariantRegistry;
use PHPUnit\Framework\TestCase;

class TranslatableVariantRegistryTest extends TestCase
{
    private TranslatableVariantRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new TranslatableVariantRegistry();
    }

    public function testDiscoverTranslatableFields(): void
    {
        $fields = $this->registry->getTranslatableFields(ArticleVariant::class);

        $this->assertContains('title', $fields);
        $this->assertContains('body', $fields);
        $this->assertNotContains('id', $fields);
        $this->assertNotContains('locale', $fields);
    }

    public function testIsFieldTranslatable(): void
    {
        $this->assertTrue($this->registry->isFieldTranslatable(ArticleVariant::class, 'title'));
        $this->assertFalse($this->registry->isFieldTranslatable(ArticleVariant::class, 'id'));
    }

    public function testGetTranslatableFieldValues(): void
    {
        $variant = new ArticleVariant();
        $variant->title = 'Example Title';
        $variant->body = 'This is the article body.';

        $values = $this->registry->getTranslatableFieldValues($variant);

        $this->assertEquals([
            'title' => 'Example Title',
            'body' => 'This is the article body.',
        ], $values);
    }
}
