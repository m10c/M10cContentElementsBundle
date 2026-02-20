<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Fixtures\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use M10c\ContentElements\Api\Filter\VariantOrderFilter;
use M10c\ContentElements\Api\Provider\IdentityWithVariantProvider;
use M10c\ContentElements\Attribute\Identity;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Fixture: Identity with locale-based variants.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            provider: IdentityWithVariantProvider::class,
            normalizationContext: ['groups' => ['Article:Read']],
        ),
        new Get(
            provider: IdentityWithVariantProvider::class,
            normalizationContext: ['groups' => ['Article:Read']],
        ),
        new Get(
            name: 'article_admin',
            uriTemplate: '/articles/{id}/admin',
            normalizationContext: ['groups' => ['Article:Admin']],
        ),
        new Post(
            denormalizationContext: ['groups' => ['Article:Write', 'ContentElements:Dimension:Locale']],
        ),
        new Patch(
            denormalizationContext: ['groups' => ['Article:Write']],
        ),
        new Delete(),
    ],
)]
#[ApiFilter(VariantOrderFilter::class, properties: ['variant.publishAt' => 'publishAt'])]
#[Identity(variantClass: ArticleVariant::class)]
#[ORM\Entity]
#[ORM\Table(name: 'test_article')]
class Article
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['Article:Read', 'Article:Admin'])]
    public string $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['Article:Read', 'Article:Admin', 'Article:Write'])]
    public string $slug = '';

    /** @var Collection<int, ArticleVariant> */
    #[ORM\OneToMany(targetEntity: ArticleVariant::class, mappedBy: 'identity', cascade: ['persist', 'remove'])]
    #[Groups(['Article:Admin', 'Article:Write'])]
    public Collection $variants;

    /**
     * Hydrated by IdentityWithVariantProvider.
     */
    #[Groups(['Article:Read'])]
    public ?ArticleVariant $variant = null;

    public function __construct()
    {
        $this->id = 'article-'.bin2hex(random_bytes(4));
        $this->variants = new ArrayCollection();
    }

    /**
     * @param ArticleVariant[] $variants
     */
    public function setVariants(array $variants): void
    {
        $this->variants = new ArrayCollection($variants);
        foreach ($this->variants as $variant) {
            $variant->identity = $this;
        }
    }
}
