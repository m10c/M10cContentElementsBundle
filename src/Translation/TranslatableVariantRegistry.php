<?php

declare(strict_types=1);

namespace M10c\ContentElements\Translation;

use M10c\ContentElements\Attribute\TranslatableField;

/**
 * Registry that discovers and caches translatable fields for variant entities.
 *
 * This service scans entities for #[TranslatableField] attributes and provides
 * a way to query which fields are translatable for any given entity class.
 */
final class TranslatableVariantRegistry
{
    /**
     * Cache of translatable fields per entity class.
     *
     * @var array<class-string, list<string>>
     */
    private array $translatableFieldsCache = [];

    /**
     * Get the translatable field names for an entity.
     *
     * @param class-string|object $entity Entity class name or instance
     *
     * @return list<string> List of property names that are translatable
     */
    public function getTranslatableFields(string|object $entity): array
    {
        $className = is_object($entity) ? $entity::class : $entity;

        if (isset($this->translatableFieldsCache[$className])) {
            return $this->translatableFieldsCache[$className];
        }

        $fields = $this->discoverTranslatableFields($className);
        $this->translatableFieldsCache[$className] = $fields;

        return $fields;
    }

    /**
     * Check if a specific field is translatable on an entity.
     *
     * @param class-string|object $entity    Entity class name or instance
     * @param string              $fieldName The field name to check
     */
    public function isFieldTranslatable(string|object $entity, string $fieldName): bool
    {
        return in_array($fieldName, $this->getTranslatableFields($entity), true);
    }

    /**
     * Get the current values of translatable fields from an entity instance.
     *
     * @return array<string, string|null> Field name => current value
     */
    public function getTranslatableFieldValues(object $entity): array
    {
        $fields = $this->getTranslatableFields($entity);
        $values = [];

        foreach ($fields as $field) {
            $value = $entity->{$field} ?? null;
            $values[$field] = is_string($value) ? $value : null;
        }

        return $values;
    }

    /**
     * Discover translatable fields for an entity class using reflection.
     *
     * @param class-string $className
     *
     * @return list<string>
     */
    private function discoverTranslatableFields(string $className): array
    {
        if (!class_exists($className)) {
            return [];
        }

        $reflectionClass = new \ReflectionClass($className);
        $fields = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(TranslatableField::class);

            if (count($attributes) > 0) {
                $fields[] = $property->getName();
            }
        }

        return $fields;
    }
}
