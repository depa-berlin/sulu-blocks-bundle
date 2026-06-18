<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BlockSlotDirectoriesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sulu_admin')) {
            return;
        }

        foreach ($container->getExtensionConfig('sulu_admin') as $config) {
            /** @var array<string, string> $dirs */
            $dirs = $config['templates']['block']['directories'] ?? [];

            foreach ($dirs as $alias => $path) {
                $paramName = $alias . '.blocks_dir';

                if (!$container->hasParameter($paramName)) {
                    $container->setParameter($paramName, $path);
                }
            }
        }
    }
}
