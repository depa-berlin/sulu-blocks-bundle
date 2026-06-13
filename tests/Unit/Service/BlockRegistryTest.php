<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Tests\Unit\Service;

use Depa\SuluBlocksBundle\Service\BlockRegistry;
use PHPUnit\Framework\TestCase;

class BlockRegistryTest extends TestCase
{
    private BlockRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new BlockRegistry();
    }

    public function testInitiallyEmpty(): void
    {
        self::assertEmpty($this->registry->getInstalledBundles());
        self::assertEmpty($this->registry->getAllBlockTypes());
        self::assertEmpty($this->registry->getActiveConnections());
    }

    public function testRegisterBundleStoresBundle(): void
    {
        $this->registry->registerBundle('TestBundle', 'vendor/test', ['block-a']);
        self::assertTrue($this->registry->isBundleInstalled('TestBundle'));
    }

    public function testUnknownBundleIsNotInstalled(): void
    {
        self::assertFalse($this->registry->isBundleInstalled('NonExistentBundle'));
    }

    public function testRegisterBundleStoresAllData(): void
    {
        $this->registry->registerBundle(
            'TestBundle',
            'vendor/test',
            ['block-a', 'block-b'],
            ['block-a' => ['block-b']]
        );

        $bundles = $this->registry->getInstalledBundles();
        self::assertArrayHasKey('TestBundle', $bundles);
        self::assertSame('vendor/test', $bundles['TestBundle']['package']);
        self::assertSame(['block-a', 'block-b'], $bundles['TestBundle']['blocks']);
        self::assertSame(['block-a' => ['block-b']], $bundles['TestBundle']['children']);
    }

    public function testGetAllBlockTypesMergesAcrossBundles(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', ['block-a1', 'block-a2']);
        $this->registry->registerBundle('BundleB', 'vendor/b', ['block-b1']);

        $types = $this->registry->getAllBlockTypes();
        self::assertCount(3, $types);
        self::assertContains('block-a1', $types);
        self::assertContains('block-a2', $types);
        self::assertContains('block-b1', $types);
    }

    public function testGetAllBlockTypesDeduplicates(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', ['shared', 'unique-a']);
        $this->registry->registerBundle('BundleB', 'vendor/b', ['shared', 'unique-b']);

        $types = $this->registry->getAllBlockTypes();
        self::assertCount(3, $types);
        self::assertCount(1, array_filter($types, static fn($t) => $t === 'shared'));
    }

    public function testGetAllBlockTypesReturnsIndexedList(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', ['block-x', 'block-y']);
        $this->registry->registerBundle('BundleB', 'vendor/b', ['block-x', 'block-z']);

        $types = $this->registry->getAllBlockTypes();
        self::assertSame($types, array_values($types), 'array_unique must be re-indexed with array_values');
    }

    public function testGetBlockTypesForBundle(): void
    {
        $this->registry->registerBundle('MyBundle', 'vendor/my', ['block-x', 'block-y']);
        self::assertSame(['block-x', 'block-y'], $this->registry->getBlockTypesForBundle('MyBundle'));
    }

    public function testGetBlockTypesForUnknownBundleReturnsEmptyArray(): void
    {
        self::assertSame([], $this->registry->getBlockTypesForBundle('Unknown'));
    }

    public function testRegisterConnectionSkippedWhenBundleMissing(): void
    {
        $this->registry->registerConnection(['MissingBundle'], ['block-z']);
        self::assertEmpty($this->registry->getActiveConnections());
    }

    public function testRegisterConnectionSkippedWhenOneBundleMissing(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', []);
        $this->registry->registerConnection(['BundleA', 'BundleB'], ['block-x']);
        self::assertEmpty($this->registry->getActiveConnections());
    }

    public function testRegisterConnectionStoredWhenAllBundlesPresent(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', []);
        $this->registry->registerBundle('BundleB', 'vendor/b', []);
        $this->registry->registerConnection(['BundleA', 'BundleB'], ['cross-block'], 'Test connection');

        $connections = $this->registry->getActiveConnections();
        self::assertCount(1, $connections);
        self::assertSame(['BundleA', 'BundleB'], $connections[0]['requires']);
        self::assertSame(['cross-block'], $connections[0]['blocks']);
        self::assertSame('Test connection', $connections[0]['description']);
    }

    public function testRegisterConnectionWithDefaultDescription(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', []);
        $this->registry->registerBundle('BundleB', 'vendor/b', []);
        $this->registry->registerConnection(['BundleA', 'BundleB'], ['block-x']);

        $connections = $this->registry->getActiveConnections();
        self::assertSame('', $connections[0]['description']);
    }

    public function testGetApiDataStructure(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', ['block-1', 'block-2']);

        $data = $this->registry->getApiData();
        self::assertArrayHasKey('installedBundles', $data);
        self::assertArrayHasKey('totalBlocks', $data);
        self::assertArrayHasKey('connections', $data);
    }

    public function testGetApiDataTotalBlocks(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', ['block-1', 'block-2']);
        $this->registry->registerBundle('BundleB', 'vendor/b', ['block-3']);

        $data = $this->registry->getApiData();
        self::assertSame(3, $data['totalBlocks']);
    }

    public function testGetApiDataBundleShape(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', ['block-1'], ['block-1' => []]);

        $data = $this->registry->getApiData();
        $bundle = $data['installedBundles'][0];
        self::assertSame('BundleA', $bundle['name']);
        self::assertSame('vendor/a', $bundle['package']);
        self::assertSame(1, $bundle['blockCount']);
        self::assertSame(['block-1'], $bundle['blocks']);
        self::assertArrayHasKey('children', $bundle);
    }

    public function testGetApiDataConnections(): void
    {
        $this->registry->registerBundle('BundleA', 'vendor/a', []);
        $this->registry->registerBundle('BundleB', 'vendor/b', []);
        $this->registry->registerConnection(['BundleA', 'BundleB'], ['x'], 'desc');

        $data = $this->registry->getApiData();
        self::assertCount(1, $data['connections']);
        self::assertSame('desc', $data['connections'][0]['description']);
    }
}
