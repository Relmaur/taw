<?php
// index.php (or front-page.php)

use TAW\Blocks\BlockRegistry;

// 1. Declare which blocks this page needs (BEFORE get_header)
BlockRegistry::queue('');

// 2. get_header triggers wp_enqueue_scripts → wp_head → styles in <head>
get_header();
?>

<?php // 3. Render blocks (HTML only, assets already handled) 
?>
<?php BlockRegistry::render('hero'); ?>

<?php get_footer(); ?>