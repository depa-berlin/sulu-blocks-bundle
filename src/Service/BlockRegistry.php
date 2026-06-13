<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Service;

class BlockRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $bundles = [];

    /** @var array<array{requires: list<string>, blocks: list<string>, description: string}> */
    private array $connections = [];

    /**
     * @param list<string>                $blockTypes
     * @param array<string, list<string>> $children
     */
    public function registerBundle(string $bundleName, string $packageName, array $blockTypes, array $children = []): void
    {
        $this->bundles[$bundleName] = [
            'package'  => $packageName,
            'blocks'   => $blockTypes,
            'children' => $children,
        ];
    }

    /**
     * @param list<string> $requires
     * @param list<string> $blockTypes
     */
    public function registerConnection(array $requires, array $blockTypes, string $description = ''): void
    {
        foreach ($requires as $bundle) {
            if (!$this->isBundleInstalled($bundle)) {
                return;
            }
        }

        $this->connections[] = [
            'requires'    => $requires,
            'blocks'      => $blockTypes,
            'description' => $description,
        ];
    }

    public function isBundleInstalled(string $bundleName): bool
    {
        return isset($this->bundles[$bundleName]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getInstalledBundles(): array
    {
        return $this->bundles;
    }

    /**
     * @return list<string>
     */
    public function getAllBlockTypes(): array
    {
        $types = [];
        foreach ($this->bundles as $info) {
            $types = [...$types, ...$info['blocks']];
        }

        return array_values(array_unique($types));
    }

    /**
     * @return list<string>
     */
    public function getBlockTypesForBundle(string $bundleName): array
    {
        return $this->bundles[$bundleName]['blocks'] ?? [];
    }

    /**
     * @return array<array{requires: list<string>, blocks: list<string>, description: string}>
     */
    public function getActiveConnections(): array
    {
        return $this->connections;
    }

    /**
     * Returns the canonical API/config payload shared by the REST controller and SuluBlocksAdmin.
     *
     * @return array{installedBundles: list<array<string,mixed>>, totalBlocks: int, connections: list<array<string,mixed>>}
     */
    public function getApiData(): array
    {
        $bundles = [];
        foreach ($this->bundles as $name => $info) {
            $bundles[] = [
                'name'       => $name,
                'package'    => $info['package'],
                'blockCount' => \count($info['blocks']),
                'blocks'     => $info['blocks'],
                'children'   => $info['children'] ?? [],
            ];
        }

        return [
            'installedBundles' => $bundles,
            'totalBlocks'      => \count($this->getAllBlockTypes()),
            'connections'      => $this->connections,
        ];
    }
}
