# Copilot Instructions

> Full architecture docs: see `AGENTS.md` in the repo root.

## Project: TAW Theme

A classic WordPress theme with a custom block system, Vite v7, Tailwind v4, Alpine.js, and a bespoke metabox framework.

## Core Package

Framework internals (`TAW\Core`, `TAW\Helpers`, `TAW\CLI`) live in the **`taw/core` composer package**, installed at `vendor/taw/core/src/`. The theme's own `inc/` only contains `options.php` and Metabox view templates. **Do not look for `TAW\Core` classes in `inc/`**, and do not edit anything inside `vendor/`.

To update the framework: `composer update taw/core`.

## Key Architecture

- Framework classes (`BaseBlock`, `Block`, `MetaBlock`, `BlockLoader`, `BlockRegistry`, `Metabox\Metabox`, `OptionsPage`, `ThemeUpdater`, `Menu\Menu`, `Menu\MenuItem`, `Rest\SearchEndpoints`) — all in `vendor/taw/core/src/Core/` (namespace `TAW\Core`)
- `TAW\Helpers\Image` — in `vendor/taw/core/src/Helpers/`
- `vite-loader.php` and `performance.php` — in `vendor/taw/core/src/Support/`, autoloaded by composer
- Dev blocks: `Blocks/{Name}/{Name}.php` — folder name must match class name, namespace `TAW\Blocks\{Name}\{Name}`
- Theme PSR-4: `TAW\Blocks\` → `Blocks/` only (everything else comes from `taw/core`)
- Two types: **MetaBlock** (data-owning, uses metaboxes) and **Block** (presentational, receives props)
- Auto-discovery via `BlockLoader::loadAll()` — no manual registration
- Asset queueing: `BlockRegistry::queue()` before `get_header()`, then `BlockRegistry::render()` in body

## New Blocks

Scaffold with the CLI: `php bin/taw make:block Name --type=meta --with-style`, then `composer dump-autoload`.
Or manually create `Blocks/{Name}/{Name}.php` + `Blocks/{Name}/index.php` — no other changes needed.

## Options Page

`OptionsPage` (from `taw/core`) stores site-wide settings in `wp_options` with the same field config as Metabox.
- Configured in `inc/options.php`
- Retrieve: `OptionsPage::get('field_id')`, `OptionsPage::get_image_url('field_id', 'size')`

## Navigation Menus

Use `TAW\Core\Menu\Menu::get('location')` instead of `wp_nav_menu()` — returns a typed tree of `MenuItem` objects with full active-state and children support.

## Image Helper

`TAW\Helpers\Image::render($id, 'size', 'alt', ['above_fold' => true])` — generates performance-optimised `<img>` tags with correct `loading`, `fetchpriority`, `decoding`, `srcset`, and `sizes`.

## REST API

`GET taw/v1/search-posts` — registered automatically. Powers the `post_selector` metabox field. Requires `edit_posts` capability.

## CSS / Asset Pipeline

- `resources/js/app.js` imports `../css/app.css` (Tailwind v4) and `../scss/app.scss` (custom SCSS) — neither is a standalone Vite entry
- `resources/scss/critical.scss` is a standalone Vite entry compiled and **inlined** in `<head>` — keep under ~14 KB, no `@font-face` inside it
- Main CSS loads asynchronously (non-render-blocking via `media="print"`) in production
- Self-hosted fonts live in `resources/fonts/`; `@font-face` goes in `resources/scss/_fonts.scss`, used via `@use 'fonts'` in `app.scss` only
- `vite_asset_url()` resolves font/asset paths correctly in both dev and prod (function provided by `taw/core`)
- Add font preloads via `vite_asset_url()` — the helper is autoloaded from `vendor/taw/core/src/Support/vite-loader.php`

## When generating code

- New blocks: extend `TAW\Core\MetaBlock` or `TAW\Core\Block`, follow the naming convention exactly
- Use `use TAW\Core\MetaBlock;`, `use TAW\Core\Block;`, `use TAW\Core\Metabox\Metabox;`, `use TAW\Core\BlockRegistry;`, `use TAW\Core\OptionsPage;`
- Templates: use `esc_html()`, `esc_url()`, `esc_attr()` for all output
- Metabox/OptionsPage field types: `text`, `textarea`, `wysiwyg`, `url`, `number`, `select`, `image`, `group`, `checkbox`, `color`, `repeater`, `post_selector`
- Meta keys follow pattern `_taw_{field_id}`; option keys follow the same pattern
- Styles: Tailwind utilities in templates, custom CSS/SCSS in block's `style.css`/`style.scss`
- Never add block registrations to `functions.php`
- Never use `wp_nav_menu()` — use `Menu::get('location')` instead
- Never edit files inside `vendor/taw/core/` — changes will be lost on the next `composer update`
