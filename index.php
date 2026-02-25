<?php get_header(); ?>

<?php

use TAW\Blocks\Button\Button;

$button = new Button();

?>

<?php  // Section — fetches its own data
TAW\Blocks\BlockRegistry::render('hero');
?>

<?php get_footer(); ?>