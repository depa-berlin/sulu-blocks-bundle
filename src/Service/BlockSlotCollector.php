<?php

declare(strict_types=1);

namespace Depa\SuluBlocksBundle\Service;

use Symfony\Component\Yaml\Yaml;

class BlockSlotCollector
{
    /**
     * Collects all block types per slot from _slots.yaml files in the given directories.
     *
     * @param array<string, string> $blocksDirs  alias => absolute path
     * @return array{section: list<string>, container: list<string>}
     */
    public function collect(array $blocksDirs): array
    {
        $slots = ['section' => [], 'container' => []];

        foreach ($blocksDirs as $dir) {
            $slotsFile = $dir . '/_slots.yaml';

            if (!\is_file($slotsFile)) {
                continue;
            }

            /** @var mixed $config */
            $config = Yaml::parseFile($slotsFile);

            if (!\is_array($config)) {
                continue;
            }

            foreach (['section', 'container'] as $slot) {
                /** @var mixed $types */
                $types = $config[$slot] ?? [];
                if (\is_array($types)) {
                    /** @var list<string> $types */
                    $slots[$slot] = array_merge($slots[$slot], $types);
                }
            }
        }

        return [
            'section'   => array_values(array_unique($slots['section'])),
            'container' => array_values(array_unique($slots['container'])),
        ];
    }

    /**
     * Generates a modified copy of the given block XML template with the provided types list.
     *
     * @param list<string> $types
     */
    public function generateXml(string $templateFile, array $types): string
    {
        if (!\is_file($templateFile)) {
            throw new \RuntimeException(\sprintf('Template file not found: %s', $templateFile));
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->load($templateFile);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('sulu', 'http://schemas.sulu.io/template/template');

        $typesNodes = $xpath->query(
            '//sulu:section[@name="blocks"]//sulu:block[@name="blocks"]/sulu:types'
        );

        if ($typesNodes === false || $typesNodes->length === 0) {
            throw new \RuntimeException(\sprintf(
                'Could not find <types> inside <block name="blocks"> in: %s',
                $templateFile
            ));
        }

        $typesNode = $typesNodes->item(0);
        \assert($typesNode instanceof \DOMElement);

        while ($typesNode->hasChildNodes()) {
            $typesNode->removeChild($typesNode->firstChild); // @phpstan-ignore-line
        }

        foreach ($types as $type) {
            $typesNode->appendChild($dom->createTextNode("\n                        "));
            $typeEl = $dom->createElement('type');
            $typeEl->setAttribute('ref', $type);
            $typesNode->appendChild($typeEl);
        }

        if ($types !== []) {
            $typesNode->appendChild($dom->createTextNode("\n                    "));
        }

        $xml = $dom->saveXML();

        if ($xml === false) {
            throw new \RuntimeException('Failed to serialize XML document.');
        }

        return $xml;
    }
}
