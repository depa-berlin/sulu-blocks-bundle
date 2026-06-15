<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Command;

use Depa\SuluBlocksBundle\Service\BlockSlotCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'sulu:blocks:generate-slots',
    description: 'Generates block--section.xml and block--container.xml based on installed block packages.',
)]
class GenerateSlotsCommand extends Command
{
    public function __construct(
        private readonly BlockSlotCollector $collector,
        private readonly ParameterBagInterface $parameterBag,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'output-dir',
            InputArgument::OPTIONAL,
            'Directory to write generated XML files (default: config/templates/blocks/)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $blocksDirs = $this->resolveBlocksDirs();

        if (!isset($blocksDirs['sulu_blocks'])) {
            $io->error('sulu-blocks-bundle is not registered. Cannot determine template source.');

            return Command::FAILURE;
        }

        $slots = $this->collector->collect($blocksDirs);

        $outputDir = $this->resolveOutputDir($input);

        if (!$this->ensureDir($outputDir)) {
            $io->error(\sprintf('Could not create output directory: %s', $outputDir));

            return Command::FAILURE;
        }

        $sectionTemplateDir = $blocksDirs['sulu_blocks'];

        $this->writeXml(
            $io,
            $sectionTemplateDir . '/block--section.xml',
            $outputDir . '/block--section.xml',
            $slots['section'],
        );

        $this->writeXml(
            $io,
            $sectionTemplateDir . '/block--container.xml',
            $outputDir . '/block--container.xml',
            $slots['container'],
        );

        $io->success('Slot files generated successfully.');

        return Command::SUCCESS;
    }

    /** @return array<string, string> */
    private function resolveBlocksDirs(): array
    {
        $dirs = [];

        foreach ($this->parameterBag->all() as $name => $value) {
            if (\str_ends_with((string) $name, '.blocks_dir') && \is_string($value)) {
                $alias = \str_replace('.blocks_dir', '', (string) $name);
                $dirs[$alias] = $value;
            }
        }

        return $dirs;
    }

    private function resolveOutputDir(InputInterface $input): string
    {
        /** @var string|null $arg */
        $arg = $input->getArgument('output-dir');

        return $arg ?? $this->projectDir . '/config/templates/blocks';
    }

    private function ensureDir(string $dir): bool
    {
        return \is_dir($dir) || \mkdir($dir, 0755, true);
    }

    /** @param list<string> $types */
    private function writeXml(SymfonyStyle $io, string $templateFile, string $outputFile, array $types): void
    {
        $xml = $this->collector->generateXml($templateFile, $types);
        \file_put_contents($outputFile, $xml);
        $io->writeln(\sprintf(
            '  Generated <info>%s</info> (%d types)',
            $outputFile,
            \count($types),
        ));
    }
}
