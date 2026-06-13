<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SuluBlocksExtension extends Extension implements PrependExtensionInterface
{
    /** @var array<array{requires: list<string>, blocks: list<string>, description: string}> */
    private const CROSS_CONNECTIONS = [
        [
            'requires'    => ['SuluBlockContentBundle', 'SuluBlockSwiperBundle'],
            'blocks'      => ['block--content-text', 'block--content-image', 'block--content-button'],
            'description' => 'Content blocks available as Swiper slide content',
        ],
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('services.yaml');

        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => [__DIR__ . '/../../Resources/translations'],
                ],
            ]);
        }

    }

    public function prepend(ContainerBuilder $container): void
    {
        /** @var array<string, string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        $activeConnections = [];
        foreach (self::CROSS_CONNECTIONS as $connection) {
            $allPresent = true;
            foreach ($connection['requires'] as $required) {
                if (!isset($bundles[$required])) {
                    $allPresent = false;
                    break;
                }
            }
            if ($allPresent) {
                $activeConnections[] = $connection;
            }
        }

        $container->setParameter('sulu_blocks.connections', $activeConnections);
    }
}
