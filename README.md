# TAW Theme

**A component-based WordPress theme built with Tailwind CSS, Alpine.js, and Vite.**

TAW ships a custom block architecture where every section of a page — hero, stats, testimonials — is a self-contained block that owns its data, markup, styles, and scripts. Only the assets a page actually uses get loaded.

---

## Requirements

| Dependency | Version |
|------------|---------|
| WordPress  | 6.0+    |
| PHP        | 7.4+    |
| Composer   | 2.0+    |
| Node.js    | 20.19+  |
| npm        | 8+      |

---

## Installation

```bash
# Navigate to the theme directory
cd wp-content/themes/taw-theme

# Install PHP dependencies (autoloader)
composer install

# Install Node dependencies
npm install

# Activate the theme in WordPress admin
```

---

## Development

### Start the dev server

```bash
npm run dev
```

This starts Vite on `http://localhost:5173` with:

- **HMR** for instant style and JS updates
- **Full-page reload** when PHP or Twig files change
- **Tailwind CSS** JIT compilation scanning all `.php` files

### Build for production

```bash
npm run build
```

Compiles and minifies all assets to `public/build/` with hashed filenames and a `manifest.json` for cache-busting.

### Other commands

| Command | Description |
|---------|-------------|
| `composer dump-autoload` | Rebuild PSR-4 classmap after adding new PHP classes |

---

## Project Structure

```
taw-theme/
├── inc/
│   ├── Core/                      # ← Framework internals (namespace TAW\Core)
│   │   ├── BaseBlock.php          #    Abstract base — asset loading, template rendering
│   │   ├── MetaBlock.php          #    Data-owning blocks (metaboxes + post_meta)
│   │   ├── Block.php              #    Presentational blocks (receives props)
│   │   ├── BlockRegistry.php      #    Static registry — queue, enqueue, render
│   │   ├── BlockLoader.php        #    Auto-discovers blocks by scanning inc/Blocks/
│   │   └── Metabox.php            #    Config-driven metabox framework
│   ├── Blocks/                    # ← Dev block collection (one folder per block)
│   │   ├── Hero/                  #    Example MetaBlock
│   │   │   ├── Hero.php           #      Class (metaboxes + data logic)
│   │   │   ├── index.php          #      Template (pure markup)
│   │   │   └── style.css          #      Scoped styles (auto-enqueued)
│   │   └── Button/                #    Example UI Block
│   │       ├── Button.php         #      Class (defaults + props)
│   │       └── index.php          #      Template
│   └── vite-loader.php            # ← Vite ↔ WordPress bridge
├── resources/
│   ├── css/app.css                # Tailwind v4 entry point
│   ├── scss/app.scss              # Global SCSS (non-Tailwind custom styles)
│   └── js/app.js                  # Alpine.js + global JS
├── public/build/                  # Compiled assets (gitignored, auto-generated)
├── functions.php                  # Theme bootstrap (minimal)
├── header.php                     # Global header
├── footer.php                     # Global footer
├── index.php                      # Main template
├── vite.config.js                 # Vite configuration
├── composer.json                  # PHP deps + PSR-4 autoloading
├── package.json                   # Node deps + scripts
├── AGENTS.md                      # AI agent documentation
└── style.css                      # WordPress theme metadata
```

---

## Architecture

### The Block System

Every visual section on a page is a **block** — a self-contained unit with its own class, template, and optional assets.

```
inc/Blocks/{Name}/
├── {Name}.php      ← Class (logic + data)
├── index.php       ← Template (markup)
├── style.css       ← Optional scoped styles (or style.scss)
└── script.js       ← Optional scoped JavaScript
```

There are two types of blocks:

#### MetaBlock — Data-owning sections

These are the building blocks of pages. Each MetaBlock:

- Registers its own metabox fields in the WordPress editor
- Fetches its own data from `post_meta`
- Renders itself with that data

```php
// inc/Blocks/Hero/Hero.php

class Hero extends MetaBlock
{
    protected string $id = 'hero';

    protected function registerMetaboxes(): void
    {
        new Metabox([
            'id'     => 'taw_hero',
            'title'  => 'Hero Section',
            'screen' => 'page',
            'fields' => [
                ['id' => 'hero_heading', 'label' => 'Heading', 'type' => 'text'],
                ['id' => 'hero_image',   'label' => 'Image',   'type' => 'image'],
            ],
        ]);
    }

    protected function getData(int $postId): array
    {
        return [
            'heading'   => $this->getMeta($postId, 'hero_heading'),
            'image_url' => $this->getImageUrl($postId, 'hero_image', 'large'),
        ];
    }
}
```

```php
<!-- inc/Blocks/Hero/index.php -->

<?php if (empty($heading)) return; ?>

<section class="hero">
    <h1><?php echo esc_html($heading); ?></h1>
    <?php if ($image_url): ?>
        <img src="<?php echo esc_url($image_url); ?>" alt="">
    <?php endif; ?>
</section>
```

#### Block — Presentational UI components

These are reusable UI elements (buttons, cards, badges) that receive data as props rather than fetching it:

```php
// inc/Blocks/Button/Button.php

class Button extends Block
{
    protected string $id = 'button';

    protected function defaults(): array
    {
        return [
            'text'    => '',
            'url'     => '#',
            'variant' => 'primary',
        ];
    }
}
```

Used directly in any template:

```php
<?php (new TAW\Blocks\Button\Button())->render([
    'text'    => 'Get Started',
    'url'     => '/contact',
    'variant' => 'secondary',
]); ?>
```

### Auto-Discovery

`BlockLoader::loadAll()` scans `inc/Blocks/*/` at boot and registers every MetaBlock it finds. **You never need to touch `functions.php` when adding new blocks.** Just create the folder and class, and it's live.

The convention is strict: the folder name **must** match the class name.

```
inc/Blocks/Hero/Hero.php       ✅  Auto-discovered
inc/Blocks/hero/Hero.php       ❌  Folder/class mismatch
inc/Blocks/HeroSection/Hero.php ❌  Folder/class mismatch
```

### Conditional Asset Loading

Block stylesheets and scripts are only loaded on pages that use them. This is managed through a queue pattern:

```php
<?php
use TAW\Core\BlockRegistry;

// 1. Declare which blocks this page needs (BEFORE get_header)
BlockRegistry::queue('hero', 'stats', 'cta');

// 2. get_header() fires wp_head — queued assets land in <head>
get_header();
?>

<!-- 3. Render blocks in the body -->
<?php BlockRegistry::render('hero'); ?>
<?php BlockRegistry::render('stats'); ?>
<?php BlockRegistry::render('cta'); ?>

<?php get_footer(); ?>
```

The `queue()` call **must** come before `get_header()` so stylesheets end up in `<head>`. If you forget, a safety fallback prints them inline — it works, but may cause a flash of unstyled content.

---

## Creating a New Block

### MetaBlock (page section with admin fields)

**1. Create the class:**

```php
<?php
// inc/Blocks/Features/Features.php

declare(strict_types=1);

namespace TAW\Blocks\Features;

use TAW\Core\MetaBlock;
use TAW\Core\Metabox;

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
                ['id' => 'features_heading',     'label' => 'Heading',     'type' => 'text'],
                ['id' => 'features_description', 'label' => 'Description', 'type' => 'textarea'],
            ],
        ]);
    }

    protected function getData(int $postId): array
    {
        return [
            'heading'     => $this->getMeta($postId, 'features_heading'),
            'description' => $this->getMeta($postId, 'features_description'),
        ];
    }
}
```

**2. Create the template:**

```php
<?php
// inc/Blocks/Features/index.php

/** @var string $heading */
/** @var string $description */

if (empty($heading)) return;
?>

<section class="features py-20">
    <div class="max-w-7xl mx-auto px-6">
        <h2 class="text-4xl font-bold"><?php echo esc_html($heading); ?></h2>
        <?php if ($description): ?>
            <p class="mt-4 text-lg text-gray-600"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
    </div>
</section>
```

**3. Optionally add `style.css` or `style.scss` and `script.js`.**

**4. Use it in a template:**

```php
<?php
BlockRegistry::queue('features');
get_header();
?>
<?php BlockRegistry::render('features'); ?>
<?php get_footer(); ?>
```

That's it. No registration, no `functions.php` changes, no manual asset wiring.

### UI Block (presentational component)

**1. Create the class:**

```php
<?php
// inc/Blocks/Badge/Badge.php

declare(strict_types=1);

namespace TAW\Blocks\Badge;

use TAW\Core\Block;

class Badge extends Block
{
    protected string $id = 'badge';

    protected function defaults(): array
    {
        return [
            'label'   => '',
            'variant' => 'default', // 'default', 'success', 'warning'
        ];
    }
}
```

**2. Create the template:**

```php
<?php
// inc/Blocks/Badge/index.php

/** @var string $label */
/** @var string $variant */

if (empty($label)) return;
?>

<span class="badge badge--<?php echo esc_attr($variant); ?>">
    <?php echo esc_html($label); ?>
</span>
```

**3. Use anywhere:**

```php
<?php (new TAW\Blocks\Badge\Badge())->render(['label' => 'New', 'variant' => 'success']); ?>
```

---

## The Metabox Framework

The theme includes a bespoke, configuration-driven metabox framework at `inc/Core/Metabox.php` (namespace `TAW\Core\Metabox`). No plugins needed.

### Supported field types

| Type | Description |
|------|-------------|
| `text` | Single-line text input |
| `textarea` | Multi-line text (supports `rows` option) |
| `wysiwyg` | WordPress rich text editor |
| `url` | URL input with validation |
| `number` | Numeric input (supports `min`, `max`, `step`) |
| `select` | Dropdown (provide `options` array) |
| `image` | WordPress media picker |
| `group` | Nested field group (e.g., CTA with text + URL) |

### Features

- **Tabs** — Group fields into tabbed sections with optional icons
- **Conditional display** — `show_on` callback to show metabox only on specific pages
- **Field widths** — `'width' => '50'` for side-by-side layout
- **Code sanitization** — `'sanitize' => 'code'` preserves raw HTML/code for trusted users

### Configuration example

```php
new Metabox([
    'id'       => 'taw_hero',
    'title'    => 'Hero Section',
    'screen'   => 'page',
    'show_on'  => function (\WP_Post $post): bool {
        $front = absint(get_option('page_on_front'));
        return $front === 0 || $post->ID === $front;
    },
    'tabs' => [
        ['id' => 'content', 'label' => 'Content', 'fields' => ['hero_heading', 'hero_tagline']],
        ['id' => 'style',   'label' => 'Style',   'fields' => ['hero_image']],
    ],
    'fields' => [
        ['id' => 'hero_heading', 'label' => 'Heading', 'type' => 'text', 'width' => '50'],
        ['id' => 'hero_tagline', 'label' => 'Tagline', 'type' => 'text', 'width' => '50'],
        ['id' => 'hero_image',   'label' => 'Image',   'type' => 'image'],
    ],
]);
```

### Retrieving data

```php
// In a MetaBlock's getData() method:
$this->getMeta($postId, 'hero_heading');           // Returns string
$this->getImageUrl($postId, 'hero_image', 'large'); // Returns URL string

// Anywhere else (static):
Metabox::get($postId, 'hero_heading');
Metabox::get_image_url($postId, 'hero_image');
```

All meta keys are stored as `_taw_{field_id}` by default.

---

## Vite Integration

### How it works

`inc/vite-loader.php` checks whether the Vite dev server is running on port 5173:

- **Dev mode:** Assets served directly from Vite with HMR. Styles update instantly, JS hot-reloads, PHP changes trigger a full page refresh.
- **Production:** Reads `public/build/manifest.json` and enqueues hashed, minified files.

### What gets compiled

| Entry point | Purpose |
|---|---|
| `resources/css/app.css` | Tailwind v4 (scans all `.php` files for classes) |
| `resources/scss/app.scss` | Global custom SCSS |
| `resources/js/app.js` | Alpine.js + global JS |
| `inc/Blocks/*/style.css` | Per-block styles (auto-discovered) |
| `inc/Blocks/*/style.scss` | Per-block SCSS (auto-discovered, prioritized over .css) |
| `inc/Blocks/*/script.js` | Per-block scripts (auto-discovered) |

Block assets are auto-discovered by `vite.config.js` at build time — adding a `style.css` or `script.js` to a block folder is all you need.

---

## Tech Stack

| Technology | Purpose |
|---|---|
| [Tailwind CSS v4](https://tailwindcss.com/) | Utility-first CSS via `@tailwindcss/vite` |
| [Alpine.js v3](https://alpinejs.dev/) | Lightweight JS reactivity for interactive components |
| [Vite v7](https://vitejs.dev/) | Build tool + HMR dev server |
| [SCSS](https://sass-lang.com/) | Optional custom styles (global and per-block) |

### PHP Architecture

| Concept | Implementation |
|---|---|
| Autoloading | PSR-4 via Composer (`TAW\` → `inc/`) |
| Blocks | Custom class hierarchy (`BaseBlock` → `MetaBlock` / `Block`) |
| Metaboxes | Bespoke framework (`inc/Core/Metabox.php`) |
| Asset pipeline | `inc/vite-loader.php` + `BlockRegistry` queue system |

---

## Template Patterns

### Homepage with multiple sections

```php
<?php
// front-page.php
use TAW\Core\BlockRegistry;

BlockRegistry::queue('hero', 'features', 'stats', 'testimonials', 'cta');
get_header();
?>

<?php BlockRegistry::render('hero'); ?>
<?php BlockRegistry::render('features'); ?>
<?php BlockRegistry::render('stats'); ?>
<?php BlockRegistry::render('testimonials'); ?>
<?php BlockRegistry::render('cta'); ?>

<?php get_footer(); ?>
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

### Minimal page with no custom blocks

```php
<?php
// page.php — no queue() needed if no blocks are used
get_header();
?>

<article>
    <?php the_content(); ?>
</article>

<?php get_footer(); ?>
```

---

## AI-Friendly

This repo includes an `AGENTS.md` file at root with comprehensive architecture documentation for AI coding assistants. Any LLM-powered tool (Claude Code, Cursor, Copilot, Windsurf) will automatically pick up the project's conventions and patterns.

---

## License

MIT License. See LICENSE.txt for details.
