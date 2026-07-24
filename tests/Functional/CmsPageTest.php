<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Functional;

final class CmsPageTest extends ContentElementsTestCase
{
    public function testPageResource(): void
    {
        $variant = $this->seedPage('home', 'Home', 'Home description', [
            ['type' => 'hero', 'data' => ['headline' => 'Hi', 'subhead' => 'Hello']],
        ]);

        // GET by slug returns page + hydrated variant with blocks.
        self::request('GET', '/pages/by-slug/home');
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'slug' => 'home',
            'variant' => [
                'seoTitle' => 'Home',
                'seoDescription' => 'Home description',
                'blocks' => [
                    ['type' => 'hero', 'data' => ['headline' => 'Hi', 'subhead' => 'Hello']],
                ],
            ],
        ]);

        // GET by slug returns 404 when not found.
        self::request('GET', '/pages/by-slug/does-not-exist');
        self::assertResponseStatusCodeSame(404);

        // PATCH rejects unknown block type.
        self::request('PATCH', "/page_variants/{$variant->id}", [
            'blocks' => [
                ['type' => 'made-up', 'data' => []],
            ],
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'blocks[0].type',
                    'message' => 'Block at position 0 has unknown type "made-up".',
                ],
            ],
        ]);

        // PATCH rejects blocks with malformed shape.
        self::request('PATCH', "/page_variants/{$variant->id}", [
            'blocks' => [
                ['type' => 'hero'],
            ],
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'blocks[0]',
                    'message' => 'Block at position 0 must be an array with "type" and "data" keys.',
                ],
            ],
        ]);

        // PATCH rejects content that violates the block type's own constraints.
        self::request('PATCH', "/page_variants/{$variant->id}", [
            'blocks' => [
                ['type' => 'hero', 'data' => ['headline' => '', 'subhead' => 'set']],
            ],
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'blocks[0].data[headline]',
                    'message' => 'This value should not be blank.',
                ],
            ],
        ]);

        // PATCH with valid blocks of mixed types replaces the variant's block list.
        self::request('PATCH', "/page_variants/{$variant->id}", [
            'blocks' => [
                ['type' => 'hero', 'data' => ['headline' => 'Welcome', 'subhead' => 'Come on in']],
                ['type' => 'rich-text', 'data' => ['body' => 'Long form copy.']],
            ],
        ]);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'blocks' => [
                ['type' => 'hero', 'data' => ['headline' => 'Welcome', 'subhead' => 'Come on in']],
                ['type' => 'rich-text', 'data' => ['body' => 'Long form copy.']],
            ],
        ]);
    }
}
