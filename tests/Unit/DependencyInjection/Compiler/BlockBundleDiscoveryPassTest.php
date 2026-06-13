<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Tests\Unit\DependencyInjection\Compiler;

use Depa\SuluBlocksBundle\DependencyInjection\Compiler\BlockBundleDiscoveryPass;
use Depa\SuluBlocksBundle\Service\BlockRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BlockBundleDiscoveryPassTest extends TestCase
{
    private ContainerBuilder $container;
    private BlockBundleDiscoveryPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new BlockBundleDiscoveryPass();

        $registryDef = new Definition(BlockRegistry::class);
        $this->container->setDefinition('sulu_blocks.registry', $registryDef);
    }

    public function testProcessDoesNothingWithoutRegistry(): void
    {
        $container = new ContainerBuilder();
        $this->pass->process($container);
        $this->addToAssertionCount(1);
    }

    public function testProcessPicksUpBundleMetadataParameter(): void
    {
        $this->container->setParameter('sulu_block_test.bundle_metadata', [
            'bundle'   => 'TestBundle',
            'package'  => 'vendor/test',
            'blocks'   => ['block-a'],
            'children' => [],
        ]);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $registerBundleCalls = array_filter($calls, static fn($c) => \is_array($c) && ($c[0] ?? null) === 'registerBundle');
        self::assertCount(1, $registerBundleCalls);
    }

    public function testProcessIgnoresNonMetadataParameters(): void
    {
        $this->container->setParameter('some_other.parameter', ['not' => 'metadata']);
        $this->container->setParameter('sulu_blocks.connections', []);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $registerBundleCalls = array_filter($calls, static fn($c) => \is_array($c) && ($c[0] ?? null) === 'registerBundle');
        self::assertCount(0, $registerBundleCalls);
    }

    public function testProcessAddsConnectionsAfterBundles(): void
    {
        $this->container->setParameter('sulu_block_a.bundle_metadata', [
            'bundle' => 'BundleA', 'package' => 'vendor/a', 'blocks' => [], 'children' => [],
        ]);
        $this->container->setParameter('sulu_blocks.connections', [
            ['requires' => ['BundleA'], 'blocks' => ['x'], 'description' => 'test'],
        ]);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $methods = array_column($calls, 0);

        $registerBundlePos = array_search('registerBundle', $methods, true);
        $registerConnectionPos = array_search('registerConnection', $methods, true);

        self::assertIsInt($registerBundlePos);
        self::assertIsInt($registerConnectionPos);
        self::assertLessThan(
            $registerConnectionPos,
            $registerBundlePos,
            'registerBundle must be called before registerConnection'
        );
    }
}
