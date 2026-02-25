<?php

declare(strict_types=1);

namespace TAW\Components;

use TAW\Metabox\Metabox;

abstract class SectionComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct();
        $this->registerMetaboxes();
    }

    /**
     * Define and register metaboxes for this section.
     */
    abstract protected function registerMetaboxes(): void;

    /**
     * Gather template data from post meta.
     */
    abstract protected function getData(int $postId): array;

    /**
     * Render this section for a given post
     */
    public function render(?int $postId = null): void
    {
        $postId = $postId ?? get_the_ID();
        if (!$postId) return;

        $data = $this->getData($postId);
        $this->renderTemplate($data);
    }

    /**
     * Helpers
     */
    protected function getMeta(int $postId, string $fieldId, string $prefix = '_taw_'): mixed
    {
        return Metabox::get($postId, $fieldId, $prefix);
    }

    protected function getImageUrl(int $postId, string $fieldId, string $size = 'full'): string
    {
        return Metabox::get_image_url($postId, $fieldId, $size);
    }
}