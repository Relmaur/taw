# TAW Theme

**A modern WordPress theme framework that makes building custom pages feel like assembling components — not fighting WordPress.**

TAW (Tailwind + Alpine + WordPress) gives you a clean, component-based block architecture on top of classic WordPress. Every section of a page — hero, stats, testimonials — is a self-contained block that owns its data, markup, styles, and scripts. Only the assets a page actually uses get loaded.

No Gutenberg blocks. No ACF dependency. No bloat. Just PHP classes, templates, and a convention that works.

---

## Why TAW?

**Zero-config blocks.** Create a folder, drop in a class and a template — it's live. No registration, no `functions.php` edits, no build step required for new blocks.

**Scoped asset loading.** Each block can ship its own CSS and JS. Assets are only enqueued on pages that use that block. Your homepage doesn't load your blog's scripts.

**A real data layer.** MetaBlocks own their data through a bespoke metabox framework. No plugin dependencies for custom fields — field registration, rendering, and retrieval are built in.

**Modern frontend, classic WordPress.** Tailwind v4 for utility CSS, Alpine.js for interactivity, Vite for instant HMR — all wired into WordPress through a lightweight bridge. No React, no REST API overhead.

**AI-native DX.** Ships with `AGENTS.md`, `CLAUDE.md`, and Copilot/Windsurf instructions so any AI coding assistant understands the architecture out of the box.

---

## Quick Start

```bash
cd wp-content/themes/taw-theme

composer install       # PHP autoloader
npm install            # Frontend dependencies
npm run dev            # Vite dev server with HMR
```

Activate the theme in WordPress admin. You're building.

---

## Create a Block in 60 Seconds

Every block is a folder inside `inc/Blocks/`. The folder name **must** match the class name — that's the only rule.

### 1. The Class

```php
// inc/Blocks/Hero/Hero.php

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

### 2. The Template

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

### 3. Use It

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

| | MetaBlock | Block |
|---|---|---|
| **Purpose** | Page sections that own their data | Reusable UI components |
| **Data source** | Metaboxes → `post_meta` | Props passed at render time |
| **Rendered via** | `BlockRegistry::render('id')` | `(new Button())->render([...])` |
| **Examples** | Hero, Stats, Testimonials, CTA | Button, Card, Badge |

### Nesting Blocks

UI Blocks compose naturally inside MetaBlocks:

```php
<!-- inc/Blocks/Hero/index.php -->
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

## Tech Stack

| Technology | Role |
|---|---|
| [Tailwind CSS v4](https://tailwindcss.com/) | Utility-first CSS via the official Vite plugin |
| [Alpine.js v3](https://alpinejs.dev/) | Lightweight reactivity for interactive components |
| [Vite v7](https://vitejs.dev/) | Build tool with instant HMR in development |
| [SCSS](https://sass-lang.com/) | Optional custom styles — global and per-block |
| PHP 7.4+ | PSR-4 autoloading via Composer |

### Architecture at a Glance

| Concept | Implementation |
|---|---|
| Autoloading | PSR-4 via Composer (`TAW\` → `inc/`) |
| Block system | `BaseBlock` → `MetaBlock` / `Block` class hierarchy |
| Metaboxes | Bespoke config-driven framework (`inc/Core/Metabox.php`) |
| Asset pipeline | `inc/vite-loader.php` + `BlockRegistry` queue system |
| Critical CSS | `critical.scss` compiled and inlined in `<head>` |
| Fonts | Self-hosted WOFF2 with preloads via `inc/performance.php` |

---

## Project Structure

```
taw-theme/
├── inc/
│   ├── Core/                  # Framework internals (namespace TAW\Core)
│   │   ├── BaseBlock.php      #   Abstract base — asset loading, template rendering
│   │   ├── MetaBlock.php      #   Data-owning blocks (metaboxes + post_meta)
│   │   ├── Block.php          #   Presentational blocks (receives props)
│   │   ├── BlockRegistry.php  #   Static registry — queue, enqueue, render
│   │   ├── BlockLoader.php    #   Auto-discovers blocks by scanning inc/Blocks/
│   │   └── Metabox.php        #   Config-driven metabox framework
│   ├── Blocks/                # One folder per block — auto-discovered
│   ├── vite-loader.php        # Vite ↔ WordPress bridge
│   └── performance.php        # Resource hints, preloads, WP bloat removal
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
├── composer.json              # PHP deps + PSR-4 autoloading
├── package.json               # Node deps + scripts
└── AGENTS.md                  # AI agent architecture docs
```

---

## Requirements

| Dependency | Version |
|---|---|
| WordPress | 6.0+ |
| PHP | 7.4+ |
| Composer | 2.0+ |
| Node.js | 20.19+ |
| npm | 8+ |

---

## Commands

| Command | Description |
|---|---|
| `npm run dev` | Start Vite dev server (port 5173) with HMR |
| `npm run build` | Production build → `public/build/` with hashed filenames |
| `composer install` | Install PHP dependencies |
| `composer dump-autoload` | Rebuild PSR-4 classmap after adding new classes |

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