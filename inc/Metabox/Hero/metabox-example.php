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

use TAW\Core\Metabox;

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
    'title'    => 'Hero Section',
    'screen'   => 'page',
    'context'  => 'normal',
    'priority' => 'high',
    'show_on'  => $is_front_page,
    // 'icon'   => get_template_directory_uri() . '/resources/static/admin/svg/icon-submission.svg',
    'tabs'     => [
        [
            'id'     => 'hero_settings',
            'label'  => 'Settings',
            'fields' => ['hero_tagline', 'hero_heading', 'hero_code', 'hero_code_language', 'hero_description', 'hero_cta_primary', 'hero_cta_secondary'],
            'icon'   => get_template_directory_uri() . '/resources/static/admin/svg/icon-submission.svg',
        ],
        [
            'id'     => 'hero_style',
            'label'  => 'Style',
            'fields' => ['hero_image'],
            'icon'   => get_template_directory_uri() . '/resources/static/admin/svg/icon-projects.svg',
        ]
    ],
    'fields'   => [
        [
            'id'          => 'hero_tagline',
            'label'       => 'Tagline',
            'type'        => 'text',
            'placeholder' => 'e.g. Bridging modern web architecture…',
            'description' => 'Short introductory text displayed above the headline.',
            // 'width'       => '50',
        ],
        // [
        //     'id'          => 'hero_heading',
        //     'label'       => 'Heading',
        //     'type'        => 'textarea',
        //     'rows'        => 3,
        //     'placeholder' => 'Main headline text',
        //     'description' => 'Primary H1 heading for the hero section.',
        //     'width'       => '50',
        // ],
        [
            'id'          => 'hero_description',
            'label'       => 'Description',
            'type'        => 'wysiwyg',
            'rows'        => 3,
            'description' => 'Supporting paragraph below the heading. Supports basic HTML.',
            'width'       => '50',
        ],
        [
            'id'          => 'hero_code',
            'label'       => 'Hero Code Snippet',
            'type'        => 'textarea',
            'sanitize'    => 'code',
            'rows'        => 10,
            'placeholder' => 'Optional code snippet (e.g., HTML, shortcode) to display in the hero section.',
            'description' => 'Code Snippet displayed in the hero section.',
            'width'       => '50',
        ],
        [
            'id'          => 'hero_code_language',
            'label'       => 'Code Snippet Formatting',
            'type'        => 'select',
            'options'     => [
                'none' => 'None',
                'html' => 'HTML',
                'css'  => 'CSS',
                'js'   => 'JavaScript',
                'php'  => 'PHP',
                'other' => 'Other',
            ],
            // 'width'       => '25',
            'description' => 'Specify the language of the code snippet for proper formatting.',
        ],
        [
            'id'          => 'hero_cta_primary',
            'label'       => 'Hero CTA (Primary)',
            'type'        => 'group',
            'fields'      => [
                [
                    'id'          => 'cta_text',
                    'label'       => 'Text',
                    'type'        => 'text',
                    'placeholder' => 'e.g. Get in touch!',
                ],
                [
                    'id'          => 'cta_url',
                    'label'       => 'URL',
                    'type'        => 'url',
                    'placeholder' => 'https://…',
                ],
            ],
            'width'       => '50',
        ],
        [
            'id'          => 'hero_cta_secondary',
            'label'       => 'Hero CTA (Secondary)',
            'type'        => 'group',
            'fields'      => [
                [
                    'id'          => 'cta_text',
                    'label'       => 'Text',
                    'type'        => 'text',
                    'placeholder' => 'e.g. Get in touch!',
                ],
                [
                    'id'          => 'cta_url',
                    'label'       => 'URL',
                    'type'        => 'url',
                    'placeholder' => 'https://…',
                ],
            ],
            'width'       => '50',
        ],
        [
            'id'          => 'hero_image',
            'label'       => 'Hero Image',
            'type'        => 'image',
            'description' => 'Optional image displayed in the hero section. Recommended dimensions: 1200x800px.',
        ]
    ],
]);

/* =========================================================================
 * Stats Section
 * ====================================================================== */

new Metabox([
    'id'       => 'taw_homepage_stats',
    'title'    => 'Stats Section',
    'screen'   => 'page',
    'context'  => 'normal',
    'priority' => 'default',
    'show_on'  => $is_front_page,
    'fields'   => [
        [
            'id'          => 'stat_1_value',
            'label'       => 'Stat 1 — Value',
            'type'        => 'text',
            'placeholder' => 'e.g. 4+',
        ],
        [
            'id'          => 'stat_1_label',
            'label'       => 'Stat 1 — Label',
            'type'        => 'text',
            'placeholder' => 'e.g. Years of Experience',
        ],
        [
            'id'          => 'stat_2_value',
            'label'       => 'Stat 2 — Value',
            'type'        => 'text',
            'placeholder' => 'e.g. 50+',
        ],
        [
            'id'          => 'stat_2_label',
            'label'       => 'Stat 2 — Label',
            'type'        => 'text',
            'placeholder' => 'e.g. Projects Completed',
        ],
        [
            'id'          => 'stat_3_value',
            'label'       => 'Stat 3 — Value',
            'type'        => 'text',
            'placeholder' => 'e.g. 100%',
        ],
        [
            'id'          => 'stat_3_label',
            'label'       => 'Stat 3 — Label',
            'type'        => 'text',
            'placeholder' => 'e.g. Project Success Rate',
        ],
    ],
]);