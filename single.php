<?php
/**
 * single.php — Template for individual blog posts.
 *
 * Posts use the standard WordPress editor (the_content), not the TAW
 * block system. Queue any blocks that appear on every post above
 * get_header() if needed.
 */

get_header();
?>

<div class="mx-auto max-w-2xl w-[90%] py-16">
    <?php while (have_posts()) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <header class="mb-10">
                <h1 class="text-4xl font-bold leading-tight"><?php the_title(); ?></h1>
                <p class="mt-3 text-sm text-gray-500">
                    <?php echo esc_html(get_the_date()); ?> &middot; <?php the_author(); ?>
                </p>
            </header>

            <?php if (has_post_thumbnail()) : ?>
                <div class="mb-10 rounded-xl overflow-hidden">
                    <?php the_post_thumbnail('large', ['class' => 'w-full']); ?>
                </div>
            <?php endif; ?>

            <div class="entry-content prose max-w-none">
                <?php the_content(); ?>
            </div>

            <footer class="mt-12 pt-8 border-t border-gray-100">
                <?php the_tags('<p class="text-sm text-gray-500">' . __('Tagged: ', 'taw-theme'), ', ', '</p>'); ?>
                <nav class="flex justify-between mt-4 text-sm">
                    <span><?php previous_post_link('%link', '&larr; %title'); ?></span>
                    <span><?php next_post_link('%link', '%title &rarr;'); ?></span>
                </nav>
            </footer>

        </article>

    <?php endwhile; ?>
</div>

<?php get_footer();
