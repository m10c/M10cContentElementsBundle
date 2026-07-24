<?php

declare(strict_types=1);

namespace M10c\ContentElements\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait HasSeoMetaTrait
{
    public const TITLE_RECOMMENDED_MIN = 45;
    public const TITLE_RECOMMENDED_MAX = 60;
    public const DESCRIPTION_RECOMMENDED_MIN = 100;
    public const DESCRIPTION_RECOMMENDED_MAX = 150;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Length(max: 255)]
    #[Serializer\Groups(['ContentElements:SeoMeta'])]
    public string $seoTitle = '';

    #[ORM\Column(type: 'string', length: 500)]
    #[Assert\Length(max: 500)]
    #[Serializer\Groups(['ContentElements:SeoMeta'])]
    public string $seoDescription = '';

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    #[Assert\Length(max: 512)]
    #[Serializer\Groups(['ContentElements:SeoMeta'])]
    public ?string $seoImage = null;
}
