# CLAUDE.md — Claude Code Instructions

> Full architecture docs: see `AGENTS.md` in this repo.

## Project

TAW Theme — a classic WordPress theme with a component-based block system, Vite, Tailwind v4, Alpine.js, and a bespoke metabox framework.

## Commands

```bash
npm run dev          # Vite dev server (port 5173, HMR)
npm run build        # Production build → public/build/
composer install     # PHP deps
composer dump-autoload  # Rebuild classmap after new classes
```

## Core Architecture

Framework internals live in `inc/Core/` (namespace `TAW\Core`): `BaseBlock`, `Block`, `MetaBlock`, `BlockLoader`, `BlockRegistry`, `Metabox`.

Dev blocks live in `inc/Blocks/{Name}/{Name}.php` with namespace `TAW\Blocks\{Name}\{Name}`.

Two block types:
- **MetaBlock** — owns metaboxes, fetches post_meta, rendered via `BlockRegistry::render('id')`
- **Block** — presentational, receives props, rendered directly: `(new Button())->render([...])`

Auto-discovery: `BlockLoader::loadAll()` scans `inc/Blocks/*/` — no manual registration needed.

Asset loading: `BlockRegistry::queue('hero', 'stats')` BEFORE `get_header()` → assets land in `<head>`. Fallback prints inline if forgotten.

## CSS / Asset Pipeline

- `resources/js/app.js` imports `../css/app.css` (Tailwind v4) and `../scss/app.scss` (custom SCSS)
- `resources/scss/critical.scss` — standalone Vite entry, inlined in `<head>` — keep under ~14 KB, **no `@font-face`**
- Self-hosted fonts: WOFF2 in `resources/fonts/`, `@font-face` in `resources/scss/_fonts.scss`, `@use 'fonts'` in `app.scss` only
- Add font preloads in `inc/performance.php` via `vite_asset_url('resources/fonts/Name.woff2')`
- `inc/vite-loader.php` handles all CSS loading (critical inline + async main + preloads)

## Key Conventions

- Folder name === class name === `$id` property
- Meta keys: `_taw_{field_id}`
- Block assets: `style.css` (or `.scss`) and `script.js` — auto-enqueued
- Templates: `index.php` receives `extract()`-ed variables from `getData()`
- PSR-4: `TAW\` → `inc/` (so `TAW\Core\*` → `inc/Core/`, `TAW\Blocks\{Name}\{Name}` → `inc/Blocks/{Name}/{Name}.php`)

## When Creating New Blocks

1. Create `inc/Blocks/{Name}/{Name}.php` — use `use TAW\Core\MetaBlock;` or `use TAW\Core\Block;`
2. Create `inc/Blocks/{Name}/index.php` template
3. Optionally add `style.css`/`style.scss` and `script.js`
4. That's it — auto-discovered, no `functions.php` changes

## Don't

- Don't manually register blocks in functions.php
- Don't call wp_enqueue_style/script for block assets directly
- Don't mismatch folder/class names (breaks auto-discovery)
- Don't forget `queue()` before `get_header()` in templates
- Don't add `@font-face` to `critical.scss` — inlined CSS can't resolve relative asset paths
- Don't add `resources/css/app.css` as a Vite entry — it's imported by `app.js`
