<?php

/**
 * Hero Component Template
 * 
 * Available variables (from Hero::getData):
 * 
 * @var string $tagline
 * @var string $heading
 * @var string $image_url
 */

// Guard: don't render if there's no content
if (empty($heading) && empty($tagline)) {
    return;
}

use TAW\Blocks\Button\Button;
use TAW\Helpers\Image;

$button = new Button();

?>

<section class="hero bg-amber-100!">
    <div class="section-container flex justify-center items-stretch gap-10 mx-auto max-w-360 w-[90%]">
        <div class=" hero__content flex-1 flex flex-col justify-center">
            <?php if ($tagline): ?>
                <p class="hero__tagline text-white"><?php echo esc_html($tagline); ?></p>
            <?php endif; ?>
            <?php if ($heading): ?>
                <h1 class="hero__heading text-5xl"><?php echo esc_html($heading); ?></h1>
            <?php endif; ?>
            <div class="flex items-center justify-start mt-2 gap-2">
                <?php $button->render(['text' => 'Get Started', 'url' => '/contact']); ?>
                <?php $button->render(['text' => 'Learn More', 'url' => '/about', 'variant' => 'secondary']); ?>
            </div>
        </div>
        <?php if ($image_url): ?>
            <div class="image w-full">
                <?php echo Image::render((int) $image_url, 'full', 'Hero banner', [
                    'above_fold' => true,
                    'sizes'      => '100vw',
                    'class'      => 'hero-image w-full',
                    'attr'       => [
                        'style' => 'width: 100%',
                    ],
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</section>