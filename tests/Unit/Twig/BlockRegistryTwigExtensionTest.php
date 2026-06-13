<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Tests\Unit\Twig;

use Depa\SuluBlocksBundle\Service\BlockRegistry;
use Depa\SuluBlocksBundle\Twig\BlockRegistryTwigExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class BlockRegistryTwigExtensionTest extends TestCase
{
    private BlockRegistry $registry;
    private BlockRegistryTwigExtension $extension;

    protected function setUp(): void
    {
        $this->registry = new BlockRegistry();
        $this->extension = new BlockRegistryTwigExtension($this->registry);
    }

    public function testGetFunctionsReturnsThreeFunctions(): void
    {
        $functions = $this->extension->getFunctions();
        self::assertCount(3, $functions);
    }

    public function testGetFunctionsAreNamedCorrectly(): void
    {
        $names = array_map(static fn(TwigFunction $f) => $f->getName(), $this->extension->getFunctions());
        self::assertContains('sulu_blocks_installed_bundles', $names);
        self::assertContains('sulu_blocks_available_types', $names);
        self::assertContains('sulu_blocks_is_bundle_installed', $names);
    }

    public function testGetInstalledBundlesDelegatesToRegistry(): void
    {
        $this->registry->registerBundle('TestBundle', 'vendor/test', ['block-a']);
        self::assertArrayHasKey('TestBundle', $this->extension->getInstalledBundles());
    }

    public function testGetAvailableBlockTypesDelegatesToRegistry(): void
    {
        $this->registry->registerBundle('TestBundle', 'vendor/test', ['block-a', 'block-b']);
        $types = $this->extension->getAvailableBlockTypes();
        self::assertContains('block-a', $types);
        self::assertContains('block-b', $types);
    }

    public function testIsBundleInstalledReturnsTrueForRegisteredBundle(): void
    {
        $this->registry->registerBundle('TestBundle', 'vendor/test', []);
        self::assertTrue($this->extension->isBundleInstalled('TestBundle'));
    }

    public function testIsBundleInstalledReturnsFalseForUnknownBundle(): void
    {
        self::assertFalse($this->extension->isBundleInstalled('NonExistent'));
    }
}
