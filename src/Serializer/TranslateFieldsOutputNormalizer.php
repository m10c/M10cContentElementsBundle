<?php

declare(strict_types=1);

namespace M10c\ContentElements\Serializer;

use M10c\ContentElements\Api\Dto\TranslateFieldsOutput;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Custom normalizer for TranslateFieldsOutput to prevent JSON-LD collection treatment.
 *
 * API Platform's JSON-LD serializer treats arrays as hydra:Collection by default.
 * This normalizer returns the simple array structure the frontend expects.
 */
final class TranslateFieldsOutputNormalizer implements NormalizerInterface
{
    /**
     * @return array{translations: array<string, string|null>}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        assert($object instanceof TranslateFieldsOutput);

        return [
            'translations' => $object->translations,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof TranslateFieldsOutput;
    }

    /**
     * @return array<class-string|'*'|'object'|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            TranslateFieldsOutput::class => true,
        ];
    }
}
