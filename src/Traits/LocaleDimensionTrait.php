<?php

declare(strict_types=1);

namespace M10c\ContentElements\Traits;

use Doctrine\ORM\Mapping as ORM;
use M10c\ContentElements\Attribute as ContentElements;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait LocaleDimensionTrait
{
    #[Assert\NotBlank]
    #[ContentElements\Dimension\Locale]
    #[Map(if: false)] // TODO: Delete after migration
    #[ORM\Column]
    #[Serializer\Groups(['ContentElements:Dimension:Locale'])]
    public string $locale;
}
