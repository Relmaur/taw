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
 * This shaves time off LCP because discovery happens earlier.
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

    // Preload the main JS bundle
    if (isset($manifest['resources/js/app.js']['file'])) {
        printf(
            '<link rel="modulepreload" href="%s">' . "\n",
            esc_url(get_theme_file_uri('/public/build/' . $manifest['resources/js/app.js']['file']))
        );
    }

    // Preload CSS files extracted from JS entry
    if (isset($manifest['resources/js/app.js']['css'])) {
        foreach ($manifest['resources/js/app.js']['css'] as $css_file) {
            printf(
                '<link rel="preload" href="%s" as="style">' . "\n",
                esc_url(get_theme_file_uri('/public/build/' . $css_file))
            );
        }
    }

    // Preload standalone CSS entry
    if (isset($manifest['resources/scss/app.scss']['file'])) {
        printf(
            '<link rel="preload" href="%s" as="style">' . "\n",
            esc_url(get_theme_file_uri('/public/build/' . $manifest['resources/scss/app.scss']['file']))
        );
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
