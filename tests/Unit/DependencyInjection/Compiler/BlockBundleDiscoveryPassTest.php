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

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $registerBundleCalls = array_filter($calls, static fn($c) => \is_array($c) && ($c[0] ?? null) === 'registerBundle');
        self::assertCount(0, $registerBundleCalls);
    }

    public function testRegisterBundleIsCalledBeforeRegisterConnection(): void
    {
        $this->container->setParameter('sulu_block_a.bundle_metadata', [
            'bundle' => 'BundleA', 'package' => 'vendor/a',
            'blocks' => ['block-a'],
            'children' => ['block-a' => ['block-b']],
        ]);
        $this->container->setParameter('sulu_block_b.bundle_metadata', [
            'bundle' => 'BundleB', 'package' => 'vendor/b',
            'blocks' => ['block-b'],
            'children' => [],
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

    public function testAutoDiscoversCrossConnectionBetweenBundles(): void
    {
        $this->container->setParameter('sulu_block_a.bundle_metadata', [
            'bundle' => 'BundleA', 'package' => 'vendor/a',
            'blocks' => ['block-a'],
            'children' => ['block-a' => ['block-b']],
        ]);
        $this->container->setParameter('sulu_block_b.bundle_metadata', [
            'bundle' => 'BundleB', 'package' => 'vendor/b',
            'blocks' => ['block-b'],
            'children' => [],
        ]);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $connectionCalls = array_values(array_filter($calls, static fn($c) => ($c[0] ?? null) === 'registerConnection'));
        self::assertCount(1, $connectionCalls);
        self::assertContains('block-b', $connectionCalls[0][1][1]);
    }

    public function testNoConnectionWhenChildIsInSameBundle(): void
    {
        $this->container->setParameter('sulu_block_a.bundle_metadata', [
            'bundle' => 'BundleA', 'package' => 'vendor/a',
            'blocks' => ['block-a', 'block-child'],
            'children' => ['block-a' => ['block-child']],
        ]);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $connectionCalls = array_filter($calls, static fn($c) => ($c[0] ?? null) === 'registerConnection');
        self::assertCount(0, $connectionCalls);
    }

    public function testNoConnectionWhenChildIsUnknown(): void
    {
        $this->container->setParameter('sulu_block_a.bundle_metadata', [
            'bundle' => 'BundleA', 'package' => 'vendor/a',
            'blocks' => ['block-a'],
            'children' => ['block-a' => ['block-unknown']],
        ]);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $connectionCalls = array_filter($calls, static fn($c) => ($c[0] ?? null) === 'registerConnection');
        self::assertCount(0, $connectionCalls);
    }

    public function testConnectionGroupsBlocksByBundlePair(): void
    {
        $this->container->setParameter('sulu_block_a.bundle_metadata', [
            'bundle' => 'BundleA', 'package' => 'vendor/a',
            'blocks' => ['block-a'],
            'children' => ['block-a' => ['block-b1', 'block-b2']],
        ]);
        $this->container->setParameter('sulu_block_b.bundle_metadata', [
            'bundle' => 'BundleB', 'package' => 'vendor/b',
            'blocks' => ['block-b1', 'block-b2'],
            'children' => [],
        ]);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $connectionCalls = array_values(array_filter($calls, static fn($c) => ($c[0] ?? null) === 'registerConnection'));
        self::assertCount(1, $connectionCalls, 'two children from same bundle pair must produce one connection');
        $blocks = $connectionCalls[0][1][1];
        self::assertContains('block-b1', $blocks);
        self::assertContains('block-b2', $blocks);
    }

    public function testConnectionRequiresPairIsSorted(): void
    {
        $this->container->setParameter('sulu_block_z.bundle_metadata', [
            'bundle' => 'ZBundle', 'package' => 'vendor/z',
            'blocks' => ['block-z'],
            'children' => ['block-z' => ['block-a']],
        ]);
        $this->container->setParameter('sulu_block_a.bundle_metadata', [
            'bundle' => 'ABundle', 'package' => 'vendor/a',
            'blocks' => ['block-a'],
            'children' => [],
        ]);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $connectionCalls = array_values(array_filter($calls, static fn($c) => ($c[0] ?? null) === 'registerConnection'));
        self::assertCount(1, $connectionCalls);
        self::assertSame(['ABundle', 'ZBundle'], $connectionCalls[0][1][0], 'requires must be alphabetically sorted');
    }

    public function testMutualChildrenProduceOneConnectionNotTwo(): void
    {
        $this->container->setParameter('sulu_block_a.bundle_metadata', [
            'bundle' => 'BundleA', 'package' => 'vendor/a',
            'blocks' => ['block-a'],
            'children' => ['block-a' => ['block-b']],
        ]);
        $this->container->setParameter('sulu_block_b.bundle_metadata', [
            'bundle' => 'BundleB', 'package' => 'vendor/b',
            'blocks' => ['block-b'],
            'children' => ['block-b' => ['block-a']],
        ]);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition('sulu_blocks.registry')->getMethodCalls();
        $connectionCalls = array_values(array_filter($calls, static fn($c) => ($c[0] ?? null) === 'registerConnection'));
        self::assertCount(1, $connectionCalls, 'mutual cross-references must produce exactly one merged connection');
    }
}
