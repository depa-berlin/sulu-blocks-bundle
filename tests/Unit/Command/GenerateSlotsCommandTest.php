<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Tests\Unit\Command;

use Depa\SuluBlocksBundle\Command\GenerateSlotsCommand;
use Depa\SuluBlocksBundle\Service\BlockSlotCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class GenerateSlotsCommandTest extends TestCase
{
    private string $tempDir;
    private string $sectionFixtureDir;

    protected function setUp(): void
    {
        $this->tempDir = \sys_get_temp_dir() . '/sulu_blocks_cmd_test_' . \uniqid('', true);
        \mkdir($this->tempDir, 0755, true);

        $this->sectionFixtureDir = $this->tempDir . '/sulu_block_section_blocks';
        \mkdir($this->sectionFixtureDir);

        $fixture = \file_get_contents(__DIR__ . '/../../fixtures/block--test-section.xml');
        \assert(\is_string($fixture));
        \file_put_contents($this->sectionFixtureDir . '/block--section.xml', $fixture);
        \file_put_contents($this->sectionFixtureDir . '/block--container.xml', $fixture);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testCommandGeneratesFiles(): void
    {
        $slotsDirA = $this->tempDir . '/bundle_a';
        \mkdir($slotsDirA);
        \file_put_contents(
            $slotsDirA . '/_slots.yaml',
            "section:\n    - block--alpha\ncontainer:\n    - block--alpha\n"
        );

        $outputDir = $this->tempDir . '/output';

        $parameterBag = new ParameterBag([
            'sulu_blocks.blocks_dir' => $this->sectionFixtureDir,
            'bundle_a.blocks_dir'           => $slotsDirA,
        ]);

        $command = new GenerateSlotsCommand(new BlockSlotCollector(), $parameterBag, $this->tempDir);
        $tester = new CommandTester($command);
        $tester->execute(['output-dir' => $outputDir]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($outputDir . '/block--section.xml');
        self::assertFileExists($outputDir . '/block--container.xml');

        $sectionXml = \file_get_contents($outputDir . '/block--section.xml');
        \assert(\is_string($sectionXml));
        self::assertStringContainsString('ref="block--alpha"', $sectionXml);
    }

    public function testCommandFailsWithoutSectionBundle(): void
    {
        $parameterBag = new ParameterBag([]);

        $command = new GenerateSlotsCommand(new BlockSlotCollector(), $parameterBag, $this->tempDir);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(1, $tester->getStatusCode());
    }

    public function testCommandCreatesOutputDirectory(): void
    {
        $outputDir = $this->tempDir . '/deep/nested/output';

        $parameterBag = new ParameterBag([
            'sulu_blocks.blocks_dir' => $this->sectionFixtureDir,
        ]);

        $command = new GenerateSlotsCommand(new BlockSlotCollector(), $parameterBag, $this->tempDir);
        $tester = new CommandTester($command);
        $tester->execute(['output-dir' => $outputDir]);

        self::assertDirectoryExists($outputDir);
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
