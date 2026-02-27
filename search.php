<?php
/**
 * search.php — Template for search results.
 */

get_header();
?>

<div class="mx-auto max-w-2xl w-[90%] py-16">

    <header class="mb-10">
        <h1 class="text-3xl font-bold">
            <?php
            printf(
                /* translators: %s: search query */
                esc_html__('Results for: %s', 'taw-theme'),
                '<span class="text-blue-600">' . esc_html(get_search_query()) . '</span>'
            );
            ?>
        </h1>
    </header>

    <?php get_search_form(); ?>

    <?php if (have_posts()) : ?>

        <div class="mt-12 divide-y divide-gray-100">
            <?php while (have_posts()) : the_post(); ?>

                <article <?php post_class('py-8'); ?>>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1"><?php echo esc_html(get_the_date()); ?></p>
                    <h2 class="text-xl font-semibold">
                        <a href="<?php the_permalink(); ?>" class="hover:text-blue-600 transition-colors">
                            <?php the_title(); ?>
                        </a>
                    </h2>
                    <p class="text-xs text-blue-400 mt-1"><?php echo esc_url(get_permalink()); ?></p>
                    <p class="text-gray-600 mt-3 text-sm"><?php the_excerpt(); ?></p>
                </article>

            <?php endwhile; ?>
        </div>

        <div class="mt-12">
            <?php the_posts_pagination(['prev_text' => '&larr; Previous', 'next_text' => 'Next &rarr;']); ?>
        </div>

    <?php else : ?>

        <p class="mt-12 text-gray-500">
            <?php esc_html_e('No results found. Try different keywords.', 'taw-theme'); ?>
        </p>

    <?php endif; ?>

</div>

<?php get_footer();
