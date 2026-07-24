<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Fixtures\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use M10c\ContentElements\Api\Provider\ByPropertyProvider;
use M10c\ContentElements\Api\Provider\IdentityWithVariantProvider;
use M10c\ContentElements\Attribute\Identity;
use M10c\ContentElements\Traits\HasSlugTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => [
        'Page:Read',
        'ContentElements:Slug',
        'ContentElements:SeoMeta',
        'ContentElements:Blocks',
        'ContentElements:UpdatedAt',
    ]],
    operations: [
        new GetCollection(provider: IdentityWithVariantProvider::class),
        new Get(provider: IdentityWithVariantProvider::class),
        new Get(
            name: 'page_by_slug',
            uriTemplate: '/pages/by-slug/{slug}',
            uriVariables: ['slug'],
            provider: ByPropertyProvider::class,
        ),
    ],
)]
#[Identity(variantClass: PageVariant::class)]
#[ORM\Entity]
#[ORM\Table(name: 'test_page')]
#[ORM\UniqueConstraint(name: 'test_page_slug_uniq', columns: ['slug'])]
class Page
{
    use HasSlugTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['Page:Read'])]
    public string $id;

    /** @var Collection<int, PageVariant> */
    #[ORM\OneToMany(targetEntity: PageVariant::class, mappedBy: 'identity', cascade: ['persist', 'remove'])]
    #[Groups(['Page:Admin'])]
    public Collection $variants;

    /** Hydrated by IdentityWithVariantProvider / ByPropertyProvider. */
    #[Groups(['Page:Read'])]
    public ?PageVariant $variant = null;

    public function __construct()
    {
        $this->id = 'page-'.bin2hex(random_bytes(4));
        $this->variants = new ArrayCollection();
    }

    /**
     * @param PageVariant[] $variants
     */
    public function setVariants(array $variants): void
    {
        $this->variants = new ArrayCollection($variants);
        foreach ($this->variants as $variant) {
            $variant->identity = $this;
        }
    }
}
