<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Fixtures\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use Doctrine\ORM\Mapping as ORM;
use M10c\ContentElements\Traits\HasBlocksTrait;
use M10c\ContentElements\Traits\HasSeoMetaTrait;
use M10c\ContentElements\Traits\HasUpdatedAtTrait;
use M10c\ContentElements\Traits\LocaleDimensionTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => [
        'PageVariant:Read',
        'ContentElements:SeoMeta',
        'ContentElements:Blocks',
        'ContentElements:UpdatedAt',
    ]],
    denormalizationContext: ['groups' => [
        'ContentElements:SeoMeta',
        'ContentElements:Blocks',
    ]],
    operations: [
        new Patch(),
    ],
)]
#[ORM\Entity]
#[ORM\Table(name: 'test_page_variant')]
#[ORM\HasLifecycleCallbacks]
class PageVariant
{
    use HasBlocksTrait;
    use HasSeoMetaTrait;
    use HasUpdatedAtTrait;
    use LocaleDimensionTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['PageVariant:Read', 'Page:Read'])]
    public string $id;

    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(name: 'identity_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public Page $identity;

    public function __construct()
    {
        $this->id = 'page-variant-'.bin2hex(random_bytes(4));
    }
}
