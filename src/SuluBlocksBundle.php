<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle;

use Depa\SuluBlocksBundle\DependencyInjection\Compiler\BlockBundleDiscoveryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluBlocksBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new BlockBundleDiscoveryPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
