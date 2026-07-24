<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Functional;

final class BlockTypeListTest extends ContentElementsTestCase
{
    public function testListsRegisteredBlockTypesWithSchemas(): void
    {
        self::request('GET', '/block-types');

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'totalItems' => 2,
            'member' => [
                [
                    'key' => 'hero',
                    'label' => 'Hero',
                    'fields' => [
                        'headline' => ['kind' => 'text', 'label' => 'Headline', 'maxLength' => 255],
                        'subhead' => ['kind' => 'textarea', 'label' => 'Subhead', 'maxLength' => 500],
                    ],
                ],
                [
                    'key' => 'rich-text',
                    'label' => 'Rich text',
                    'fields' => [
                        'body' => [
                            'kind' => 'markdown',
                            'label' => 'Body',
                            'features' => ['bold', 'italic', 'lists', 'links'],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
