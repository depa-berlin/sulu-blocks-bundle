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

        /** @var list<array{bundle: string, package: string, blocks: list<string>, children: array<string, list<string>>}> $allMeta */
        $allMeta = [];

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

            $allMeta[] = [
                'bundle'   => (string) ($value['bundle'] ?? $key),
                'package'  => (string) ($value['package'] ?? ''),
                'blocks'   => (array) ($value['blocks'] ?? []),
                'children' => (array) ($value['children'] ?? []),
            ];
        }

        // 2. Auto-discover cross-bundle connections by analysing children references
        /** @var array<string, string> $blockToBundle */
        $blockToBundle = [];
        foreach ($allMeta as $meta) {
            foreach ($meta['blocks'] as $block) {
                $blockToBundle[$block] = $meta['bundle'];
            }
        }

        /** @var array<string, array{requires: list<string>, blocks: array<string, true>}> $connections */
        $connections = [];
        foreach ($allMeta as $meta) {
            $bundleA = $meta['bundle'];
            foreach ($meta['children'] as $childRefs) {
                foreach ($childRefs as $childRef) {
                    $bundleB = $blockToBundle[$childRef] ?? null;
                    if ($bundleB === null || $bundleB === $bundleA) {
                        continue;
                    }
                    $pair = [$bundleA, $bundleB];
                    sort($pair);
                    $pairKey = implode('|', $pair);
                    $connections[$pairKey]['requires'] = $pair;
                    $connections[$pairKey]['blocks'][$childRef] = true;
                }
            }
        }

        foreach ($connections as $connection) {
            $registryDef->addMethodCall('registerConnection', [
                $connection['requires'],
                array_keys($connection['blocks']),
                'auto',
            ]);
        }
    }
}
