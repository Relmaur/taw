# Copilot Instructions

> Full architecture docs: see `AGENTS.md` in the repo root.

## Project: TAW Theme

A classic WordPress theme with a custom block system, Vite v7, Tailwind v4, Alpine.js, and a bespoke metabox framework.

## Key Architecture

- Framework internals in `inc/Core/` (namespace `TAW\Core`): `BaseBlock`, `Block`, `MetaBlock`, `BlockLoader`, `BlockRegistry`, `Metabox`
- Dev blocks: `inc/Blocks/{Name}/{Name}.php` — folder name must match class name, namespace `TAW\Blocks\{Name}\{Name}`
- Two types: **MetaBlock** (data-owning, uses metaboxes) and **Block** (presentational, receives props)
- Auto-discovery via `BlockLoader::loadAll()` — no manual registration
- Asset queueing: `BlockRegistry::queue()` before `get_header()`, then `BlockRegistry::render()` in body
- PSR-4: `TAW\` → `inc/`

## CSS / Asset Pipeline

- `resources/js/app.js` imports `../css/app.css` (Tailwind v4) and `../scss/app.scss` (custom SCSS) — neither is a standalone Vite entry
- `resources/scss/critical.scss` is a standalone Vite entry compiled and **inlined** in `<head>` — keep under ~14 KB, no `@font-face` inside it
- Main CSS loads asynchronously (non-render-blocking via `media="print"`) in production
- Self-hosted fonts live in `resources/fonts/`; `@font-face` goes in `resources/scss/_fonts.scss`, used via `@use 'fonts'` in `app.scss` only
- `vite_asset_url()` in `inc/vite-loader.php` resolves font/asset paths correctly in both dev and prod
- Add font preloads in `inc/performance.php` via `vite_asset_url()`

## When generating code

- New blocks: extend `TAW\Core\MetaBlock` or `TAW\Core\Block`, follow the naming convention exactly
- Use `use TAW\Core\MetaBlock;`, `use TAW\Core\Block;`, `use TAW\Core\Metabox\Metabox;`, `use TAW\Core\BlockRegistry;`
- Templates: use `esc_html()`, `esc_url()`, `esc_attr()` for all output
- Metabox fields: use types `text`, `textarea`, `wysiwyg`, `url`, `number`, `select`, `image`, `group`
- Meta keys follow pattern `_taw_{field_id}`
- Styles: Tailwind utilities in templates, custom CSS/SCSS in block's `style.css`/`style.scss`
- Never add block registrations to `functions.php`
