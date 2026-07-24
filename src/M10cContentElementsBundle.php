<?php

declare(strict_types=1);

namespace M10c\ContentElements;

use M10c\ContentElements\Block\BlockTypeInterface;
use M10c\ContentElements\Sitemap\SitemapSourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class M10cContentElementsBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        if ('test' === $container->env()) {
            $container->import('../config/services_test.yaml');
        }

        $builder->registerForAutoconfiguration(BlockTypeInterface::class)
            ->addTag('m10c.content_elements.block_type');

        $builder->registerForAutoconfiguration(SitemapSourceInterface::class)
            ->addTag('m10c.content_elements.sitemap_source');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->extension('api_platform', [
            'mapping' => [
                'paths' => [dirname(__DIR__).'/src/Block'],
            ],
        ]);
    }
}
