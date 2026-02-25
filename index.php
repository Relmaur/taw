<?php get_header(); ?>

<!-- <section>
    <div class="section-container">
        <h1>Hello, World!</h1>
    </div>
</section> -->

<?php  // Section — fetches its own data
TAW\Components\ComponentRegistry::render('hero');
?>

<?php get_footer(); ?>