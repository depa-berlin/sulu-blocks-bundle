<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Admin;

use Depa\SuluBlocksBundle\Service\BlockRegistry;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;

class SuluBlocksAdmin extends Admin
{
    public const DASHBOARD_VIEW = 'sulu_blocks.dashboard';

    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly ViewBuilderFactoryInterface $viewBuilderFactory,
    ) {
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $viewCollection->add(
            $this->viewBuilderFactory
                ->createViewBuilder(self::DASHBOARD_VIEW, '/sulu-blocks', self::DASHBOARD_VIEW)
        );
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        $item = new NavigationItem('sulu_blocks.navigation.title');
        $item->setPosition(30);
        $item->setView(self::DASHBOARD_VIEW);

        $navigationItemCollection
            ->get(Admin::SETTINGS_NAVIGATION_ITEM)
            ->addChild($item);
    }

    public function getConfigKey(): string
    {
        return 'sulu_blocks';
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->registry->getApiData();
    }
}
