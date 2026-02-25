<?php

require_once get_template_directory() . '/vendor/autoload.php'; // For Timber
require_once get_template_directory() . '/inc/vite-loader.php';
// require_once get_template_directory() . '/inc/Metabox/metabox-example.php';

add_action('wp_enqueue_scripts', function () {
    vite_enqueue_theme_assets();
});

// Remove the editor for all pages
add_action('admin_init', function () {
    remove_post_type_support('page', 'editor');
});

use TAW\Blocks\BlockRegistry;
use TAW\Blocks\Hero\Hero;

BlockRegistry::register(new Hero());