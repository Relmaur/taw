# CLAUDE.md — Claude Code Instructions

> Full architecture docs: see `AGENTS.md` in this repo.

## Project

TAW Theme — a classic WordPress theme with a component-based block system, Vite, Tailwind v4, Alpine.js, and a bespoke metabox framework.

## Commands

```bash
npm run dev              # Vite dev server (port 5173, HMR)
npm run build            # Production build → public/build/
composer install         # PHP deps
composer dump-autoload   # Rebuild classmap after new classes
php bin/taw make:block Name --type=meta --with-style  # Scaffold a new block
php bin/taw export:block Name                         # Export block as ZIP
php bin/taw import:block path/to/Block.zip            # Import block from ZIP
```

## Core Architecture

Framework internals live in `inc/Core/` (namespace `TAW\Core`): `BaseBlock`, `Block`, `MetaBlock`, `BlockLoader`, `BlockRegistry`, `Metabox\Metabox`, `OptionsPage`, `ThemeUpdater`, `Menu\Menu`, `Menu\MenuItem`, `Rest\SearchEndpoints`.

Dev blocks live in `inc/Blocks/{Name}/{Name}.php` with namespace `TAW\Blocks\{Name}\{Name}`.

Two block types:
- **MetaBlock** — owns metaboxes, fetches post_meta, rendered via `BlockRegistry::render('id')`
- **Block** — presentational, receives props, rendered directly: `(new Button())->render([...])`

Auto-discovery: `BlockLoader::loadAll()` scans `inc/Blocks/*/` — no manual registration needed.

Asset loading: `BlockRegistry::queue('hero', 'stats')` BEFORE `get_header()` → assets land in `<head>`. Fallback prints inline if forgotten.

## Options Page

`inc/Core/OptionsPage.php` — same field config format as Metabox but stores to `wp_options`.

```php
new OptionsPage(['id' => 'taw_settings', 'title' => 'TAW Settings', 'fields' => [...]]);
OptionsPage::get('company_phone');           // retrieve a value
OptionsPage::get_image_url('logo', 'large'); // retrieve an image URL
```

Theme options configured in `inc/options.php`, required from `functions.php`.

## Navigation Menus

`inc/Core/Menu/Menu.php` — typed tree wrapper for WP nav menus. Use instead of `wp_nav_menu()`.

```php
$menu = TAW\Core\Menu\Menu::get('primary');
foreach ($menu->items() as $item) {
    // $item->title(), $item->url(), $item->isActive(), $item->hasChildren(), ...
}
```

Menus (`primary`, `footer`) are registered in `functions.php` via `register_nav_menus()`.

## Helpers

`inc/Helpers/Image.php` — performance-optimised `<img>` tag generator.

```php
echo TAW\Helpers\Image::render($id, 'large', 'Alt text');
echo TAW\Helpers\Image::render($id, 'full', 'Hero', ['above_fold' => true]);
echo TAW\Helpers\Image::preload_tag($id, 'full'); // <link rel="preload">
```

## REST API

`inc/Core/Rest/SearchEndpoints.php` — `GET taw/v1/search-posts`. Requires `edit_posts` capability. Powers the `post_selector` metabox field type. Registered automatically in `functions.php`.

## CSS / Asset Pipeline

- `resources/js/app.js` imports `../css/app.css` (Tailwind v4) and `../scss/app.scss` (custom SCSS)
- `resources/scss/critical.scss` — standalone Vite entry, inlined in `<head>` — keep under ~14 KB, **no `@font-face`**
- Self-hosted fonts: WOFF2 in `resources/fonts/`, `@font-face` in `resources/scss/_fonts.scss`, `@use 'fonts'` in `app.scss` only
- Add font preloads in `inc/performance.php` via `vite_asset_url('resources/fonts/Name.woff2')`
- `inc/vite-loader.php` handles all CSS loading (critical inline + async main + preloads)

## Key Conventions

- Folder name === class name === `$id` property
- Meta keys: `_taw_{field_id}`, option keys: `_taw_{field_id}`
- Block assets: `style.css` (or `.scss`) and `script.js` — auto-enqueued
- Templates: `index.php` receives `extract()`-ed variables from `getData()`
- PSR-4: `TAW\` → `inc/` (so `TAW\Core\*` → `inc/Core/`, `TAW\Blocks\{Name}\{Name}` → `inc/Blocks/{Name}/{Name}.php`)

## Metabox Field Types

`text`, `textarea`, `wysiwyg`, `url`, `number`, `select`, `image`, `group`, `checkbox`, `color`, `repeater`, `post_selector`

## When Creating New Blocks

1. **CLI (preferred):** `php bin/taw make:block Name --type=meta --with-style`, then `composer dump-autoload`
2. **Manual:** Create `inc/Blocks/{Name}/{Name}.php` and `inc/Blocks/{Name}/index.php` — auto-discovered, no `functions.php` changes

## Don't

- Don't manually register blocks in functions.php
- Don't call wp_enqueue_style/script for block assets directly
- Don't mismatch folder/class names (breaks auto-discovery)
- Don't forget `queue()` before `get_header()` in templates
- Don't add `@font-face` to `critical.scss` — inlined CSS can't resolve relative asset paths
- Don't add `resources/css/app.css` as a Vite entry — it's imported by `app.js`
- Don't use `wp_nav_menu()` — use `Menu::get('location')` for full markup control
