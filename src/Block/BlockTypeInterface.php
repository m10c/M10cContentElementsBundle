<?php

declare(strict_types=1);

namespace M10c\ContentElements\Block;

use Symfony\Component\Validator\Constraint;

interface BlockTypeInterface
{
    public function getKey(): string;

    public function getDataConstraints(): Constraint;

    /**
     * @return array<string, mixed>
     */
    public function getDefaultData(): array;

    /**
     * @return array{label: string, fields: array<string, array<string, mixed>>}
     */
    public function getSchema(): array;
}
