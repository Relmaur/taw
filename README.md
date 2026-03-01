# TAW Theme

**A modern WordPress theme framework that makes building custom pages feel like assembling components — not fighting WordPress.**

TAW (Tailwind + Alpine + WordPress) gives you a clean, component-based block architecture on top of classic WordPress. Every section of a page — hero, stats, testimonials — is a self-contained block that owns its data, markup, styles, and scripts. Only the assets a page actually uses get loaded.

No Gutenberg blocks. No ACF dependency. No bloat. Just PHP classes, templates, and a convention that works.

The framework internals (block system, metabox engine, Vite bridge) ship as the **[`taw/core`](https://github.com/Relmaur/taw-core) composer package** — versioned independently so you can update the framework across all your TAW sites with a single `composer update taw/core`.

---

## Why TAW?

**Zero-config blocks.** Create a folder, drop in a class and a template — it's live. No registration, no `functions.php` edits, no build step required for new blocks.

**CLI scaffolding.** `php bin/taw make:block MyBlock --type=meta --with-style` creates the folder, class, template, and stylesheet in one command. Export and import blocks between projects as portable ZIPs.

**Scoped asset loading.** Each block can ship its own CSS and JS. Assets are only enqueued on pages that use that block. Your homepage doesn't load your blog's scripts.

**A real data layer.** MetaBlocks own their data through a bespoke metabox framework. No plugin dependencies for custom fields — field registration, rendering, and retrieval are built in. Supports `text`, `textarea`, `wysiwyg`, `image`, `select`, `group`, `repeater`, `post_selector`, `color`, `checkbox`, and more.

**Theme-level options.** `OptionsPage` brings the same config-driven field experience to site-wide settings stored in `wp_options` — tabbed UI, validation, and a clean retrieval API included.

**Modern frontend, classic WordPress.** Tailwind v4 for utility CSS, Alpine.js for interactivity, Vite for instant HMR — all wired into WordPress through a lightweight bridge. No React, no REST API overhead.

**AI-native DX.** Ships with `AGENTS.md`, `CLAUDE.md`, and Copilot/Windsurf instructions so any AI coding assistant understands the architecture out of the box.

---

## Quick Start

```bash

# Move to themes directory of your WordPress installation
cd wp-content/themes/

# This command will create the starter theme with the correct structure and dependencies. Replace <theme_name> with your desired theme folder name.
composer create-project taw/theme <theme_name>  --repository='{"type":"vcs","url":"https://github.com/Relmaur/taw-theme"}'

composer install       # PHP deps — pulls taw/core framework package
npm install            # Frontend dependencies
npm run dev            # Vite dev server with HMR
```

Activate the theme in WordPress admin. You're building.

---

## Create a Block in 10 Seconds

Every block is a folder inside `Blocks/`. The folder name **must** match the class name — that's the only rule.

### Via CLI (recommended)

```bash
php bin/taw make:block Hero --type=meta --with-style
composer dump-autoload
```

### Manually

#### 1. The Class

```php
// Blocks/Hero/Hero.php

namespace TAW\Blocks\Hero;

use TAW\Core\MetaBlock;
use TAW\Core\Metabox\Metabox;

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

#### 2. The Template

```php
<!-- Blocks/Hero/index.php -->

<?php if (empty($heading)) return; ?>

<section class="hero">
    <h1><?php echo esc_html($heading); ?></h1>
    <?php if ($image_url): ?>
        <img src="<?php echo esc_url($image_url); ?>" alt="">
    <?php endif; ?>
</section>
```

#### 3. Use It

```php
<?php
// front-page.php
use TAW\Core\BlockRegistry;

BlockRegistry::queue('hero');
get_header();
?>

<?php BlockRegistry::render('hero'); ?>

<?php get_footer(); ?>
```

That's it. No registration step. The block auto-discovers itself, its metabox appears in the editor, and its assets load only where it's used.

---

## Two Types of Blocks

|                  | MetaBlock                         | Block                           |
| ---------------- | --------------------------------- | ------------------------------- |
| **Purpose**      | Page sections that own their data | Reusable UI components          |
| **Data source**  | Metaboxes → `post_meta`           | Props passed at render time     |
| **Rendered via** | `BlockRegistry::render('id')`     | `(new Button())->render([...])` |
| **Examples**     | Hero, Stats, Testimonials, CTA    | Button, Card, Badge             |

### Nesting Blocks

UI Blocks compose naturally inside MetaBlocks:

```php
<!-- Blocks/Hero/index.php -->
<section class="hero">
    <h1><?php echo esc_html($heading); ?></h1>

    <?php (new \TAW\Blocks\Button\Button())->render([
        'text' => 'Get Started',
        'url'  => '/contact',
    ]); ?>
</section>
```

---

## Template Patterns

### Multi-section homepage

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

### Standard page (no custom blocks)

```php
<?php
// page.php
get_header();
?>

<article>
    <?php the_content(); ?>
</article>

<?php get_footer(); ?>
```

---

## Theme Options

`OptionsPage` provides site-wide settings stored in `wp_options` using the same field config format as metaboxes. Configured in `inc/options.php`.

```php
// Reading options anywhere in the theme
use TAW\Core\OptionsPage;

$phone = OptionsPage::get('company_phone');
$logo  = OptionsPage::get_image_url('company_logo', 'medium');
```

---

## Navigation Menus

`Menu::get()` wraps WordPress nav menus into a typed tree — giving you full control over markup without `wp_nav_menu()`.

```php
use TAW\Core\Menu\Menu;

$menu = Menu::get('primary');
if ($menu && $menu->hasItems()) {
    foreach ($menu->items() as $item) {
        // $item->title(), $item->url(), $item->isActive(), $item->hasChildren() ...
    }
}
```

---

## Performance-Optimised Images

`TAW\Helpers\Image` generates `<img>` tags with the correct `loading`, `fetchpriority`, `decoding`, and `srcset` attributes based on whether the image is above or below the fold.

```php
use TAW\Helpers\Image;

// Above-the-fold hero (eager, high priority)
echo Image::render($hero_id, 'full', 'Hero image', ['above_fold' => true]);

// Regular image (lazy, low priority — the default)
echo Image::render(get_post_thumbnail_id(), 'large', 'Post thumbnail');
```

---

## Tech Stack

| Technology                                                                 | Role                                                             |
| -------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| [Tailwind CSS v4](https://tailwindcss.com/)                                | Utility-first CSS via the official Vite plugin                   |
| [Alpine.js v3](https://alpinejs.dev/)                                      | Lightweight reactivity for interactive components                |
| [Vite v7](https://vitejs.dev/)                                             | Build tool with instant HMR in development                       |
| [SCSS](https://sass-lang.com/)                                             | Optional custom styles — global and per-block                    |
| [Symfony Console](https://symfony.com/doc/current/components/console.html) | CLI scaffolding commands (`bin/taw`) — shipped inside `taw/core` |
| PHP 8.1+                                                                   | PSR-4 autoloading via Composer                                   |
| [`taw/core`](https://github.com/Relmaur/taw-core)                          | Versioned composer package containing all framework internals    |

### Architecture at a Glance

| Concept           | Implementation                                                                          |
| ----------------- | --------------------------------------------------------------------------------------- |
| Autoloading       | PSR-4 via Composer — `TAW\Blocks\` → `Blocks/` (theme); everything else from `taw/core` |
| Block system      | `BaseBlock` → `MetaBlock` / `Block` class hierarchy (in `taw/core`)                     |
| Metaboxes         | Bespoke config-driven framework (`TAW\Core\Metabox\Metabox` in `taw/core`)              |
| Options page      | Config-driven `OptionsPage` — stores to `wp_options` (in `taw/core`)                    |
| Navigation menus  | `Menu` / `MenuItem` typed tree (`TAW\Core\Menu` in `taw/core`)                          |
| REST API          | `taw/v1/search-posts` endpoint (`TAW\Core\Rest` in `taw/core`)                          |
| Asset pipeline    | `vite-loader.php` (autoloaded from `taw/core`) + `BlockRegistry` queue system           |
| Critical CSS      | `critical.scss` compiled and inlined in `<head>`                                        |
| Fonts             | Self-hosted WOFF2 with preloads via `performance.php` (autoloaded from `taw/core`)      |
| Theme updates     | GitHub Releases-based auto-updater (`TAW\Core\ThemeUpdater` in `taw/core`)              |
| Framework updates | `composer update taw/core` — update across all sites independently                      |

---

## Project Structure

```
taw-theme/
├── bin/
│   └── taw                    # CLI entry point (Symfony Console — delegates to taw/core)
├── Blocks/                    # Your blocks — one folder per block, auto-discovered
│   └── Hero/
│       ├── Hero.php           #   class TAW\Blocks\Hero\Hero extends MetaBlock
│       ├── index.php          #   Template
│       └── style.scss         #   Optional per-block styles
├── inc/
│   └── options.php            # Theme options page configuration
├── vendor/
│   └── taw/
│       └── core/              # ← Framework internals (managed via composer)
│           └── src/
│               ├── Core/      #   BaseBlock, MetaBlock, Block, BlockRegistry, etc.
│               ├── Helpers/   #   Image helper
│               ├── CLI/       #   make:block, export:block, import:block commands
│               └── Support/   #   vite-loader.php, performance.php (autoloaded)
├── resources/
│   ├── css/app.css            # Tailwind v4 directives
│   ├── scss/
│   │   ├── app.scss           # Global custom SCSS
│   │   ├── critical.scss      # Above-the-fold CSS (inlined in <head>)
│   │   └── _fonts.scss        # @font-face declarations
│   ├── fonts/                 # Self-hosted WOFF2 files
│   └── js/app.js              # Alpine.js + global JS entry point
├── public/build/              # Compiled assets (gitignored)
├── functions.php              # Theme bootstrap (minimal)
├── vite.config.js             # Vite configuration
├── composer.json              # PHP deps — TAW\Blocks\ → Blocks/, requires taw/core
├── package.json               # Node deps + scripts
└── AGENTS.md                  # AI agent architecture docs
```

---

## Requirements

| Dependency | Version |
| ---------- | ------- |
| WordPress  | 6.0+    |
| PHP        | 8.1+    |
| Composer   | 2.0+    |
| Node.js    | 20.19+  |
| npm        | 8+      |

---

## Commands

| Command                             | Description                                              |
| ----------------------------------- | -------------------------------------------------------- |
| `npm run dev`                       | Start Vite dev server (port 5173) with HMR               |
| `npm run build`                     | Production build → `public/build/` with hashed filenames |
| `composer install`                  | Install PHP dependencies (including `taw/core`)          |
| `composer update taw/core`          | Pull the latest framework update                         |
| `composer dump-autoload`            | Rebuild PSR-4 classmap after adding new block classes    |
| `php bin/taw make:block Name`       | Scaffold a new block (interactive if no flags)           |
| `php bin/taw export:block Name`     | Export a block as a portable ZIP                         |
| `php bin/taw import:block path.zip` | Import a block from a ZIP                                |

---

## AI-Friendly

This repo ships with architecture documentation for AI coding assistants:

- **`AGENTS.md`** — comprehensive architecture guide (Claude Code, Cursor, generic agents)
- **`CLAUDE.md`** — Claude Code-specific instructions
- **`.github/copilot-instructions.md`** — GitHub Copilot instructions
- **`.windsurfrules`** — Windsurf/Codeium instructions

Any LLM-powered tool will automatically pick up the project's conventions, naming patterns, and anti-patterns. Point your AI at the repo and start building.

---

## License

GPL v2. See [LICENSE.txt](LICENSE.txt) for details.
