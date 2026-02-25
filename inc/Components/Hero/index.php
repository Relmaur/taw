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
    <div class="section-container flex w-full gap-10">
        <div class=" hero__content">
        <?php if ($tagline): ?>
            <p class="hero__tagline"><?php echo esc_html($tagline); ?></p>
        <?php endif; ?>
        <?php if ($heading): ?>
            <h1 class="hero__heading"><?php echo esc_html($heading); ?></h1>
        <?php endif; ?>
    </div>
    <?php if ($image_url): ?>
        <div class="hero__media">
            <img src="<?php echo esc_url($image_url); ?>" alt="" class="hero__image">
        </div>
    <?php endif; ?>
    </div>
</section>