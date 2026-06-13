<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BlockBundleDiscoveryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sulu_blocks.registry')) {
            return;
        }

        $registryDef = $container->getDefinition('sulu_blocks.registry');

        // 1. Register all bundles first — must come before registerConnection calls
        foreach ($container->getParameterBag()->all() as $key => $value) {
            if (!\str_ends_with((string) $key, '.bundle_metadata') || !\is_array($value)) {
                continue;
            }

            $registryDef->addMethodCall('registerBundle', [
                $value['bundle']   ?? $key,
                $value['package']  ?? '',
                $value['blocks']   ?? [],
                $value['children'] ?? [],
            ]);
        }

        // 2. Register connections after all bundles — isBundleInstalled() depends on this order
        if ($container->hasParameter('sulu_blocks.connections')) {
            /** @var list<array{requires: list<string>, blocks: list<string>, description: string}> $connections */
            $connections = $container->getParameter('sulu_blocks.connections');
            foreach ($connections as $connection) {
                $registryDef->addMethodCall('registerConnection', [
                    $connection['requires'],
                    $connection['blocks'],
                    $connection['description'] ?? '',
                ]);
            }
        }
    }
}
