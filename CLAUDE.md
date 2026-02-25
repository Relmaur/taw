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

Blocks live in `inc/Blocks/{Name}/{Name}.php` with matching namespace `TAW\Blocks\{Name}\{Name}`.

Two types:
- **MetaBlock** — owns metaboxes, fetches post_meta, rendered via `BlockRegistry::render('id')`
- **Block** — presentational, receives props, rendered directly: `(new Button())->render([...])`

Auto-discovery: `BlockLoader::loadAll()` scans `inc/Blocks/*/` — no manual registration needed.

Asset loading: `BlockRegistry::queue('hero', 'stats')` BEFORE `get_header()` → assets land in `<head>`. Fallback prints inline if forgotten.

## Key Conventions

- Folder name === class name === `$id` property
- Meta keys: `_taw_{field_id}`
- Block assets: `style.css` (or `.scss`) and `script.js` — auto-enqueued
- Templates: `index.php` receives `extract()`-ed variables from `getData()`
- PSR-4: `TAW\` → `inc/`

## When Creating New Blocks

1. Create `inc/Blocks/{Name}/{Name}.php` extending `MetaBlock` or `Block`
2. Create `inc/Blocks/{Name}/index.php` template
3. Optionally add `style.css`/`style.scss` and `script.js`
4. That's it — auto-discovered, no `functions.php` changes

## Don't

- Don't manually register blocks in functions.php
- Don't call wp_enqueue_style/script for block assets directly
- Don't mismatch folder/class names (breaks auto-discovery)
- Don't forget `queue()` before `get_header()` in templates
