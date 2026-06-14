# sulu-blocks-bundle

Meta-bundle for Sulu CMS that orchestrates modular block bundles. Provides a central **BlockRegistry**, a **Sulu Admin Dashboard**, and automatic **cross-bundle connections**.

## Features

- **BlockRegistry** — central service tracking all installed block bundles, their types, and parent-child relationships
- **Admin Dashboard** — visual overview of installed bundles, available block types, and active connections (under *Settings → Block Bundles*)
- **Cross-Bundle Connections** — automatically detects compatible installed bundles and activates shared block capabilities
- **Self-Registration** — block bundles register themselves via container parameters; no hardcoded lists in the meta-bundle
- **Twig Functions** — `sulu_blocks_installed_bundles()`, `sulu_blocks_available_types()`, `sulu_blocks_is_bundle_installed()`

## Architecture

```
sulu-block-helper          (shared XML fragments + Twig partials)
       │
       ├── sulu-block-content (29 content blocks)
       │
       └── sulu-block-swiper  (8 swiper blocks)

sulu-blocks-bundle            (this meta-bundle)
       │
       └── orchestrates all of the above
```

## Requirements

- PHP 8.2+
- Symfony 7.0+
- Sulu CMS 3.0+
- `depa-berlin/sulu-block-helper`

## Installation

```bash
composer require depa-berlin/sulu-blocks-bundle

# Optionally install block collections:
composer require depa-berlin/sulu-block-content
composer require depa-berlin/sulu-block-swiper
```

Register in `config/bundles.php` (order matters):

```php
Depa\SuluBlockHelperBundle\SuluBlockHelperBundle::class => ['all' => true],
Depa\SuluBlocksBundle\SuluBlocksBundle::class => ['all' => true],
Depa\SuluBlockContentBundle\SuluBlockContentBundle::class => ['all' => true],  // optional
Depa\SuluBlockSwiperBundle\SuluBlockSwiperBundle::class => ['all' => true],    // optional
```

## Adding a New Block Bundle

Any third-party block bundle can self-register by setting a container parameter in its Extension:

```php
// In YourBundleExtension::load():
$container->setParameter('your_bundle.bundle_metadata', [
    'bundle'   => 'YourBundleName',
    'package'  => 'vendor/your-bundle',
    'blocks'   => ['block--your-block'],
    'children' => [],
]);
```

The `BlockBundleDiscoveryPass` picks this up automatically — no changes to `sulu-blocks-bundle` required.

## Twig Usage

```twig
{% if sulu_blocks_is_bundle_installed('SuluBlockSwiperBundle') %}
    {# Swiper-specific rendering #}
{% endif %}

{% for bundle in sulu_blocks_installed_bundles() %}
    {{ bundle.name }}: {{ bundle.blocks|length }} blocks
{% endfor %}
```

## License

Proprietary — Copyright (c) depa Berlin GmbH & Co. KG. All rights reserved.
See [LICENSE](LICENSE) for details.
