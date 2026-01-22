<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use M10c\ContentElements\Api\Dto\TranslateFieldsInput;
use M10c\ContentElements\Api\Dto\TranslateFieldsOutput;
use M10c\ContentElements\Translation\TranslatableVariantRegistry;
use M10c\ContentElements\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmozart\Assert\Assert;

/**
 * Processor for translating fields on Variant entities.
 *
 * Returns translated field values for the frontend to apply to a form.
 * The frontend then submits via the existing PATCH endpoint.
 *
 * @implements ProcessorInterface<TranslateFieldsInput, TranslateFieldsOutput>
 */
final class TranslateFieldsProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
        private readonly TranslatableVariantRegistry $registry,
    ) {
    }

    /**
     * @param TranslateFieldsInput $data
     * @param array<mixed>         $uriVariables
     * @param array<mixed>         $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TranslateFieldsOutput
    {
        Assert::isInstanceOf($data, TranslateFieldsInput::class);

        $resourceClass = $operation->getClass();
        $id = $uriVariables['id'] ?? null;

        if (null === $resourceClass || null === $id) {
            throw new BadRequestHttpException('Could not determine the source variant.');
        }

        $sourceVariant = $this->em->find($resourceClass, $id);

        if (null === $sourceVariant) {
            throw new NotFoundHttpException('Variant not found.');
        }

        if (!property_exists($sourceVariant, 'locale')) {
            throw new BadRequestHttpException('Source variant does not have a locale property.');
        }

        /** @var string $sourceLocale */
        $sourceLocale = $sourceVariant->locale;
        $targetLocale = $data->targetLocale;

        if ($targetLocale === $sourceLocale) {
            throw new BadRequestHttpException('Cannot translate: source and target locales are the same.');
        }

        $allTranslatableFields = $this->registry->getTranslatableFields($sourceVariant);
        $fieldsToTranslate = empty($data->fields)
            ? $allTranslatableFields
            : array_values(array_intersect($data->fields, $allTranslatableFields));

        if (empty($fieldsToTranslate)) {
            throw new BadRequestHttpException('No valid translatable fields specified.');
        }

        foreach ($data->fields as $requestedField) {
            if (!in_array($requestedField, $allTranslatableFields, true)) {
                throw new BadRequestHttpException(sprintf('Field "%s" is not translatable. Available fields: %s', $requestedField, implode(', ', $allTranslatableFields)));
            }
        }

        $sourceTexts = [];
        foreach ($fieldsToTranslate as $field) {
            /** @var string|null $fieldValue */
            $fieldValue = $sourceVariant->{$field} ?? null;
            $sourceTexts[$field] = $fieldValue;
        }

        $translations = $this->translator->translateBatch(
            $sourceTexts,
            $sourceLocale,
            $targetLocale
        );

        return new TranslateFieldsOutput($translations);
    }
}
