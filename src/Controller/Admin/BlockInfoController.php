<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Controller\Admin;

use Depa\SuluBlocksBundle\Service\BlockRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BlockInfoController extends AbstractController
{
    public function __construct(
        private readonly BlockRegistry $registry,
    ) {
    }

    public function infoAction(): JsonResponse
    {
        return $this->json($this->registry->getApiData());
    }
}
