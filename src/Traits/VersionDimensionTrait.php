<?php

declare(strict_types=1);

namespace M10c\ContentElements\Traits;

use Doctrine\ORM\Mapping as ORM;
use M10c\ContentElements\Attribute as ContentElements;
use Symfony\Component\Serializer\Attribute as Serializer;

trait VersionDimensionTrait
{
    #[ContentElements\Dimension\Version]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Serializer\Groups(['ContentElements:Dimension:Version'])]
    public int $version;
}
