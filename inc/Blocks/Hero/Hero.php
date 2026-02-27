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
                    'width' => '33.33'
                ],
                [
                    'id' => 'hero_tagline',
                    'label' => 'Tagline',
                    'type' => 'text',
                    'width' => '33.33'
                ],
                [
                    'id' => 'hero_image_url',
                    'label' => 'Hero Image',
                    'type' => 'image',
                    'width' => '33.33'
                ],
                [
                    'id' => 'hero_show_tagline',
                    'label' => 'Show Tagline',
                    'type' => 'checkbox',
                    'description' => 'Enable or disable the tagline above the heading.',
                    'width' => '33.33'
                ],
                [
                    'id'          => 'hero_padding',
                    'label'       => 'Hero Padding',
                    'type'        => 'range',
                    'min'         => 20,
                    'max'         => 200,
                    'step'        => 10,
                    'unit'        => 'px',
                    'default'     => 80,
                    'description' => 'Vertical padding for the hero section.',
                    'width'       => '33.33',
                ],
                [
                    'id'          => 'hero_bg_color',
                    'label'       => 'Background Color',
                    'type'        => 'color',
                    // 'default'     => '#0f172a',
                    'description' => 'Background color for the hero section.',
                    'width'       => '33.33',
                ],
                [
                    'id'          => 'featured_post',
                    'label'       => 'Featured Post',
                    'type'        => 'post_select',
                    'post_type'   => 'post,page',
                    'description' => 'Select a single post to feature.',
                    'width'       => '50'
                ],
                [
                    'id'          => 'related_posts',
                    'label'       => 'Related Posts',
                    'type'        => 'post_select',
                    'post_type'   => 'post',
                    'multiple'    => true,
                    'max'         => 5,
                    'description' => 'Select up to 5 related posts.',
                    'width'       => '50'
                ],
                [
                    'id'           => 'team_members',
                    'label'        => 'Team Members',
                    'type'         => 'repeater',
                    'button_label' => 'Add Team Member',
                    'max'          => 8,
                    'fields'       => [
                        [
                            'id'          => 'name',
                            'label'       => 'Name',
                            'type'        => 'text',
                            'placeholder' => 'Full name',
                            'width'       => '50',
                        ],
                        [
                            'id'          => 'role',
                            'label'       => 'Role',
                            'type'        => 'text',
                            'placeholder' => 'e.g. Designer, Developer',
                            'width'       => '50',
                        ],
                        [
                            'id'    => 'bio',
                            'label' => 'Bio',
                            'type'  => 'textarea',
                            'rows'  => 3,
                        ],
                        [
                            'id'    => 'avatar',
                            'label' => 'Photo',
                            'type'  => 'image',
                            'width' => '50',
                        ],
                        [
                            'id' => 'group',
                            'label' => 'Group',
                            'type' => 'group',
                            'fields' => [
                                [
                                    'id' => 'linkedin',
                                    'label' => 'LinkedIn URL',
                                    'type' => 'text',
                                    'placeholder' => 'https://linkedin.com/in/username',
                                    'width' => '50',
                                ],
                                [
                                    'id' => 'twitter',
                                    'label' => 'Twitter URL',
                                    'type' => 'text',
                                    'placeholder' => 'https://twitter.com/username',
                                    'width' => '50',
                                ],
                            ],
                            'width' => '50'
                        ]
                    ],
                    'description' => 'Add your team members. Drag to reorder.',
                ],
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
