<?php

declare(strict_types=1);

namespace TAW\Blocks;

abstract class BaseBlock
{

    /**
     * Unique identifier for this component (e.g. 'hero', 'button')
     */
    protected string $id;

    /**
     * Absolute path to this component's directory
     */
    protected string $dir;

    /**
     * URI to this component's directory (for enqueuing assets)
     */
    protected string $uri;

    private static array $enqueuedComponents = [];

    public function __construct()
    {

        // Use reflection to find the CHILD class's file location
        // This is the key trick - it lets BaseBlock find Hero/Hero.php's directory
        $reflector = new \ReflectionClass(static::class);
        $this->dir = dirname($reflector->getFileName());

        // Convert absolute path to theme URI
        $this->uri = get_template_directory_uri() . '/'
            . str_replace(get_template_directory() . '/', '', $this->dir);

        // Enqueue assets early enough for wp_head() to pick them up
        // add_action('wp_enqueue_scripts', function () {
        //     $this->enqueueAssets();
        // });

        // Register the metaboxes this component needs
        // $this->registerMetaboxes();
    }

    /**
     * Get this component's unique ID (e.g. 'hero').
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Enqueue component assets if they exist.
     * For SCSS: you'll handle this via Vite (more on this below).
     * For JS: same -  Vite or wp_enqueue_script.
     */
    protected function enqueueAssets(): void
    {
        if (isset(self::$enqueuedComponents[$this->id])) {
            return;
        }
        self::$enqueuedComponents[$this->id] = true;

        $relative_dir = str_replace(get_template_directory() . '/', '', $this->dir);

        // Determine if we've already passed wp_head — if so, we need to print styles inline
        $did_head = did_action('wp_head');

        if (function_exists('vite_is_dev') && vite_is_dev()) {
            $css_src = file_exists($this->dir . '/style.css')
                ? VITE_SERVER . '/' . $relative_dir . '/style.css'
                : null;
            $js_src = file_exists($this->dir . '/script.js')
                ? VITE_SERVER . '/' . $relative_dir . '/script.js'
                : null;

            if ($css_src) {
                if ($did_head) {
                    // Print directly since wp_head already fired
                    echo '<link rel="stylesheet" href="' . esc_url($css_src) . '">' . "\n";
                } else {
                    wp_enqueue_style('taw-block-' . $this->id, $css_src, [], null);
                }
            }

            if ($js_src) {
                wp_enqueue_script('taw-block-' . $this->id, $js_src, ['vite-client'], null, true);
            }
        } else {
            static $manifest = null;
            if ($manifest === null) {
                $manifest_path = get_template_directory() . '/public/build/manifest.json';
                $manifest = file_exists($manifest_path)
                    ? json_decode(file_get_contents($manifest_path), true)
                    : [];
            }

            $css_key = $relative_dir . '/style.css';
            $js_key  = $relative_dir . '/script.js';

            if (isset($manifest[$css_key])) {
                $css_url = get_theme_file_uri('/public/build/' . $manifest[$css_key]['file']);
                if ($did_head) {
                    echo '<link rel="stylesheet" href="' . esc_url($css_url) . '">' . "\n";
                } else {
                    wp_enqueue_style('taw-block-' . $this->id, $css_url, [], null);
                }
            }

            if (isset($manifest[$js_key])) {
                wp_enqueue_script(
                    'taw-block-' . $this->id,
                    get_theme_file_uri('/public/build/' . $manifest[$js_key]['file']),
                    [],
                    null,
                    true
                );
            }
        }
    }

    /**
     * Include the component's index.php with given variables.
     */
    protected function renderTemplate(array $data): void
    {

        $this->enqueueAssets();

        $template = $this->dir . '/index.php';

        if (file_exists($template)) {
            extract($data, EXTR_SKIP);
            include $template;
        }
    }
}
