# AGENTS.md — AI Agent Guide for TAW Theme

> **TAW** = Tailwind + Alpine + WordPress
> A classic WordPress theme with a component-based block architecture, Vite asset pipeline, and a bespoke metabox framework.

---

## Quick Orientation

| Path | Purpose |
|---|---|
| `inc/Core/` | Framework internals — base classes, registry, loader, metabox engine |
| `inc/Blocks/` | Dev block collection — one folder per block, auto-discovered |
| `inc/vite-loader.php` | Vite ↔ WordPress bridge (dev/prod, CSS pipeline, preloads) |
| `inc/performance.php` | Resource hints, font preloads, WP frontend bloat removal |
| `resources/js/app.js` | Alpine.js + global JS — imports Tailwind CSS and custom SCSS |
| `resources/css/app.css` | Tailwind v4 directives (`@import "tailwindcss"`) — imported by `app.js` |
| `resources/scss/app.scss` | Global custom SCSS (fonts, overrides) — imported by `app.js` |
| `resources/scss/critical.scss` | Above-the-fold CSS — compiled and inlined in `<head>` |
| `resources/scss/_fonts.scss` | `@font-face` declarations for self-hosted fonts |
| `resources/fonts/` | Self-hosted WOFF2 font files |
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

`inc/vite-loader.php` detects whether the Vite dev server is running via `fsockopen()` on port 5173.

- **Dev:** `app.js` (+ its CSS imports) served from `http://localhost:5173` with HMR
- **Prod:** Reads `public/build/manifest.json` for hashed filenames

### CSS loading pipeline (production)

CSS is loaded in three layers to maximise paint speed:

```
1. critical.scss  → compiled → inlined as <style> in <head>   (zero network request)
2. app.js CSS     → <link rel="preload"> + async <link media="print" onload="...">
3. app.scss CSS   → same async pattern (deduped if same compiled file)
```

The async `media="print"` trick makes stylesheets non-render-blocking — the browser downloads them in the background and swaps them in when ready. A `<noscript>` fallback covers JS-disabled users.

### CSS entry points

| File | How loaded |
|---|---|
| `resources/js/app.js` | Vite JS entry. Imports `app.css` + `app.scss` — both compile into the JS entry's CSS output. In dev this is the only PHP-loaded script; Vite HMR injects styles automatically. |
| `resources/css/app.css` | Tailwind v4 (`@import "tailwindcss"`, `@source "../../**/*.php"`). Imported by `app.js`, **not** a standalone Vite entry. |
| `resources/scss/app.scss` | Custom SCSS (fonts, global rules). Imported by `app.js`. |
| `resources/scss/critical.scss` | Standalone Vite entry. Inlined into `<head>` by `vite_inline_critical_css()`. Must stay under ~14 KB. No `@font-face` here — inlined CSS resolves `url()` against the page origin, not a stylesheet location, causing 404s. |
| `inc/Blocks/*/style.css` | Per-block styles. Auto-discovered by `vite.config.js`, separate Rollup entries. |

### Key Vite config decisions

```js
base: command === 'build' ? './' : '/'
```
Production uses a relative base so compiled CSS references fonts as `./Roboto-xxx.woff2` — resolves correctly relative to the CSS file regardless of WordPress install path.
Dev uses `'/'` because Vite's HMR and module resolution break with a relative base when scripts are served cross-origin.

```js
server: { origin: 'http://localhost:5173' }
```
Forces Vite to embed the full dev server URL in injected CSS (e.g. `url('http://localhost:5173/resources/fonts/...')`). Without this, Vite writes `/resources/fonts/...` which the browser resolves against the WordPress page origin, causing font 404s.

### Self-hosted fonts

- Place WOFF2 files in `resources/fonts/`
- Declare `@font-face` in `resources/scss/_fonts.scss` with `url('../fonts/Name.woff2')`
- `@use 'fonts'` in `app.scss` (linked CSS) — Vite rewrites the URL correctly
- **Never** `@use 'fonts'` in `critical.scss` — inlined styles can't resolve relative asset paths
- Register preloads in `inc/performance.php` via `vite_asset_url('resources/fonts/Name.woff2')` — this returns the dev server URL in dev and the hashed build URL in prod

### Helper: `vite_asset_url(string $path): string`

Resolves any theme asset to the correct URL in both modes:
```php
vite_asset_url('resources/fonts/Roboto-Regular.woff2')
// Dev  → 'http://localhost:5173/resources/fonts/Roboto-Regular.woff2'
// Prod → 'https://example.com/.../public/build/assets/Roboto-Regular-B51t0g.woff2'
```

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
- `theme-app` and `taw-component-*` handles (prod)

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
- Add `@font-face` / `@use 'fonts'` to `critical.scss` — inlined `<style>` tags resolve `url()` against the page origin, not the stylesheet, causing font 404s on any non-root install
- Add `resources/css/app.css` back as a standalone Vite entry — it is imported by `app.js` and must not be a separate entry or it will compile twice
- Set `base: './'` globally in `vite.config.js` — it must only apply to `build` (dev mode breaks with a relative base in cross-origin setups)
