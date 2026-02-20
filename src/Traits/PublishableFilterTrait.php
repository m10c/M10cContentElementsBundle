<?php

declare(strict_types=1);

namespace M10c\ContentElements\Traits;

use Doctrine\ORM\Mapping as ORM;
use M10c\ContentElements\Attribute as ContentElements;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Serializer\Attribute as Serializer;

trait PublishableFilterTrait
{
    #[ContentElements\Filter\Publishable]
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Serializer\Groups(['ContentElements:Filter:Publishable'])]
    public ?\DateTimeImmutable $publishAt = null;

    public function getPublishablePublishAt(): ?\DateTimeImmutable
    {
        return $this->publishAt;
    }

    public function setPublishablePublishAt(?\DateTimeImmutable $value): void
    {
        $this->publishAt = $value;
    }

    public function isPublished(): bool
    {
        return $this->publishAt ? ($this->publishAt < new DatePoint()) : false;
    }
}
