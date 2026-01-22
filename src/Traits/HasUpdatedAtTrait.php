<?php

declare(strict_types=1);

namespace M10c\ContentElements\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Adds an updatedAt field that is automatically set on creation and updated on every persist.
 *
 * Note: The entity using this trait must have #[ORM\HasLifecycleCallbacks] attribute.
 */
trait HasUpdatedAtTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    #[Serializer\Groups(['ContentElements:UpdatedAt'])]
    public \DateTimeImmutable $updatedAt;

    #[ORM\PrePersist]
    public function initUpdatedAt(): void
    {
        $this->updatedAt = new DatePoint();
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new DatePoint();
    }
}
