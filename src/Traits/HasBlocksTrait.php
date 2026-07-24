<?php

declare(strict_types=1);

namespace M10c\ContentElements\Traits;

use Doctrine\ORM\Mapping as ORM;
use M10c\ContentElements\Block\Validator\AllBlocksValid;
use Symfony\Component\Serializer\Attribute as Serializer;

trait HasBlocksTrait
{
    /**
     * @var list<array{type: string, data: array<string, mixed>}>
     */
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    #[Serializer\Groups(['ContentElements:Blocks'])]
    #[AllBlocksValid]
    public array $blocks = [];
}
