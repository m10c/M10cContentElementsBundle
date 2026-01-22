<?php

declare(strict_types=1);

namespace M10c\ContentElements\Translation;

/**
 * Interface for translation services.
 *
 * Implementations provide the actual translation functionality (e.g., AWS Translate).
 */
interface TranslatorInterface
{
    /**
     * Translate text from source locale to target locale.
     *
     * @param string $text         The text to translate
     * @param string $sourceLocale The source language code (e.g., 'en')
     * @param string $targetLocale The target language code (e.g., 'es')
     */
    public function translate(string $text, string $sourceLocale, string $targetLocale): string;

    /**
     * Translate multiple texts in a batch.
     *
     * @param array<string, string|null> $texts        Associative array of field => text
     * @param string                     $sourceLocale The source language code
     * @param string                     $targetLocale The target language code
     *
     * @return array<string, string|null> Associative array of field => translated text
     */
    public function translateBatch(array $texts, string $sourceLocale, string $targetLocale): array;
}
