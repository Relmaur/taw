<?php
// inc/Blocks/Button/index.php

/**
 * @var string $text
 * @var string $url
 * @var string $variant
 * @var string $target
 */

if (empty($text)) return;
?>

<a href="<?php echo esc_url($url); ?>"
    class="btn btn--<?php echo esc_attr($variant); ?>"
    target="<?php echo esc_attr($target); ?>">
    <?php echo esc_html($text); ?>
</a>