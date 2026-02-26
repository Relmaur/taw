# AGENTS.md — AI Agent Guide for TAW Theme

> **TAW** = Tailwind + Alpine + WordPress
> A classic WordPress theme with a component-based block architecture, Vite asset pipeline, and a bespoke metabox framework.

---

## Quick Orientation

| Path | Purpose |
|---|---|
| `inc/Core/` | Framework internals — base classes, registry, loader, metabox engine |
| `inc/Blocks/` | Dev block collection — one folder per block, auto-discovered |
| `inc/vite-loader.php` | Vite ↔ WordPress bridge (dev/prod) |
| `resources/css/app.css` | Tailwind v4 entry point |
| `resources/scss/app.scss` | Global SCSS (non-Tailwind styles) |
| `resources/js/app.js` | Alpine.js + global JS entry |
| `functions.php` | Theme bootstrap (minimal — delegates to block system) |

---

## Architecture: The Block System

### Class Hierarchy

```
BaseBlock (abstract)
├── MetaBlock (abstract) — owns data via metaboxes, fetches from post_meta
│   └── Hero, Stats, Testimonials, etc.
└── Block (abstract) — presentational, receives data as props
    └── Button, Card, Badge, etc.
```

### Key Files

| File | Role |
|---|---|
| `inc/Core/BaseBlock.php` | Reflection-based auto-discovery of component directory, asset enqueuing (CSS/JS), template rendering via `extract()` |
| `inc/Core/MetaBlock.php` | Extends BaseBlock. Registers metaboxes in constructor, provides `getData(int $postId)` and `render(?int $postId)` |
| `inc/Core/Block.php` | Extends BaseBlock. Defines `defaults()` for props, provides `render(array $props)` |
| `inc/Core/BlockRegistry.php` | Static registry for MetaBlocks. Supports `register()`, `queue()`, `render()`, `enqueueQueuedAssets()` |
| `inc/Core/BlockLoader.php` | Auto-discovers all MetaBlock classes by scanning `inc/Blocks/*/` directories |
| `inc/Core/Metabox.php` | Configuration-driven metabox framework. Field registration, rendering, saving, and static retrieval helpers |

### Naming Convention (CRITICAL)

Every block follows this exact convention — the folder name **must** match the class name:

```
inc/Blocks/{Name}/{Name}.php    → class TAW\Blocks\{Name}\{Name}
inc/Blocks/{Name}/index.php     → Template file
inc/Blocks/{Name}/style.css     → Optional stylesheet (or style.scss)
inc/Blocks/{Name}/script.js     → Optional JavaScript
```

Example:
```
inc/Blocks/Hero/Hero.php        → class TAW\Blocks\Hero\Hero extends MetaBlock
inc/Blocks/Hero/index.php       → Template receives extracted variables
inc/Blocks/Hero/style.css       → Enqueued only when Hero renders
```

BlockLoader relies on this convention for auto-discovery. Breaking it will silently skip the block.

### Two Block Types

**MetaBlock** (data-owning sections):
- Registered in `BlockRegistry` via `BlockLoader::loadAll()`
- Owns metaboxes → appears in WP admin editor
- Fetches its own data from `post_meta`
- Rendered via `BlockRegistry::render('hero')`

**Block** (presentational UI components):
- NOT registered in the registry
- Receives data as props
- Instantiated directly where needed: `(new Button())->render(['text' => 'Click'])`

### Asset Loading Strategy — The Queue Pattern

Assets are loaded conditionally per page. The timing chain is:

```
functions.php        → BlockLoader::loadAll() registers all MetaBlocks
template file        → BlockRegistry::queue('hero', 'stats') ← BEFORE get_header()
get_header()         → wp_enqueue_scripts fires
                       → BlockRegistry::enqueueQueuedAssets() (only queued blocks' CSS/JS)
                     → wp_head() outputs <link>/<script> in <head>
template body        → BlockRegistry::render('hero') outputs HTML only
get_footer()         → wp_footer()
```

**Template pattern:**
```php
<?php
use TAW\Core\BlockRegistry;

// 1. Queue blocks BEFORE get_header (assets land in <head>)
BlockRegistry::queue('hero', 'stats');

get_header();
?>

<?php BlockRegistry::render('hero'); ?>
<?php BlockRegistry::render('stats'); ?>

<?php get_footer(); ?>
```

**Safety fallback:** If `render()` is called without prior `queue()`, styles are printed inline in the body via `did_action('wp_head')` check. This works but is suboptimal (potential FOUC). Always prefer `queue()` first.

---

## Creating a New MetaBlock

### Step 1: Create the directory and class

```php
<?php
// inc/Blocks/Features/Features.php

declare(strict_types=1);

namespace TAW\Blocks\Features;

use TAW\Core\MetaBlock;
use TAW\Core\Metabox\Metabox;

class Features extends MetaBlock
{
    protected string $id = 'features';

    protected function registerMetaboxes(): void
    {
        new Metabox([
            'id'     => 'taw_features',
            'title'  => 'Features Section',
            'screen' => 'page',
            'fields' => [
                [
                    'id'    => 'features_heading',
                    'label' => 'Heading',
                    'type'  => 'text',
                ],
                // Add more fields...
            ],
        ]);
    }

    protected function getData(int $postId): array
    {
        return [
            'heading' => $this->getMeta($postId, 'features_heading'),
        ];
    }
}
```

### Step 2: Create the template

```php
<?php
// inc/Blocks/Features/index.php

/** @var string $heading */

if (empty($heading)) return;
?>

<section class="features">
    <h2><?php echo esc_html($heading); ?></h2>
</section>
```

### Step 3: Optionally add style.css or style.scss

The block auto-discovers and enqueues these when rendered. SCSS is prioritized over CSS if both exist.

### Step 4: That's it

`BlockLoader::loadAll()` auto-discovers the block. No changes to `functions.php` needed. Just `queue()` and `render()` it in a template.

---

## Creating a New UI Block

```php
<?php
// inc/Blocks/Card/Card.php

declare(strict_types=1);

namespace TAW\Blocks\Card;

use TAW\Core\Block;

class Card extends Block
{
    protected string $id = 'card';

    protected function defaults(): array
    {
        return [
            'title'       => '',
            'description' => '',
            'image_url'   => '',
        ];
    }
}
```

Usage in any template:
```php
<?php (new TAW\Blocks\Card\Card())->render([
    'title'       => 'My Card',
    'description' => 'Card content here',
]); ?>
```

---

## The Metabox Framework

Located in `inc/Core/Metabox.php` (namespace `TAW\Core\Metabox\Metabox`). Configuration-driven, supports:

**Field types:** `text`, `textarea`, `wysiwyg`, `url`, `number`, `select`, `image`, `group`

**Features:**
- `show_on` callback for conditional display (e.g., front page only)
- `tabs` for grouped field organization
- `width` property for side-by-side fields (e.g., `'width' => '50'`)
- `sanitize` => `'code'` for raw code snippet fields
- `group` type for nested field groups (e.g., CTA with text + URL)

**Meta key pattern:** `_taw_{field_id}` (prefix configurable, default `_taw_`)

**Static helpers:**
```php
Metabox::get(int $postId, string $fieldId, string $prefix = '_taw_'): mixed
Metabox::get_image_url(int $postId, string $fieldId, string $size = 'full'): string
```

---

## Vite Integration

### How it works

`inc/vite-loader.php` detects whether Vite dev server is running via `fsockopen()` on port 5173.

- **Dev:** Assets served from `http://localhost:5173` with HMR
- **Prod:** Reads `public/build/manifest.json` for hashed filenames

### Block assets in Vite

`vite.config.js` auto-discovers block assets:
```js
const componentAssets = readdirSync('inc/Blocks', { recursive: true })
    .filter(f => f.endsWith('style.css') || f.endsWith('style.scss') || f.endsWith('script.js'))
    .map(f => `inc/Blocks/${f}`);
```

These become separate Rollup entry points → separate cached files in production.

### Script type="module"

The `script_loader_tag` filter in `vite-loader.php` adds `type="module"` to:
- All scripts from `VITE_SERVER` (dev)
- `theme-app` and `taw-block-*` handles (prod)

---

## Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| WordPress | 6.0+ | CMS |
| PHP | 7.4+ | Server-side |
| Tailwind CSS | v4 | Utility-first CSS (via `@tailwindcss/vite`) |
| Alpine.js | v3 | Lightweight JS reactivity |
| Vite | v7 | Build tool + HMR |
| SCSS | via `sass` | Optional per-block or global styles |
| Composer | v2 | PSR-4 autoloading (`TAW\` → `inc/`) |

---

## PSR-4 Autoloading

Defined in `composer.json`:
```json
{
    "autoload": {
        "psr-4": {
            "TAW\\": "inc/"
        }
    }
}
```

Namespace mapping:
- `TAW\Core\BaseBlock` → `inc/Core/BaseBlock.php`
- `TAW\Core\MetaBlock` → `inc/Core/MetaBlock.php`
- `TAW\Core\Block` → `inc/Core/Block.php`
- `TAW\Core\BlockRegistry` → `inc/Core/BlockRegistry.php`
- `TAW\Core\BlockLoader` → `inc/Core/BlockLoader.php`
- `TAW\Core\Metabox\Metabox` → `inc/Core/Metabox.php`
- `TAW\Blocks\Hero\Hero` → `inc/Blocks/Hero/Hero.php`

After adding new classes, run `composer dump-autoload` if autoloading fails.

---

## Commands

| Command | Description |
|---|---|
| `npm run dev` | Start Vite dev server (port 5173) with HMR |
| `npm run build` | Production build → `public/build/` |
| `composer install` | Install PHP dependencies |
| `composer dump-autoload` | Rebuild autoload classmap |

---

## Common Patterns

### Conditional block loading per template
```php
// front-page.php
BlockRegistry::queue('hero', 'features', 'testimonials', 'cta');

// single.php
BlockRegistry::queue('post-header', 'related-posts');

// archive.php — maybe no custom blocks needed
```

### Accessing meta in MetaBlock::getData()
```php
protected function getData(int $postId): array
{
    return [
        'heading'   => $this->getMeta($postId, 'my_heading'),
        'image_url' => $this->getImageUrl($postId, 'my_image', 'large'),
    ];
}
```

### Nesting UI blocks inside MetaBlocks
```php
<!-- inc/Blocks/Hero/index.php -->
<section class="hero">
    <h1><?php echo esc_html($heading); ?></h1>
    <?php if ($cta_text): ?>
        <?php (new \TAW\Blocks\Button\Button())->render([
            'text' => $cta_text,
            'url'  => $cta_url,
        ]); ?>
    <?php endif; ?>
</section>
```

---

## Do NOT

- Put block logic in `functions.php` — it belongs in the block class
- Manually register blocks in `functions.php` — `BlockLoader::loadAll()` handles it
- Call `wp_enqueue_style/script` directly for blocks — the base class handles it
- Create blocks with mismatched folder/class names — auto-discovery will skip them
- Forget to `queue()` blocks before `get_header()` — assets will fall back to inline (suboptimal)
