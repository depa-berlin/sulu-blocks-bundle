<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Twig;

use Depa\SuluBlocksBundle\Service\BlockRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BlockRegistryTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly BlockRegistry $registry,
    ) {
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_blocks_installed_bundles', $this->getInstalledBundles(...)),
            new TwigFunction('sulu_blocks_available_types', $this->getAvailableBlockTypes(...)),
            new TwigFunction('sulu_blocks_is_bundle_installed', $this->isBundleInstalled(...)),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getInstalledBundles(): array
    {
        return $this->registry->getInstalledBundles();
    }

    /**
     * @return list<string>
     */
    public function getAvailableBlockTypes(): array
    {
        return $this->registry->getAllBlockTypes();
    }

    public function isBundleInstalled(string $bundleName): bool
    {
        return $this->registry->isBundleInstalled($bundleName);
    }
}
