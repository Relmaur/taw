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

// Auto-discover and register all MetaBlocks
TAW\Core\BlockLoader::loadAll();

// Enqueue assets for queued blocks (runs during wp_enqueue_scripts)
add_action('wp_enqueue_scripts', [TAW\Core\BlockRegistry::class, 'enqueueQueuedAssets']);
