<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\DependencyInjection;

use Depa\SuluBlockHelperBundle\DependencyInjection\BlockMetadataLoaderTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SuluBlocksExtension extends Extension implements PrependExtensionInterface
{
    use BlockMetadataLoaderTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('services.yaml');

        $blocksDir = $this->getBlocksDir();
        $metadata  = $this->loadMetadataFromXml($blocksDir);

        $container->setParameter('sulu_blocks.blocks_dir', $blocksDir);
        $container->setParameter('sulu_blocks.bundle_metadata', [
            'bundle'   => 'SuluBlocksBundle',
            'package'  => 'depa/sulu-blocks-bundle',
            'blocks'   => $metadata['blocks'],
            'children' => $metadata['children'],
        ]);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    $this->getViewsDir() => null,
                ],
            ]);
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig('sulu_admin', [
                'templates' => [
                    'block' => [
                        'directories' => [
                            'sulu_blocks' => $this->getBlocksDir(),
                        ],
                    ],
                ],
            ]);
        }

        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => [__DIR__ . '/../../Resources/translations'],
                ],
            ]);
        }
    }

    private function getBlocksDir(): string
    {
        return __DIR__ . '/../../Resources/config/blocks';
    }

    private function getViewsDir(): string
    {
        return __DIR__ . '/../../Resources/views';
    }
}
