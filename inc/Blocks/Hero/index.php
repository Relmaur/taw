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

?>

<section class="hero">
    <div class="section-container flex justify-center items-stretch gap-10 mx-auto max-w-360">
        <div class=" hero__content flex-1 flex flex-col justify-center">
            <?php if ($tagline): ?>
                <p class="hero__tagline text-white"><?php echo esc_html($tagline); ?></p>
            <?php endif; ?>
            <?php if ($heading): ?>
                <h1 class="hero__heading text-5xl"><?php echo esc_html($heading); ?></h1>
            <?php endif; ?>
        </div>
        <?php if ($image_url): ?>
            <div class="hero__media flex-1">
                <img src="<?php echo esc_url($image_url); ?>" alt="" class="hero__image w-full h-full object-cover">
            </div>
        <?php endif; ?>
    </div>
</section>