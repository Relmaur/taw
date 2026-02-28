<?php

declare(strict_types=1);

/**
 * Homepage Metabox Fields
 *
 * These metaboxes appear when editing the page set as the static front page.
 * → Settings > Reading > "A static page" > Homepage
 *
 * To add fields: append entries to the 'fields' array.
 * To add new sections: create another `new Metabox([ ... ])` block.
 *
 * Supported field types: text, textarea, wysiwyg, url, number, select, image
 *
 * @package TAW
 */

namespace TAW\Metabox;

use TAW\Core\Metabox\Metabox;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Condition: only show on the page assigned as the static front page.
 * If no front page is set, returns true so fields are still accessible.
 */
$is_front_page = function (\WP_Post $post): bool {
    $front_page_id = absint(get_option('page_on_front'));
    return $front_page_id === 0 || $post->ID === $front_page_id;
};

/* =========================================================================
 * Hero Section
 * ====================================================================== */

new Metabox([
    'id'       => 'taw_homepage_hero',
    'title'    => __('Hero Section', 'taw-theme'),
    'screen'   => 'page',
    'context'  => 'normal',
    'priority' => 'high',
    'show_on'  => $is_front_page,
    // 'icon'   => get_template_directory_uri() . '/resources/static/admin/svg/icon-submission.svg',
    'tabs'     => [
        [
            'id'     => 'hero_settings',
            'label'  => __('Settings', 'taw-theme'),
            'fields' => ['hero_tagline', 'hero_heading', 'hero_code', 'hero_code_language', 'hero_description', 'hero_cta_primary', 'hero_cta_secondary'],
            'icon'   => get_template_directory_uri() . '/resources/static/admin/svg/icon-submission.svg',
        ],
        [
            'id'     => 'hero_style',
            'label'  => __('Style', 'taw-theme'),
            'fields' => ['hero_image'],
            'icon'   => get_template_directory_uri() . '/resources/static/admin/svg/icon-projects.svg',
        ]
    ],
    'fields'   => [
        [
            'id'          => 'hero_tagline',
            'label'       => __('Tagline', 'taw-theme'),
            'type'        => 'text',
            'placeholder' => __('e.g. Bridging modern web architecture…', 'taw-theme'),
            'description' => __('Short introductory text displayed above the headline.', 'taw-theme'),
            // 'width'       => '50',
        ],
        // [
        //     'id'          => 'hero_heading',
        //     'label'       => __('Heading', 'taw-theme'),
        //     'type'        => 'textarea',
        //     'rows'        => 3,
        //     'placeholder' => __('Main headline text', 'taw-theme'),
        //     'description' => __('Primary H1 heading for the hero section.', 'taw-theme'),
        //     'width'       => '50',
        // ],
        [
            'id'          => 'hero_description',
            'label'       => __('Description', 'taw-theme'),
            'type'        => 'wysiwyg',
            'rows'        => 3,
            'description' => __('Supporting paragraph below the heading. Supports basic HTML.', 'taw-theme'),
            'width'       => '50',
        ],
        [
            'id'          => 'hero_code',
            'label'       => __('Hero Code Snippet', 'taw-theme'),
            'type'        => 'textarea',
            'sanitize'    => 'code',
            'rows'        => 10,
            'placeholder' => __('Optional code snippet (e.g., HTML, shortcode) to display in the hero section.', 'taw-theme'),
            'description' => __('Code Snippet displayed in the hero section.', 'taw-theme'),
            'width'       => '50',
        ],
        [
            'id'          => 'hero_code_language',
            'label'       => __('Code Snippet Formatting', 'taw-theme'),
            'type'        => 'select',
            'options'     => [
                'none'  => __('None', 'taw-theme'),
                'html'  => __('HTML', 'taw-theme'),
                'css'   => __('CSS', 'taw-theme'),
                'js'    => __('JavaScript', 'taw-theme'),
                'php'   => __('PHP', 'taw-theme'),
                'other' => __('Other', 'taw-theme'),
            ],
            // 'width'       => '25',
            'description' => __('Specify the language of the code snippet for proper formatting.', 'taw-theme'),
        ],
        [
            'id'          => 'hero_cta_primary',
            'label'       => __('Hero CTA (Primary)', 'taw-theme'),
            'type'        => 'group',
            'fields'      => [
                [
                    'id'          => 'cta_text',
                    'label'       => __('Text', 'taw-theme'),
                    'type'        => 'text',
                    'placeholder' => __('e.g. Get in touch!', 'taw-theme'),
                ],
                [
                    'id'          => 'cta_url',
                    'label'       => __('URL', 'taw-theme'),
                    'type'        => 'url',
                    'placeholder' => 'https://…',
                ],
            ],
            'width'       => '50',
        ],
        [
            'id'          => 'hero_cta_secondary',
            'label'       => __('Hero CTA (Secondary)', 'taw-theme'),
            'type'        => 'group',
            'fields'      => [
                [
                    'id'          => 'cta_text',
                    'label'       => __('Text', 'taw-theme'),
                    'type'        => 'text',
                    'placeholder' => __('e.g. Get in touch!', 'taw-theme'),
                ],
                [
                    'id'          => 'cta_url',
                    'label'       => __('URL', 'taw-theme'),
                    'type'        => 'url',
                    'placeholder' => 'https://…',
                ],
            ],
            'width'       => '50',
        ],
        [
            'id'          => 'hero_image',
            'label'       => __('Hero Image', 'taw-theme'),
            'type'        => 'image',
            'description' => __('Optional image displayed in the hero section. Recommended dimensions: 1200x800px.', 'taw-theme'),
        ]
    ],
]);

/* =========================================================================
 * Stats Section
 * ====================================================================== */

new Metabox([
    'id'       => 'taw_homepage_stats',
    'title'    => __('Stats Section', 'taw-theme'),
    'screen'   => 'page',
    'context'  => 'normal',
    'priority' => 'default',
    'show_on'  => $is_front_page,
    'fields'   => [
        [
            'id'          => 'stat_1_value',
            'label'       => __('Stat 1 — Value', 'taw-theme'),
            'type'        => 'text',
            'placeholder' => __('e.g. 4+', 'taw-theme'),
        ],
        [
            'id'          => 'stat_1_label',
            'label'       => __('Stat 1 — Label', 'taw-theme'),
            'type'        => 'text',
            'placeholder' => __('e.g. Years of Experience', 'taw-theme'),
        ],
        [
            'id'          => 'stat_2_value',
            'label'       => __('Stat 2 — Value', 'taw-theme'),
            'type'        => 'text',
            'placeholder' => __('e.g. 50+', 'taw-theme'),
        ],
        [
            'id'          => 'stat_2_label',
            'label'       => __('Stat 2 — Label', 'taw-theme'),
            'type'        => 'text',
            'placeholder' => __('e.g. Projects Completed', 'taw-theme'),
        ],
        [
            'id'          => 'stat_3_value',
            'label'       => __('Stat 3 — Value', 'taw-theme'),
            'type'        => 'text',
            'placeholder' => __('e.g. 100%', 'taw-theme'),
        ],
        [
            'id'          => 'stat_3_label',
            'label'       => __('Stat 3 — Label', 'taw-theme'),
            'type'        => 'text',
            'placeholder' => __('e.g. Project Success Rate', 'taw-theme'),
        ],
    ],
]);
