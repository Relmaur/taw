<?php
/**
 * archive.php — Template for date/category/tag/author archives.
 */

get_header();
?>

<div class="mx-auto max-w-360 w-[90%] py-16">

    <header class="mb-12">
        <h1 class="text-4xl font-bold"><?php the_archive_title(); ?></h1>
        <?php the_archive_description('<p class="mt-3 text-gray-500">', '</p>'); ?>
    </header>

    <?php if (have_posts()) : ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php while (have_posts()) : the_post(); ?>

                <article <?php post_class('bg-white rounded-xl shadow-sm overflow-hidden flex flex-col'); ?>>

                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
                            <?php the_post_thumbnail('medium', ['class' => 'w-full h-48 object-cover transition-transform hover:scale-105']); ?>
                        </a>
                    <?php endif; ?>

                    <div class="p-6 flex flex-col flex-1">
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-2"><?php echo esc_html(get_the_date()); ?></p>
                        <h2 class="text-lg font-semibold leading-snug flex-1">
                            <a href="<?php the_permalink(); ?>" class="hover:text-blue-600 transition-colors">
                                <?php the_title(); ?>
                            </a>
                        </h2>
                        <p class="mt-3 text-sm text-gray-500 line-clamp-3"><?php the_excerpt(); ?></p>
                    </div>

                </article>

            <?php endwhile; ?>
        </div>

        <div class="mt-12">
            <?php the_posts_pagination(['prev_text' => __('&larr; Newer', 'taw-theme'), 'next_text' => __('Older &rarr;', 'taw-theme')]); ?>
        </div>

    <?php else : ?>

        <p class="text-gray-500"><?php esc_html_e('No posts found.', 'taw-theme'); ?></p>

    <?php endif; ?>

</div>

<?php get_footer();
