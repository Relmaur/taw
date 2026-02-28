<?php

require_once get_template_directory() . '/vendor/autoload.php';

require_once get_template_directory() . '/inc/vite-loader.php';
require_once get_template_directory() . '/inc/performance.php';

require_once get_template_directory() . '/inc/options.php';

add_action('wp_enqueue_scripts', function () {
    vite_enqueue_theme_assets();
});

add_action('admin_init', function () {
    remove_post_type_support('page', 'editor');
});

add_action('after_setup_theme', function () {
    load_theme_textdomain('taw-theme', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    register_nav_menus([
        'primary' => __('Primary Menu', 'taw-theme'),
        'footer'  => __('Footer Menu', 'taw-theme'),
    ]);
});

// Auto-discover and register all MetaBlocks
TAW\Core\BlockLoader::loadAll();

// Enqueue assets for queued blocks (runs during wp_enqueue_scripts)
add_action('wp_enqueue_scripts', [TAW\Core\BlockRegistry::class, 'enqueueQueuedAssets']);

new TAW\Core\Rest\SearchEndpoints();

/**
 * Admin Notices
 */
add_action('admin_notices', function () {
    global $post;
    if (!$post) return;

    $errors = get_transient('taw_validation_errors_' . $post->ID);
    if (!$errors) return;

    // Delete immediately so they don't persist
    delete_transient('taw_validation_errors_' . $post->ID);

    foreach ($errors as $error) {
        printf(
            '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
            esc_html($error)
        );
    }
});

// Theme auto-updater (only needed if distributing outside wordpress.org)
if (is_admin()) {
    new \TAW\Core\ThemeUpdater([
        'slug'       => 'taw-theme',
        'github_url' => 'https://api.github.com/repos/Relmaur/taw-theme/releases/latest',
    ]);
}
