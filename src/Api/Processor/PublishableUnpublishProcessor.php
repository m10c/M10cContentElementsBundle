<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use M10c\ContentElements\Filter\PublishableInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<PublishableInterface, PublishableInterface>
 */
final class PublishableUnpublishProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     * @param mixed[]                 $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PublishableInterface
    {
        Assert::isInstanceOf($publishable = $data, PublishableInterface::class);

        $publishable->setPublishablePublishAt(null);

        $this->em->flush();

        return $publishable;
    }
}
