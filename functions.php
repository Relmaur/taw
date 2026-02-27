<?php

require_once get_template_directory() . '/vendor/autoload.php';

require_once get_template_directory() . '/inc/vite-loader.php';
require_once get_template_directory() . '/inc/performance.php';

add_action('wp_enqueue_scripts', function () {
    vite_enqueue_theme_assets();
});

add_action('admin_init', function () {
    remove_post_type_support('page', 'editor');
});

add_action('after_setup_theme', function () {
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
