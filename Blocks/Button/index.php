<?php
/**
 * @var string $text
 * @var string $url
 * @var string $variant
 * @var string $target
 */

if (empty($text)) return;
?>

<a href="<?php echo esc_url($url); ?>"
    class="btn btn--<?php echo esc_attr($variant); ?> flex items-center gap-2 px-4 py-2 rounded bg-blue-600 text-white text-sm font-medium w-fit"
    target="<?php echo esc_attr($target); ?>">
    <?php echo esc_html($text); ?>
</a>