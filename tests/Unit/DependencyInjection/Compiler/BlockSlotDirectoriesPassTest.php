<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Tests\Unit\DependencyInjection\Compiler;

use Depa\SuluBlocksBundle\DependencyInjection\Compiler\BlockSlotDirectoriesPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class BlockSlotDirectoriesPassTest extends TestCase
{
    public function testRegistersDirectoriesFromSuluAdminConfig(): void
    {
        $container = new ContainerBuilder();

        $suluAdmin = $this->createMock(ExtensionInterface::class);
        $suluAdmin->method('getAlias')->willReturn('sulu_admin');
        $container->registerExtension($suluAdmin);

        $container->prependExtensionConfig('sulu_admin', [
            'templates' => [
                'block' => [
                    'directories' => [
                        'app_blocks' => '/project/config/templates/blocks',
                    ],
                ],
            ],
        ]);

        (new BlockSlotDirectoriesPass())->process($container);

        self::assertTrue($container->hasParameter('app_blocks.blocks_dir'));
        self::assertSame('/project/config/templates/blocks', $container->getParameter('app_blocks.blocks_dir'));
    }

    public function testDoesNotOverrideExistingBundleParameter(): void
    {
        $container = new ContainerBuilder();

        $suluAdmin = $this->createMock(ExtensionInterface::class);
        $suluAdmin->method('getAlias')->willReturn('sulu_admin');
        $container->registerExtension($suluAdmin);

        $container->setParameter('app_blocks.blocks_dir', '/original/path');

        $container->prependExtensionConfig('sulu_admin', [
            'templates' => [
                'block' => [
                    'directories' => [
                        'app_blocks' => '/new/path',
                    ],
                ],
            ],
        ]);

        (new BlockSlotDirectoriesPass())->process($container);

        self::assertSame('/original/path', $container->getParameter('app_blocks.blocks_dir'));
    }

    public function testDoesNothingWithoutSuluAdmin(): void
    {
        $container = new ContainerBuilder();

        (new BlockSlotDirectoriesPass())->process($container);

        $this->addToAssertionCount(1);
    }
}
