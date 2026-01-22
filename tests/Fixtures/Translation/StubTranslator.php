<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Fixtures\Translation;

use M10c\ContentElements\Translation\TranslatorInterface;

/**
 * Stub translator for testing that returns predictable translations.
 */
final class StubTranslator implements TranslatorInterface
{
    public function translate(string $text, string $sourceLocale, string $targetLocale): string
    {
        return sprintf('[%s] %s', $targetLocale, $text);
    }

    public function translateBatch(array $texts, string $sourceLocale, string $targetLocale): array
    {
        $result = [];
        foreach ($texts as $field => $text) {
            $result[$field] = null === $text ? null : $this->translate($text, $sourceLocale, $targetLocale);
        }

        return $result;
    }
}
