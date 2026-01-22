<?php

declare(strict_types=1);

namespace M10c\ContentElements\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Common\Util\ClassUtils;
use M10c\ContentElements\Finder\IdentityFinder;
use M10c\ContentElements\Metadata\MetadataRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Processor for variant delete operations that also deletes the identity when the last variant is deleted.
 *
 * When the last variant of an identity is deleted, the identity is also deleted.
 *
 * @implements ProcessorInterface<object, null>
 */
final class VariantDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        /** @var ProcessorInterface<object, null> */
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $decorated,
        private readonly IdentityFinder $identityFinder,
        private readonly MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * @param array<mixed>                                                                                                                      $uriVariables
     * @param array{request?: \Symfony\Component\HttpFoundation\Request, previous_data?: mixed, resource_class?: string, original_data?: mixed} $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $identity = $this->identityFinder->findIdentity($data);
        if (null === $identity) {
            throw new \LogicException(sprintf('VariantDeleteProcessor could not find an identity for %s. The variant must have a property that references an entity with the #[Identity] attribute.', $data::class));
        }

        $identityMetadata = $this->metadataRegistry->getIdentityMetadata(ClassUtils::getClass($identity));
        $variantsProperty = $identityMetadata->variantsProperty;

        if (!property_exists($identity, $variantsProperty)) {
            throw new \LogicException(sprintf('VariantDeleteProcessor cannot be used with %s because it does not have a "%s" property.', $identity::class, $variantsProperty));
        }

        // Get the variant count using the configured property name
        $variantCount = $identity->{$variantsProperty}->count();

        if ($variantCount <= 1) {
            // Last variant - delete the identity (which will cascade delete the variant)
            return $this->decorated->process($identity, $operation, $uriVariables, $context);
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
