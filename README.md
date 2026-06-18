# sulu-blocks-bundle

Meta-bundle for Sulu CMS that orchestrates modular block bundles. Provides a central **BlockRegistry**, a **Sulu Admin Dashboard**, and automatic **cross-bundle connections**.

## Features

- **BlockRegistry** — central service tracking all installed block bundles, their types, and parent-child relationships
- **Admin Dashboard** — visual overview of installed bundles, available block types, and active connections (under *Settings → Block Bundles*)
- **Cross-Bundle Connections** — automatically detects compatible installed bundles and activates shared block capabilities
- **Self-Registration** — block bundles register themselves via container parameters; no hardcoded lists in the meta-bundle
- **Twig Functions** — `sulu_blocks_installed_bundles()`, `sulu_blocks_available_types()`, `sulu_blocks_is_bundle_installed()`
- **Dynamic Slot Generation** — generates `block--section.xml` and `block--container.xml` based on installed packages

## Architecture

```
sulu-block-helper          (shared base classes + Twig partials)
       │
       ├── sulu-block-content (content blocks)
       ├── sulu-block-grid    (grid blocks)
       ├── sulu-block-hero    (hero blocks)
       ├── sulu-block-layout  (layout blocks)
       ├── sulu-block-section (section + container blocks)
       └── sulu-block-swiper  (swiper/carousel blocks)

sulu-blocks-bundle            (this meta-bundle)
       │
       └── orchestrates all of the above
```

## Requirements

- PHP 8.2+
- Symfony 7.0+
- Sulu CMS 3.0+
- `depa/sulu-block-helper`
- `depa/sulu-block-section` (required for slot generation)

## Installation

```bash
composer require depa/sulu-blocks-bundle

# Optionally install block collections:
composer require depa/sulu-block-content
composer require depa/sulu-block-grid
composer require depa/sulu-block-hero
composer require depa/sulu-block-layout
composer require depa/sulu-block-section
composer require depa/sulu-block-swiper
```

Register in `config/bundles.php`:

```php
Depa\SuluBlockHelperBundle\SuluBlockHelperBundle::class  => ['all' => true],
Depa\SuluBlockContentBundle\SuluBlockContentBundle::class => ['all' => true], // optional
Depa\SuluBlockGridBundle\SuluBlockGridBundle::class       => ['all' => true], // optional
Depa\SuluBlockHeroBundle\SuluBlockHeroBundle::class       => ['all' => true], // optional
Depa\SuluBlockLayoutBundle\SuluBlockLayoutBundle::class   => ['all' => true], // optional
Depa\SuluBlockSectionBundle\SuluBlockSectionBundle::class => ['all' => true], // optional
Depa\SuluBlockSwiperBundle\SuluBlockSwiperBundle::class   => ['all' => true], // optional
Depa\SuluBlocksBundle\SuluBlocksBundle::class             => ['all' => true],
```

Register the project override directory in `config/packages/sulu_admin.yaml`:

```yaml
sulu_admin:
    templates:
        block:
            directories:
                app_blocks: '%kernel.project_dir%/config/templates/blocks'
```

## Dynamic Slot Generation

`block--section.xml` and `block--container.xml` contain a list of sub-block types that
vary depending on which block packages are installed. Instead of maintaining this list
manually, run the following command after installing or removing a block package:

```bash
bin/console sulu:blocks:generate-slots
```

This command:

1. Reads all registered block directories (`sulu_admin.templates.block.directories`)
2. Collects block types declared in each package's `_slots.yaml`
3. Uses `block--section.xml` and `block--container.xml` from `sulu-block-section` as templates
4. Writes the generated files to `config/templates/blocks/` in the project

The generated files override the package defaults via the `app_blocks` directory registered above.

To write to a custom directory:

```bash
bin/console sulu:blocks:generate-slots path/to/output/
```

### Declaring slot-compatible blocks in a custom bundle

Add a `_slots.yaml` to your bundle's block directory:

```yaml
# Resources/config/blocks/_slots.yaml
section:
    - block--my-custom-block
    - block--my-other-block

container:
    - block--my-custom-block
```

The command picks this up automatically — no changes to `sulu-blocks-bundle` required.

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
