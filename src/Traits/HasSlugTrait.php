<?php

declare(strict_types=1);

namespace M10c\ContentElements\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait HasSlugTrait
{
    #[ORM\Column(type: 'string', length: 128)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    #[Assert\Regex(
        pattern: '~^/?[a-z0-9][a-z0-9/\-]*$~',
        message: 'Slug must be lowercase letters, digits, hyphens or forward slashes.',
    )]
    #[Serializer\Groups(['ContentElements:Slug'])]
    public string $slug;
}
