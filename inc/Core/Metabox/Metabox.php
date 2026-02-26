<?php

declare(strict_types=1);

namespace TAW\Core\Metabox;

/**
 * Native WordPress Metabox Framework
 *
 * A reusable, configuration-driven class for registering metaboxes.
 * Supports: text, textarea, wysiwyg, image, url, number, select fields.
 *
 * Usage:
 *   new Metabox([
 *       'id'     => 'my_metabox',
 *       'title'  => 'My Fields',
 *       'screen' => 'page',
 *       'fields' => [ ... ],
 *   ]);
 *
 * @package TAW
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metabox
{
    private string $id;
    private string $title;
    private string $screen;
    private string $context;
    private string $priority;
    private string $prefix;
    private array  $fields;
    private array $tabs;
    private string $icon;

    /** @var callable|null Callback to conditionally show the metabox. Receives WP_Post. */
    private $show_on;

    /** Tracks whether the image uploader JS has already been enqueued. */
    private static bool $image_script_enqueued = false;

    private static bool $assets_enqueued = false;

    /**
     * @param array $config {
     *     @type string   $id       Unique metabox ID.
     *     @type string   $title    Metabox title shown in the editor.
     *     @type string   $screen   Post type to attach to. Default 'page'.
     *     @type string   $context  'normal', 'side', or 'advanced'. Default 'normal'.
     *     @type string   $priority 'high', 'default', or 'low'. Default 'high'.
     *     @type string   $prefix   Meta key prefix. Default '_taw_'.
     *     @type callable $show_on  Optional callback(WP_Post): bool — return false to hide the metabox.
     *     @type array    $fields   Array of field definitions.
     *     @type array    $tabs     Optional array of tab definitions.
     *     @type string   $icon     Optional icon uri for the metabox.
     * }
     */
    public function __construct(array $config)
    {
        $this->id       = $config['id'];
        $this->title    = $config['title'];
        $this->screen   = $config['screen']   ?? 'page';
        $this->context  = $config['context']  ?? 'normal';
        $this->priority = $config['priority'] ?? 'high';
        $this->prefix   = $config['prefix']   ?? '_taw_';
        $this->fields   = $config['fields']   ?? [];
        $this->show_on  = $config['show_on']  ?? null;
        $this->tabs     = $config['tabs'] ?? [];
        $this->icon     = isset($config['icon']) ? 'data:image/svg+xml;base64,' . base64_encode($config['icon']) : '';

        add_action('add_meta_boxes', [$this, 'register']);
        add_action('save_post', [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /* -------------------------------------------------------------------------
     * Registration
     * ---------------------------------------------------------------------- */

    public function register(): void
    {

        $post = get_post();
        if (
            $post &&
            is_callable($this->show_on) &&
            !call_user_func($this->show_on, $post)
        ) {
            return;
        }

        add_meta_box(
            $this->id,
            $this->title,
            [$this, 'render'],
            $this->screen,
            $this->context,
            $this->priority
        );
    }

    /* -------------------------------------------------------------------------
     * Admin Assets
     * ---------------------------------------------------------------------- */

    public function enqueue_admin_assets(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        // Enqueue Alpine
        wp_enqueue_script(
            'alpinejs',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            '3.0',
            true
        );

        wp_enqueue_style(
            'taw-metaboxes',
            get_template_directory_uri() . '/inc/Core/Metabox/style.css',
            [],
            '1.0'
        );

        $has_image = array_filter($this->fields, fn($f) => ($f['type'] ?? '') === 'image');

        if ($has_image) {
            wp_enqueue_media();
            $this->enqueue_image_script();
        }
    }

    /**
     * Outputs the image-upload JS exactly once, using event delegation so it
     * handles any number of image fields across multiple metabox instances.
     */
    private function enqueue_image_script(): void
    {
        if (self::$image_script_enqueued) {
            return;
        }

        self::$image_script_enqueued = true;

        add_action('admin_footer', static function () {
?>
            <script>
                (function($) {
                    'use strict';

                    // Upload button
                    $(document.body).on('click', '.taw-upload-image', function(e) {
                        e.preventDefault();
                        var $btn = $(this),
                            $wrapper = $btn.closest('.taw-image-field'),
                            $input = $wrapper.find('.taw-image-input'),
                            $preview = $wrapper.find('.taw-image-preview'),
                            $remove = $wrapper.find('.taw-remove-image');

                        var frame = wp.media({
                            title: 'Select or Upload Image',
                            button: {
                                text: 'Use this image'
                            },
                            multiple: false,
                            library: {
                                type: 'image'
                            }
                        });

                        frame.on('select', function() {
                            var attachment = frame.state().get('selection').first().toJSON();
                            var url = attachment.sizes && attachment.sizes.medium ?
                                attachment.sizes.medium.url :
                                attachment.url;
                            $input.val(attachment.id);
                            $preview.html(
                                '<img src="' + url + '" style="max-width:300px;height:auto;display:block;border:1px solid #ddd;padding:4px;border-radius:4px;">'
                            );
                            $remove.show();
                        });

                        frame.open();
                    });

                    // Remove button
                    $(document.body).on('click', '.taw-remove-image', function(e) {
                        e.preventDefault();
                        var $wrapper = $(this).closest('.taw-image-field');
                        $wrapper.find('.taw-image-input').val('');
                        $wrapper.find('.taw-image-preview').html('');
                        $(this).hide();
                    });
                })(jQuery);
            </script>
        <?php
        });
    }

    public function enqueue_assets(): void
    {
        if (self::$assets_enqueued) {
            return;
        }

        self::$assets_enqueued = true;

        // Enqueue additional CSS/JS on the admin for custom styling and functionality for the metaboxes
        // add_action('admin_enqueue_scripts', function () {
        //     wp_enqueue_style('taw-metaboxes', get_template_directory_uri() . '/inc/metaboxes/metaboxes.css', [], '1.0');
        // });
    }

    /* -------------------------------------------------------------------------
     * Render
     * ---------------------------------------------------------------------- */

    public function render(\WP_Post $post): void
    {
        // Conditional visibility
        if (is_callable($this->show_on) && !call_user_func($this->show_on, $post)) {
            // echo '<p class="description">This metabox is not applicable to this page.</p>';
            // // Still output nonce so save() exits cleanly.
            // wp_nonce_field($this->id . '_nonce_action', $this->id . '_nonce');
            // return;
            return;
        }

        wp_nonce_field($this->id . '_nonce_action', $this->id . '_nonce');



        if ($this->tabs && is_array($this->tabs)) {

            $this->render_tabs($this->tabs, $this->prefix, $post);
        } else { ?>

            <div class="fields-container">
                <?php foreach ($this->fields as $field):
                    $field_id = $this->prefix . $field['id'];
                    $value    = get_post_meta($post->ID, $field_id, true);
                    $label    = $field['label'] ?? '';
                    $desc     = $field['description'] ?? '';
                    $width    = ($field['width'] ?? '100') . '%'; ?>

                    <div class="field" style="--width: <?php echo esc_attr($width); ?>;">
                        <div class="field-and-label">
                            <label for="<?php echo esc_attr($field_id); ?>" class="field-label"><?php echo esc_html($label); ?></label>
                            <?php
                            $this->render_field($field, $field_id, $value, $post->ID);
                            ?>
                        </div>
                        <?php if ($desc): ?>
                            <p class="description"><?php echo esc_html($desc); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        }
    }

    /**
     * Render a single field by type.
     */
    private function render_field(array $field, string $field_id, mixed $value, ?int $post_id = null): void
    {
        $type        = $field['type'] ?? 'text';
        $placeholder = $field['placeholder'] ?? '';
        $tabs  = $field['tabs'] ?? null;

        switch ($type) {

            /* ---- Text ---- */
            case 'text':
                printf(
                    '<input type="text" id="%s" name="%s" value="%s" placeholder="%s" class="regular-text">',
                    esc_attr($field_id),
                    esc_attr($field_id),
                    esc_attr($value),
                    esc_attr($placeholder)
                );
                break;

            /* ---- URL ---- */
            case 'url':
                printf(
                    '<input type="url" id="%s" name="%s" value="%s" placeholder="%s" class="regular-text">',
                    esc_attr($field_id),
                    esc_attr($field_id),
                    esc_url($value),
                    esc_attr($placeholder)
                );
                break;

            /* ---- Number ---- */
            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" placeholder="%s" class="small-text" min="%s" max="%s" step="%s">',
                    esc_attr($field_id),
                    esc_attr($field_id),
                    esc_attr($value),
                    esc_attr($placeholder),
                    esc_attr($field['min'] ?? ''),
                    esc_attr($field['max'] ?? ''),
                    esc_attr($field['step'] ?? '1')
                );
                break;

            /* ---- Textarea ---- */
            case 'textarea':
                // Allow for custom snippets (e.g., code, shortcodes) by not escaping the value. The placeholder can still be escaped.
                printf(
                    '<textarea id="%s" name="%s" rows="%d" class="large-text" placeholder="%s">%s</textarea>',
                    esc_attr($field_id),
                    esc_attr($field_id),
                    intval($field['rows'] ?? 4),
                    esc_attr($placeholder),
                    esc_textarea($value)
                );
                break;

            /* ---- WYSIWYG ---- */
            case 'wysiwyg':
                wp_editor($value ?: '', $field_id, [
                    'textarea_name' => $field_id,
                    'textarea_rows' => intval($field['rows'] ?? 8),
                    'media_buttons' => $field['media_buttons'] ?? true,
                    'teeny'         => $field['teeny'] ?? false,
                ]);
                break;

            /* ---- Select ---- */
            case 'select':
                $options = $field['options'] ?? [];
                printf('<select id="%s" name="%s">', esc_attr($field_id), esc_attr($field_id));
                foreach ($options as $opt_value => $opt_label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($opt_value),
                        selected($value, $opt_value, false),
                        esc_html($opt_label)
                    );
                }
                echo '</select>';
                break;

            /* ---- Image ---- */
            case 'image':
                $image_url = $value ? wp_get_attachment_url(absint($value)) : '';
            ?>
                <div class="taw-image-field">
                    <input type="hidden"
                        class="taw-image-input"
                        id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($field_id); ?>"
                        value="<?php echo esc_attr($value); ?>">

                    <?php if ($image_url): ?>
                        <div class="taw-image-preview" style="margin-bottom: 10px;">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>"
                                    style="max-width:300px;height:auto;display:block;border:1px solid #ddd;padding:4px;border-radius:4px;">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <button type="button" class="button taw-upload-image">
                        <?php esc_html_e('Select Image', 'taw-theme'); ?>
                    </button>
                    <button type="button" class="button taw-remove-image"
                        style="<?php echo $value ? '' : 'display:none;'; ?>">
                        <?php esc_html_e('Remove Image', 'taw-theme'); ?>
                    </button>
                </div>
            <?php break;

            case 'group':
                $group_fields = $field['fields'] ?? [];
                $this->render_group($group_fields, $field_id, $post_id);
                break;
        }
    }

    private function render_group(array $group_fields, string $field_id_prefix, ?int $post_id = null): void
    {
        foreach ($group_fields as $field) {
            $field_id = $field_id_prefix . '_' . $field['id']; ?>
            <div class="field" style="--width: 100%;">
                <div class="field-and-label">
                    <label for="<?php echo esc_attr($field_id); ?>" class="field-label"><?php echo esc_html($field['label'] ?? ''); ?></label>
                    <?php
                    $value = $post_id ? get_post_meta($post_id, $field_id, true) : '';
                    $this->render_field($field, $field_id, $value, $post_id);
                    ?>
                </div>
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif; ?>
            </div>
        <?php }
    }

    private function render_tabs(array $tabs, string $field_id_prefix, \WP_Post $post): void
    {

        ?>
        <div class="taw-tabbed" x-data="{ activeTab: 0 }">
            <div class="tabs">
                <?php foreach ($tabs as $index => $tab): ?>
                    <?php
                    $tab_id = $field_id_prefix . '_' . $tab['id'];
                    $tab_label = $tab['label'] ?? 'Tab';
                    $tab_fields = $tab['fields'] ?? [];
                    $tab_icon = isset($tab['icon']) ? $tab['icon'] : '';
                    ?>
                    <div class="tab-title" :class="activeTab === <?php echo $index; ?> ? 'active' : ''" @click="activeTab = <?php echo $index; ?>">
                        <?php if ($tab_icon): ?>
                            <img src="<?php echo $tab_icon ?>" alt="Tab Icon">
                        <?php endif; ?>
                        <p><?php echo esc_html($tab_label); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="tab-content--wrapper">
                <?php foreach ($tabs as $index => $tab):
                    $tab_id = $field_id_prefix . '_' . $tab['id'];
                    $tab_label = $tab['label'] ?? 'Tab';
                    $tab_fields = $tab['fields'] ?? []; // Array of field IDs
                ?>
                    <div class="fields-container tab-content-<?php echo $index ?>" x-show="activeTab === <?php echo $index; ?>" x-cloak>
                        <?php $matches = array_filter($this->fields, function ($field) use ($tab_fields) {
                            return in_array($field['id'], $tab_fields);
                        }) ?>
                        <?php foreach ($matches as $field_index => $field): ?>
                            <?php
                            $field_id = $field_id_prefix . $field['id'];
                            $value    = get_post_meta($post->ID, $field_id, true);
                            $label    = $field['label'] ?? '';
                            $desc     = $field['description'] ?? '';
                            $width    = ($field['width'] ?? '100') . '%';
                            $border   = '';
                            // If it's the last field and its width is less than 100%, add a right border
                            if ($field_index === array_key_last($matches) && $width !== '100%') {
                                $border = 'border-right: 0.5px solid rgb(195, 196, 199);';
                            }
                            ?>

                            <div class="tab-field field" style="--width: <?php echo esc_attr($width) ?>; <?php echo esc_attr($border); ?>">

                                <div class="field-and-label">
                                    <label for="<?php echo esc_attr($field_id); ?>" class="tab-field-label"><?php echo esc_html($label); ?></label>
                                    <?php $this->render_field($field, $field_id, $value, $post->ID); ?>
                                </div>

                                <?php if ($desc): ?>
                                    <p class="description"><?php echo esc_html($desc); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php
                        endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php }

    /* -------------------------------------------------------------------------
     * Save
     * ---------------------------------------------------------------------- */

    public function save(int $post_id, \WP_Post $post): void
    {
        // Nonce check
        if (
            !isset($_POST[$this->id . '_nonce']) ||
            !wp_verify_nonce($_POST[$this->id . '_nonce'], $this->id . '_nonce_action')
        ) {
            return;
        }

        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Capability check
        $post_type_obj = get_post_type_object($post->post_type);
        if (!current_user_can($post_type_obj->cap->edit_post, $post_id)) {
            return;
        }

        // Must match the registered post type
        if ($post->post_type !== $this->screen) {
            return;
        }

        foreach ($this->fields as $field) {
            $field_id = $this->prefix . $field['id'];

            if (($field['type'] ?? '') === 'group') {
                $group_fields = $field['fields'] ?? [];
                foreach ($group_fields as $group_field) {
                    $group_field_id = $field_id . '_' . $group_field['id'];

                    if (!isset($_POST[$group_field_id])) {
                        delete_post_meta($post_id, $group_field_id);
                        continue;
                    }

                    $value = $this->sanitize_field($group_field, $_POST[$group_field_id]);
                    update_post_meta($post_id, $group_field_id, $value);
                }

                continue;
            }

            if (!isset($_POST[$field_id])) {
                delete_post_meta($post_id, $field_id);
                continue;
            }

            $value = $this->sanitize_field($field, $_POST[$field_id]);
            update_post_meta($post_id, $field_id, $value);
        }
    }

    /**
     * Sanitize a field value based on its type and optional 'sanitize' override.
     */
    private function sanitize_field(array $field, mixed $value): mixed
    {
        // Fields with 'sanitize' => 'code' preserve raw content for trusted users.
        if (($field['sanitize'] ?? '') === 'code') {
            return current_user_can('unfiltered_html') ? $value : wp_kses_post($value);
        }

        $type = $field['type'] ?? 'text';

        return match ($type) {
            'text', 'select' => sanitize_text_field($value),
            'textarea'       => sanitize_textarea_field($value),
            'url'            => esc_url_raw($value),
            'number'         => floatval($value),
            'image'          => absint($value),
            'wysiwyg'        => wp_kses_post($value),
            default          => sanitize_text_field($value),
        };
    }

    /* -------------------------------------------------------------------------
     * Template Helpers (static)
     * ---------------------------------------------------------------------- */

    /**
     * Retrieve a single meta value.
     *
     * @param int    $post_id  The post/page ID.
     * @param string $field_id Field ID (without prefix).
     * @param string $prefix   Meta key prefix. Default '_taw_'.
     */
    public static function get(int $post_id, string $field_id, string $prefix = '_taw_'): mixed
    {
        return get_post_meta($post_id, $prefix . $field_id, true);
    }

    /**
     * Retrieve a meta value and return an image URL.
     *
     * @param int    $post_id  The post/page ID.
     * @param string $field_id Field ID (without prefix).
     * @param string $size     WordPress image size. Default 'full'.
     */
    public static function get_image_url(int $post_id, string $field_id, string $size = 'full', string $prefix = '_taw_'): string
    {
        $attachment_id = absint(self::get($post_id, $field_id, $prefix));
        if (!$attachment_id) {
            return '';
        }

        $src = wp_get_attachment_image_url($attachment_id, $size);

        return $src ?: '';
    }
}
