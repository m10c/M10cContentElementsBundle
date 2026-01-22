<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Metadata;

use M10c\ContentElements\Attribute\Dimension\Locale as LocaleAttribute;
use M10c\ContentElements\Attribute\Dimension\Version as VersionAttribute;
use M10c\ContentElements\Attribute\Filter\Publishable as PublishableAttribute;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Metadata\DimensionMetadata;
use M10c\ContentElements\Metadata\FilterMetadata;
use M10c\ContentElements\Metadata\MetadataRegistry;
use M10c\ContentElements\Tests\Fixtures\Entity\Article;
use M10c\ContentElements\Tests\Fixtures\Entity\ArticleVariant;
use PHPUnit\Framework\TestCase;

/**
 * @covers \M10c\ContentElements\Metadata\MetadataRegistry
 */
final class MetadataRegistryTest extends TestCase
{
    private MetadataRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new MetadataRegistry();
    }

    public function testGetIdentityMetadataReturnsNullForNonIdentityClass(): void
    {
        $result = $this->registry->getIdentityMetadata(\stdClass::class);

        self::assertNull($result);
    }

    public function testGetIdentityMetadataReturnsIdentityAttribute(): void
    {
        $result = $this->registry->getIdentityMetadata(Article::class);

        self::assertInstanceOf(Identity::class, $result);
        self::assertSame(ArticleVariant::class, $result->variantClass);
        self::assertSame('variants', $result->variantsProperty);
        self::assertSame('identity', $result->identityProperty);
        self::assertSame('variant', $result->variantProperty);
    }

    public function testGetIdentityMetadataCachesResult(): void
    {
        $result1 = $this->registry->getIdentityMetadata(Article::class);
        $result2 = $this->registry->getIdentityMetadata(Article::class);

        // In-memory cache returns same instance
        self::assertSame($result1, $result2);
    }

    public function testGetVariantMetadataReturnsEmptyForPlainClass(): void
    {
        $result = $this->registry->getVariantMetadata(\stdClass::class);

        self::assertSame([], $result);
    }

    public function testGetVariantMetadataReturnsDimensionMetadata(): void
    {
        $result = $this->registry->getVariantMetadata(ArticleVariant::class);

        $localeMetadata = array_filter(
            $result,
            fn ($item) => $item instanceof DimensionMetadata && $item->attribute instanceof LocaleAttribute
        );

        self::assertCount(1, $localeMetadata);
        $localeMetadata = array_values($localeMetadata)[0];
        self::assertSame('locale', $localeMetadata->property);
    }

    public function testGetVariantMetadataReturnsFilterMetadata(): void
    {
        $result = $this->registry->getVariantMetadata(ArticleVariant::class);

        $publishableMetadata = array_filter(
            $result,
            fn ($item) => $item instanceof FilterMetadata && $item->attribute instanceof PublishableAttribute
        );

        self::assertCount(1, $publishableMetadata);
        $publishableMetadata = array_values($publishableMetadata)[0];
        self::assertSame('publishAt', $publishableMetadata->property);
    }

    public function testGetVariantDimensionMetadataReturnsCorrectMetadata(): void
    {
        $result = $this->registry->getVariantDimensionMetadata(ArticleVariant::class, LocaleAttribute::class);

        self::assertInstanceOf(DimensionMetadata::class, $result);
        self::assertSame('locale', $result->property);
        self::assertInstanceOf(LocaleAttribute::class, $result->attribute);
    }

    public function testGetVariantDimensionMetadataReturnsNullForNonExistent(): void
    {
        // ArticleVariant doesn't have Version dimension
        $result = $this->registry->getVariantDimensionMetadata(ArticleVariant::class, VersionAttribute::class);

        self::assertNull($result);
    }

    public function testGetVariantFilterMetadataReturnsCorrectMetadata(): void
    {
        $result = $this->registry->getVariantFilterMetadata(ArticleVariant::class, PublishableAttribute::class);

        self::assertInstanceOf(FilterMetadata::class, $result);
        self::assertSame('publishAt', $result->property);
        self::assertInstanceOf(PublishableAttribute::class, $result->attribute);
    }

    public function testGetVariantFilterMetadataReturnsNullForNonExistent(): void
    {
        // ArticleVariant doesn't have Archivable filter
        $result = $this->registry->getVariantFilterMetadata(ArticleVariant::class, \M10c\ContentElements\Attribute\Filter\Archivable::class);

        self::assertNull($result);
    }
}
