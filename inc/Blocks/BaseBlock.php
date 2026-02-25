<?php
// inc/Blocks/BaseBlock.php

declare(strict_types=1);

namespace TAW\Blocks;

abstract class BaseBlock
{
    protected string $id;
    protected string $dir;
    protected string $uri;

    private static array $enqueuedComponents = [];

    public function __construct()
    {
        $reflector = new \ReflectionClass(static::class);
        $this->dir = dirname($reflector->getFileName());

        $this->uri = get_template_directory_uri() . '/'
            . str_replace(get_template_directory() . '/', '', $this->dir);
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Enqueue this block's CSS and JS.
     * Supports style.scss, style.css, and script.js.
     * Safe to call multiple times — only enqueues once per block.
     */
    public function enqueueAssets(): void
    {
        if (isset(self::$enqueuedComponents[$this->id])) {
            return;
        }
        self::$enqueuedComponents[$this->id] = true;

        $relative_dir = str_replace(get_template_directory() . '/', '', $this->dir);

        if (function_exists('vite_is_dev') && vite_is_dev()) {
            $this->enqueueDevAssets($relative_dir);
        } else {
            $this->enqueueProdAssets($relative_dir);
        }
    }

    /**
     * DEV: serve assets from Vite dev server for HMR.
     */
    private function enqueueDevAssets(string $relative_dir): void
    {
        // SCSS takes priority over CSS
        $style_ext = $this->resolveStyleExtension();

        if ($style_ext) {
            wp_enqueue_style(
                'taw-block-' . $this->id,
                VITE_SERVER . '/' . $relative_dir . '/' . $style_ext,
                [],
                null
            );
        }

        if (file_exists($this->dir . '/script.js')) {
            wp_enqueue_script(
                'taw-block-' . $this->id,
                VITE_SERVER . '/' . $relative_dir . '/script.js',
                ['vite-client'],
                null,
                true
            );
        }
    }

    /**
     * PROD: resolve hashed filenames from the Vite manifest.
     */
    private function enqueueProdAssets(string $relative_dir): void
    {
        static $manifest = null;
        if ($manifest === null) {
            $manifest_path = get_template_directory() . '/public/build/manifest.json';
            $manifest = file_exists($manifest_path)
                ? json_decode(file_get_contents($manifest_path), true)
                : [];
        }

        // Check manifest for scss first, then css
        $scss_key  = $relative_dir . '/style.scss';
        $css_key   = $relative_dir . '/style.css';
        $js_key    = $relative_dir . '/script.js';
        $style_key = isset($manifest[$scss_key]) ? $scss_key : (isset($manifest[$css_key]) ? $css_key : null);

        if ($style_key) {
            wp_enqueue_style(
                'taw-block-' . $this->id,
                get_theme_file_uri('/public/build/' . $manifest[$style_key]['file']),
                [],
                null
            );
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

    /**
     * Determine which style file this block uses.
     * SCSS takes priority over CSS. Returns null if neither exists.
     */
    private function resolveStyleExtension(): ?string
    {
        if (file_exists($this->dir . '/style.scss')) {
            return 'style.scss';
        }
        if (file_exists($this->dir . '/style.css')) {
            return 'style.css';
        }
        return null;
    }

    /**
     * Include the block's index.php template with the given data.
     */
    protected function renderTemplate(array $data): void
    {
        $template = $this->dir . '/index.php';
        if (file_exists($template)) {
            extract($data, EXTR_SKIP);
            include $template;
        }
    }
}
