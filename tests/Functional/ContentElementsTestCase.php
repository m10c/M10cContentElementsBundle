<?php

declare(strict_types=1);

namespace M10c\ContentElements\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use M10c\ContentElements\Tests\App\TestKernel;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class ContentElementsTestCase extends ApiTestCase
{
    protected static bool $schemaCreated = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->createSchemaIfNeeded();
        $this->loadFixtures();
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function getEm(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    private function createSchemaIfNeeded(): void
    {
        if (self::$schemaCreated) {
            return;
        }

        $em = $this->getEm();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        self::$schemaCreated = true;
    }

    protected function loadFixtures(): void
    {
        // Clear tables for clean test state
        $em = $this->getEm();
        $connection = $em->getConnection();

        $connection->executeStatement('DELETE FROM test_article_variant');
        $connection->executeStatement('DELETE FROM test_article');
    }

    /**
     * @param array<string, string> $headers
     */
    protected static function request(string $method, string $url, mixed $json = null, array $headers = []): ResponseInterface
    {
        if ('PATCH' === $method) {
            $headers['Content-Type'] = 'application/merge-patch+json';
        } elseif ('POST' === $method) {
            $headers['Content-Type'] = 'application/ld+json';
        }

        $options = ['headers' => $headers];
        if (null !== $json) {
            $options['json'] = $json;
        } elseif ('POST' === $method) {
            // Ensure POST without body sends empty JSON object
            $options['body'] = '{}';
        }

        $response = static::createClient()->request(
            $method,
            $url,
            $options
        );
        $response->getHeaders(throw: false);

        return $response;
    }
}
