<?php
// inc/vite-loader.php

define('VITE_SERVER', 'http://localhost:5173');
define('VITE_ENTRY_POINT', 'resources/js/app.js'); // Relative to theme root

function vite_is_dev()
{
    static $is_dev = null;
    if ($is_dev !== null) return $is_dev;
    $handle = @fsockopen('localhost', 5173, $errno, $errstr, 0.1);
    $is_dev = $handle !== false;
    if ($handle) fclose($handle);
    return $is_dev;
}

function vite_enqueue_theme_assets()
{
    $is_dev = vite_is_dev();
    $manifest_path = get_theme_file_path('/public/build/manifest.json');

    if ($is_dev) {
        // DEV MODE - Vite client must load first for HMR
        wp_enqueue_script('vite-client', VITE_SERVER . '/@vite/client', [], null, false);

        // Tailwind CSS entry (HMR handled by @tailwindcss/vite)
        wp_enqueue_style('tailwind', VITE_SERVER . '/resources/css/app.css', [], null);

        // Load JS entry point (which imports SCSS, so HMR works for styles too)
        wp_enqueue_script('theme-app', VITE_SERVER . '/' . VITE_ENTRY_POINT, ['vite-client'], null, false);

        // Note: Don't enqueue SCSS separately in dev - let JS handle it for HMR to work
    } elseif (file_exists($manifest_path)) {
        // PRODUCTION MODE
        $manifest = json_decode(file_get_contents($manifest_path), true);

        // 1. JS
        if (isset($manifest['resources/js/app.js'])) {
            $js_file = $manifest['resources/js/app.js']['file'];
            wp_enqueue_script('theme-app', get_theme_file_uri('/public/build/' . $js_file), [], null, true);
        }

        // 2. CSS (Vite extracts CSS imported in JS to the entry chunk)
        if (isset($manifest['resources/js/app.js']['css'])) {
            foreach ($manifest['resources/js/app.js']['css'] as $css_file) {
                wp_enqueue_style('theme-styles', get_theme_file_uri('/public/build/' . $css_file), [], null);
            }
        }

        // 3. Tailwind CSS entry
        if (isset($manifest['resources/css/app.css'])) {
            $css_file = $manifest['resources/css/app.css']['file'];
            wp_enqueue_style('tailwind', get_theme_file_uri('/public/build/' . $css_file), [], null);
        }

        // 4. Standalone SCSS (custom theme styles)
        if (isset($manifest['resources/scss/app.scss'])) {
            $css_file = $manifest['resources/scss/app.scss']['file'];
            wp_enqueue_style('theme-main-css', get_theme_file_uri('/public/build/' . $css_file), [], null);
        }
    }
}

/**
 * Preload critical assets from the Vite manifest.
 *
 * Emits <link rel="preload"> for production CSS and JS so the browser
 * begins fetching them before it reaches the actual enqueue tags.
 *
 * Tracks already-emitted URLs to avoid duplicate preload tags — this
 * matters because Vite's manifest can reference the same compiled CSS
 * file from multiple entry points (e.g., SCSS imported in JS AND as
 * a standalone entry).
 *
 * In dev mode this does nothing — Vite's dev server handles everything.
 */
function vite_preload_assets()
{
    if (vite_is_dev()) {
        return;
    }

    $manifest_path = get_theme_file_path('/public/build/manifest.json');

    if (!file_exists($manifest_path)) {
        return;
    }

    $manifest = json_decode(file_get_contents($manifest_path), true);
    $preloaded = []; // Track URLs to prevent duplicates

    // Helper: emit a preload tag only if we haven't already
    $emit = function (string $file, string $type) use (&$preloaded) {
        $url = get_theme_file_uri('/public/build/' . $file);
        if (isset($preloaded[$url])) {
            return;
        }
        $preloaded[$url] = true;

        if ($type === 'module') {
            printf('<link rel="modulepreload" href="%s">' . "\n", esc_url($url));
        } else {
            printf('<link rel="preload" href="%s" as="%s">' . "\n", esc_url($url), esc_attr($type));
        }
    };

    // 1. Preload the main JS bundle
    if (isset($manifest['resources/js/app.js']['file'])) {
        $emit($manifest['resources/js/app.js']['file'], 'module');
    }

    // 2. Preload Tailwind CSS (your biggest stylesheet)
    if (isset($manifest['resources/css/app.css']['file'])) {
        $emit($manifest['resources/css/app.css']['file'], 'style');
    }

    // 3. Preload CSS files extracted from JS entry (SCSS imported in app.js)
    if (isset($manifest['resources/js/app.js']['css'])) {
        foreach ($manifest['resources/js/app.js']['css'] as $css_file) {
            $emit($css_file, 'style');
        }
    }

    // 4. Preload standalone SCSS entry (deduplicated — skipped if already emitted above)
    if (isset($manifest['resources/scss/app.scss']['file'])) {
        $emit($manifest['resources/scss/app.scss']['file'], 'style');
    }
}

add_action('wp_head', 'vite_preload_assets', 2);

// Add type="module" for Vite
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    // Dev: all Vite server scripts
    if (str_starts_with($src, VITE_SERVER)) {
        return '<script type="module" src="' . esc_url($src) . '"></script>';
    }
    // Prod: theme-app and any component scripts
    if ($handle === 'theme-app' || str_starts_with($handle, 'taw-component-')) {
        return '<script type="module" src="' . esc_url($src) . '"></script>';
    }
    return $tag;
}, 10, 3);

/**
 * Resolve a theme asset path, checking the Vite manifest first.
 *
 * In production, assets processed by Vite get hashed filenames for
 * cache-busting (e.g., Inter-Regular-Bx7kZ3.woff2). This function
 * checks the manifest for the hashed version and falls back to the
 * raw file path if the asset wasn't processed by Vite.
 *
 * This lets developers choose either approach:
 *
 *   1. Vite-processed (hashed):
 *      Place fonts in resources/fonts/ and reference in SCSS.
 *      Vite hashes them → manifest maps original → hashed path.
 *
 *   2. Static (unhashed):
 *      Place fonts in resources/static/fonts/.
 *      Not in the manifest → function returns the direct URI.
 *
 * @param string $path Relative path from theme root (e.g., 'resources/fonts/Inter-Regular.woff2')
 * @return string Full URL to the asset (hashed if available, raw otherwise)
 */
function vite_asset_url(string $path): string
{
    // In dev mode, serve directly from Vite
    if (vite_is_dev()) {
        return VITE_SERVER . '/' . ltrim($path, '/');
    }

    // In production, check the manifest for a hashed version
    static $manifest = null;

    if ($manifest === null) {
        $manifest_path = get_theme_file_path('/public/build/manifest.json');
        $manifest = file_exists($manifest_path)
            ? json_decode(file_get_contents($manifest_path), true)
            : [];
    }

    // If Vite processed this file, use the hashed version
    if (isset($manifest[$path]['file'])) {
        return get_theme_file_uri('/public/build/' . $manifest[$path]['file']);
    }

    // Otherwise, serve the file directly from the theme directory
    return get_theme_file_uri('/' . ltrim($path, '/'));
}
