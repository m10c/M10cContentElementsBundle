<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Fixtures\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use M10c\ContentElements\Api\Processor\PublishableUnpublishProcessor;
use M10c\ContentElements\Api\Processor\VariantDeleteProcessor;
use M10c\ContentElements\Attribute\TranslatableField;
use M10c\ContentElements\Filter\PublishableInterface;
use M10c\ContentElements\Traits\LocaleDimensionTrait;
use M10c\ContentElements\Traits\PublishableFilterTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fixture: Variant with locale dimension and publishable filter.
 */
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['ArticleVariant:Read']],
        ),
        new Post(
            denormalizationContext: ['groups' => ['ArticleVariant:Write', 'ContentElements:Dimension:Locale']],
        ),
        new Patch(
            denormalizationContext: ['groups' => ['ArticleVariant:Write']],
        ),
        new Post(
            uriTemplate: '/article_variants/{id}/publish',
            denormalizationContext: ['groups' => ['ContentElements:Filter:Publishable']],
        ),
        new Post(
            uriTemplate: '/article_variants/{id}/unpublish',
            processor: PublishableUnpublishProcessor::class,
        ),
        new Delete(
            processor: VariantDeleteProcessor::class,
        ),
    ],
)]
#[ApiResource(
    uriTemplate: '/articles/{id}/variants',
    uriVariables: [
        'id' => new Link(
            fromClass: Article::class,
            toProperty: 'identity',
        ),
    ],
    order: ['locale' => 'ASC'],
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['ArticleVariant:Read', 'ContentElements:Dimension:Locale']],
        ),
    ],
)]
#[ORM\Entity]
#[ORM\Table(name: 'test_article_variant')]
class ArticleVariant implements PublishableInterface
{
    use LocaleDimensionTrait;
    use PublishableFilterTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['ArticleVariant:Read', 'Article:Read'])]
    public string $id;

    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(name: 'identity_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['ArticleVariant:Write'])]
    public Article $identity;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['ArticleVariant:Read', 'ArticleVariant:Write', 'Article:Read'])]
    #[TranslatableField]
    public string $title = '';

    #[ORM\Column(type: 'text')]
    #[Groups(['ArticleVariant:Read', 'ArticleVariant:Write', 'Article:Read'])]
    #[TranslatableField]
    public string $body = '';

    public function __construct()
    {
        $this->id = 'article-variant-'.bin2hex(random_bytes(4));
    }
}
