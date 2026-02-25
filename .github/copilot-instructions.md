# Copilot Instructions

> Full architecture docs: see `AGENTS.md` in the repo root.

## Project: TAW Theme

A classic WordPress theme with a custom block system, Vite v7, Tailwind v4, Alpine.js, and a bespoke metabox framework.

## Key Architecture

- Blocks: `inc/Blocks/{Name}/{Name}.php` — folder name must match class name
- Two types: **MetaBlock** (data-owning, uses metaboxes) and **Block** (presentational, receives props)
- Auto-discovery via `BlockLoader::loadAll()` — no manual registration
- Asset queueing: `BlockRegistry::queue()` before `get_header()`, then `BlockRegistry::render()` in body
- PSR-4: `TAW\` → `inc/`

## When generating code

- New blocks: extend `MetaBlock` or `Block`, follow the naming convention exactly
- Templates: use `esc_html()`, `esc_url()`, `esc_attr()` for all output
- Metabox fields: use types `text`, `textarea`, `wysiwyg`, `url`, `number`, `select`, `image`, `group`
- Meta keys follow pattern `_taw_{field_id}`
- Styles: Tailwind utilities in templates, custom CSS/SCSS in block's `style.css`/`style.scss`
- Never add block registrations to `functions.php`
