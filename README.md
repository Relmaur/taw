# Taw Theme - Classic Modern

**A classic WordPress theme powered by Timber/Twig and Vite.**

---

## Overview

This is a classic WordPress theme that combines traditional PHP templating with modern development tools:

- **Vite** — Lightning-fast HMR (Hot Module Replacement) during development
- **SCSS** — Organized, modular stylesheets
- **Alpine.js** — Lightweight JavaScript for interactive components

---

## Requirements

| Requirement | Version |
|-------------|---------|
| Node.js     | v18+    |
| PHP         | v7.4+   |
| WordPress   | v6.0+   |
| Composer    | v2.0+   |

---

## Installation

1. Navigate to the theme directory:
   ```bash
   cd wp-content/themes/taw-theme
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node dependencies:
   ```bash
   npm install
   ```

4. Activate the theme in WordPress.

---

## Development

### Start Dev Server

```bash
npm run dev
```

This starts Vite on `http://localhost:5173` with:
- **HMR** for instant style and JS updates
- **Auto-refresh** when PHP or Twig files change

### Build for Production

```bash
npm run build
```

Compiles and minifies assets to `public/build/` with cache-busting hashes.

---

## Project Structure

```
/
├── public/build/           # Compiled assets (auto-generated)
├── inc/
│   └── vite-loader.php     # Vite integration for WordPress
├── resources/
│   ├── js/                 # JavaScript source files
│   │   └── app.js
│   ├── scss/               # SCSS source files
│   │   └── app.scss
│   └── views/              # Twig templates
├── vendor/                 # Composer dependencies (Timber)
├── functions.php           # Theme setup & hooks
├── header.php              # Header template
├── footer.php              # Footer template
├── index.php               # Main template
├── style.css               # Theme metadata
├── vite.config.js          # Vite configuration
├── composer.json           # PHP dependencies
└── package.json            # Node dependencies
```

---

## How It Works

### Vite Integration

The `inc/vite-loader.php` file automatically detects whether Vite's dev server is running:

- **Development:** Loads assets directly from `http://localhost:5173` with HMR enabled
- **Production:** Reads `public/build/manifest.json` and loads hashed, minified assets

### Timber/Twig

Twig templates live in `resources/views/`. Timber separates your HTML markup from PHP logic, making templates cleaner and easier to maintain.

---

## Scripts

| Command         | Description                              |
|-----------------|------------------------------------------|
| `npm run dev`   | Start Vite dev server with HMR           |
| `npm run build` | Build optimized assets for production    |

---

## Tech Stack

- [Timber](https://timber.github.io/docs/v2/) — Twig templating for WordPress
- [Vite](https://vitejs.dev/) — Next-generation frontend tooling
- [Alpine.js](https://alpinejs.dev/) — Minimal JavaScript framework
- [SCSS](https://sass-lang.com/) — CSS preprocessor