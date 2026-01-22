<?php

declare(strict_types=1);

namespace M10c\ContentElements\Filter;

interface PublishableInterface
{
    public function getPublishablePublishAt(): ?\DateTimeImmutable;

    public function setPublishablePublishAt(?\DateTimeImmutable $value): void;
}
