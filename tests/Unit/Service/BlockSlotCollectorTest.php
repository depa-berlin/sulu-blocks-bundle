<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Tests\Unit\Service;

use Depa\SuluBlocksBundle\Service\BlockSlotCollector;
use PHPUnit\Framework\TestCase;

class BlockSlotCollectorTest extends TestCase
{
    private BlockSlotCollector $collector;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->collector = new BlockSlotCollector();
        $this->tempDir = \sys_get_temp_dir() . '/sulu_blocks_test_' . \uniqid('', true);
        \mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testCollectsTypesFromSlotsFile(): void
    {
        $dir = $this->tempDir . '/bundle_a';
        \mkdir($dir);
        \file_put_contents($dir . '/_slots.yaml', "section:\n    - block--alpha\n    - block--beta\ncontainer:\n    - block--alpha\n");

        $result = $this->collector->collect(['bundle_a' => $dir]);

        self::assertSame(['block--alpha', 'block--beta'], $result['section']);
        self::assertSame(['block--alpha'], $result['container']);
    }

    public function testMergesMultipleDirectories(): void
    {
        $dirA = $this->tempDir . '/bundle_a';
        $dirB = $this->tempDir . '/bundle_b';
        \mkdir($dirA);
        \mkdir($dirB);
        \file_put_contents($dirA . '/_slots.yaml', "section:\n    - block--alpha\ncontainer: []\n");
        \file_put_contents($dirB . '/_slots.yaml', "section:\n    - block--beta\ncontainer:\n    - block--beta\n");

        $result = $this->collector->collect(['a' => $dirA, 'b' => $dirB]);

        self::assertSame(['block--alpha', 'block--beta'], $result['section']);
        self::assertSame(['block--beta'], $result['container']);
    }

    public function testSkipsMissingSlotFile(): void
    {
        $dir = $this->tempDir . '/bundle_no_slots';
        \mkdir($dir);

        $result = $this->collector->collect(['x' => $dir]);

        self::assertSame([], $result['section']);
        self::assertSame([], $result['container']);
    }

    public function testDeduplicatesTypes(): void
    {
        $dirA = $this->tempDir . '/bundle_a';
        $dirB = $this->tempDir . '/bundle_b';
        \mkdir($dirA);
        \mkdir($dirB);
        \file_put_contents($dirA . '/_slots.yaml', "section:\n    - block--alpha\ncontainer: []\n");
        \file_put_contents($dirB . '/_slots.yaml', "section:\n    - block--alpha\ncontainer: []\n");

        $result = $this->collector->collect(['a' => $dirA, 'b' => $dirB]);

        self::assertSame(['block--alpha'], $result['section']);
    }

    public function testGenerateXmlReplacesTypes(): void
    {
        $fixture = __DIR__ . '/../../fixtures/block--test-section.xml';
        $types = ['block--new-one', 'block--new-two'];

        $xml = $this->collector->generateXml($fixture, $types);

        self::assertStringContainsString('ref="block--new-one"', $xml);
        self::assertStringContainsString('ref="block--new-two"', $xml);
        self::assertStringNotContainsString('ref="block--foo"', $xml);
        self::assertStringNotContainsString('ref="block--bar"', $xml);
    }

    public function testGenerateXmlWithEmptyTypesProducesEmptyTypesElement(): void
    {
        $fixture = __DIR__ . '/../../fixtures/block--test-section.xml';

        $xml = $this->collector->generateXml($fixture, []);

        self::assertStringNotContainsString('ref=', $xml);
        self::assertStringContainsString('<types/>', $xml);
    }

    public function testGenerateXmlThrowsForMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->collector->generateXml('/nonexistent/path/block.xml', []);
    }

    private function removeDir(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }
        foreach (\scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            \is_dir($path) ? $this->removeDir($path) : \unlink($path);
        }
        \rmdir($dir);
    }
}
