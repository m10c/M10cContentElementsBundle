<?php

declare(strict_types=1);

namespace M10c\ContentElements\Block\Validator;

use M10c\ContentElements\Block\BlockTypeRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class AllBlocksValidValidator extends ConstraintValidator
{
    public function __construct(
        private readonly BlockTypeRegistry $registry,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AllBlocksValid) {
            throw new UnexpectedTypeException($constraint, AllBlocksValid::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        foreach (array_values($value) as $index => $block) {
            if (
                !is_array($block)
                || !isset($block['type'], $block['data'])
                || !is_string($block['type'])
                || !is_array($block['data'])
            ) {
                $this->context->buildViolation($constraint->messageShape)
                    ->setParameter('{{ index }}', (string) $index)
                    ->atPath("[{$index}]")
                    ->addViolation();

                continue;
            }

            if (!$this->registry->has($block['type'])) {
                $this->context->buildViolation($constraint->messageUnknownType)
                    ->setParameter('{{ index }}', (string) $index)
                    ->setParameter('{{ type }}', $block['type'])
                    ->atPath("[{$index}].type")
                    ->addViolation();

                continue;
            }

            $blockType = $this->registry->get($block['type']);
            $this->context->getValidator()
                ->inContext($this->context)
                ->atPath("[{$index}].data")
                ->validate($block['data'], $blockType->getDataConstraints());
        }
    }
}
