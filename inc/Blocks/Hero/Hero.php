<?php

declare(strict_types=1);

namespace TAW\Blocks\Hero;

use TAW\Core\MetaBlock;
use TAW\Core\Metabox\Metabox;

class Hero extends MetaBlock
{
    protected string $id = 'hero';

    protected function registerMetaboxes(): void
    {
        new Metabox([
            'id'     => 'taw_hero',
            'title'  => 'Hero Section',
            'screen' => 'page',
            'fields' => [
                [
                    'id' => 'hero_heading',
                    'label' => 'Heading',
                    'type' => 'text',
                    'width' => '50'
                ],
                [
                    'id' => 'hero_tagline',
                    'label' => 'Tagline',
                    'type' => 'text',
                    'width' => '50'
                ],
                [
                    'id' => 'hero_image_url',
                    'label' => 'Hero Image',
                    'type' => 'image',
                    'width' => '100'
                ]
            ],
        ]);
    }

    protected function getData(int $postId): array
    {
        $image_url = $this->getMeta($postId, 'hero_image_url') ? $this->getMeta($postId, 'hero_image_url') : 'https://placehold.co/600x400';

        return [
            'heading' => $this->getMeta($postId, 'hero_heading'),
            'tagline' => $this->getMeta($postId, 'hero_tagline'),
            'image_url' => $image_url
        ];
    }
}
