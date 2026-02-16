<?php

namespace Hirasso\ACFML;

if (! \defined('ABSPATH')) {
    exit;
}

class FieldsController
{
    /**
     * For which field types should 'acfml_multilingual' be available?
     *
     * @var array<int, string>
     */
    private array $multilingual_field_types = [
        'text', 'textarea', 'url', 'image', 'file', 'wysiwyg', 'post_object', 'true_false'
    ];

    /** @var array<int, string> */
    private array $available_ui_styles = ['tabs', 'columns'];

    private string $prefix;

    /**
     * Constructor
     *
     */
    public function __construct(private ACFMultilingual $acfml)
    {

        // inject main class
        $this->prefix = $this->acfml->get_prefix();
        $this->add_hooks();
    }

    /**
     * Add hooks to ACF
     */
    private function add_hooks(): void
    {
        // allow custom field types to be multilingual
        $multilingual_field_types = \apply_filters("$this->prefix/multilingual_field_types", $this->multilingual_field_types);
        // add hooks to multilingual field types
        foreach ($multilingual_field_types as $field_type) {
            \add_action("acf/render_field_settings/type=$field_type", [$this, 'render_field_settings'], 9);
            \add_filter("acf/load_field/type=$field_type", [$this, 'load_multilingual_field'], 20);
        }
        // add hooks for generated multilingual fields (type of those will be 'group')
        \add_filter("acf/format_value/type=group", [$this, 'format_multilingual_value'], 12, 3);
        \add_filter("acf/update_value/type=group", [$this, 'before_update_multilingual_value'], 9, 4);
        \add_filter("acf/update_value/type=group", [$this, 'after_update_multilingual_value'], 12, 4);
        \add_action("acf/render_field/type=group", [$this, 'render_multilingual_field'], 5);
        \add_filter("acf/load_value/type=group", [$this, 'inject_previous_monolingual_value'], 10, 3);
        \add_filter("acf/field_wrapper_attributes", [$this, "field_wrapper_attributes"], 10, 2);
        \add_filter("acf/prepare_field/type=wysiwyg", [$this, "maybe_delay_wysiwyg"], 11);
    }

    /**
     * Render field settings for multilingual fields
     * @param array<string, mixed> $field
     */
    public function render_field_settings(array $field): void
    {
        \acf_render_field_setting($field, [
            'label'         => \__('Multilingual?', 'acfml'),
            'instructions'	=> '',
            'name'          => 'acfml_multilingual',
            'type'          => 'true_false',
            'ui'            => 1,
        ], false);

    }

    /**
     * Load multilingual fields. If a field's 'acfml_multilingual' setting is set to 'true', then:
     *
     *    - create one sub-field for each language with the same type of the field (e.g. text, textarea, ...)
     *    - create a field of type 'group' that will hold the different sub-fields
     *    – if the field is set to 'required', set the sub-field for the default language to required,
     *      but not the group itself
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function load_multilingual_field(array $field): array
    {
        global $post_type;

        // return of on acf-field-group edit screen
        $post_type = $_GET['post_type'] ?? \get_post_type();
        if ($post_type === 'acf-field-group') {
            return $field;
        }
        // bail early if the field is not multilingual
        if (empty($field['acfml_multilingual'])) {
            return $field;
        }

        $active_language_tab = $this->get_active_language_tab($field);
        $required_all = $field['acfml_all_required'] ?? false;

        if (($field['acfml_suppress_filters'] ?? false) === false) {
            // allow themes to alter ACFML-Fields
            $field = \apply_filters('acfml/load_field', $field);
            $field = \apply_filters('acfml/load_field/type=' . $field['type'], $field);
            $field = \apply_filters('acfml/load_field/name=' . $field['_name'], $field);
            $field = \apply_filters('acfml/load_field/key=' . $field['key'], $field);
        }

        $ui_style = $this->get_field_ui_style($field);

        $default_language = $this->acfml->get_default_language();
        $sub_fields = [];
        $languages = $this->acfml->get_languages();
        foreach ($languages as $lang => $language_info) {

            // prepare wrapper
            $wrapper = $field['wrapper'];
            $wrapper['class'] .= ' acfml-field';
            if (!\in_array($field['name'], [
                'acfml_post_title',
                'acfml_slug',
                'acfml_lang_active',
                'acfml_term_name'
            ])) {
                $wrapper['dir'] = $language_info['dir'];
                $wrapper['data-acfml-text-direction'] = $language_info['dir'];
            }

            if ($ui_style === 'tabs' && $lang === $active_language_tab) {
                $wrapper['class'] .= ' acfml-is-visible';
            }
            if (!empty($wrapper['id'])) {
                $wrapper['id'] = "{$wrapper['id']}--{$lang}";
            }
            $wrapper['width'] = '';
            // prepare subfield
            $sub_field = \array_merge($field, [
                'key' => "{$field['key']}_{$lang}",
                'label' => "{$field['label']} ({$language_info['name']})",
                'name' => "{$field['name']}_{$lang}",
                '_name' => "$lang",
                // Only the default language of a sub-field should be required
                'required' => $required_all || $lang === $default_language && $field['required'],
                'acfml_multilingual' => 0,
                'acfml_multilingual_subfield' => 1,
                'acfml_field_language' => $lang,
                'acfml_field_is_hidden' => $ui_style === 'tabs' && !$this->acfml->is_default_language($lang),
                'wrapper' => $wrapper,
            ]);

            // add the subfield
            $sub_fields[] = $sub_field;
        }

        // Add 'required'-indicator to the groups label, if it is set to required
        $label = $field['label'];
        $key = $field['key'];

        if ($field['required']) {
            \add_filter('acf/get_field_label', function (string $label, array $field) use ($key) {
                return $field['key'] === $key
                  ? "$label <span class=\"acf-required\">*</span>"
                  : $label;

            }, 10, 2);
        }

        $field_classes = \explode(' ', $field['wrapper']['class']);
        $field_classes[] = "acfml-multilingual-field";
        $field_classes[] = "acfml-ui-style--$ui_style";
        // Change the $field to a group that will hold all sub-fields for all languages

        $field = \array_merge($field, [
            'label' => $label,
            'type' => 'group',
            'layout' => 'block',
            'sub_fields' => $sub_fields,
            'required' => false,
            'wrapper' => [
                'width' => $field['wrapper']['width'],
                'class' => \implode(' ', $field_classes),
                'id' => $field['wrapper']['id'],
                'data-acfml-ui-style' => $ui_style,
            ],
        ]);
        return $field;
    }


    /**
     * Automatically loads possible value of previously monolingual field
     * to the sub_field assigned to the default language
     */
    public function inject_previous_monolingual_value(mixed $value, int|string $post_id, array $field): mixed
    {
        // bail early if field is empty or not multilingual
        if (!$this->is_acfml_group($field)) {
            return $value;
        }
        // bail early if no value or array
        if (!$value || \is_array($value)) {
            return $value;
        }

        $default_language = $this->acfml->get_default_language();
        $untranslated_value = $value;

        // Clone fields have their key stored under '__key'
        $field_key = $field['__key'] ?? $field['key'];
        $default_language_field_key = "{$field_key}_$default_language";

        $this->add_filter_once(
            "acf/load_value/key={$default_language_field_key}",
            function ($translated_value) use ($untranslated_value) {
                return !empty($translated_value) ? $translated_value : $untranslated_value;
            }
        );

        return $value;
    }

    /**
     * Register a filter to run exactly one time.
     *
     * The arguments match that of add_filter(), but this function will also register a second
     * callback designed to remove the first immediately after it runs.
     */
    public function add_filter_once(string $hook, callable $callback, int $priority = 10, int $args = 1): bool
    {
        $singular = function () use ($hook, $callback, $priority, $args, &$singular) {
            \remove_filter($hook, $singular, $priority);
            return \call_user_func_array($callback, \func_get_args());
        };

        return \add_filter($hook, $singular, $priority, $args);
    }

    /**
     * Formats a fields value
     */
    public function format_multilingual_value(mixed $value, int|string $post_id, array $field): mixed
    {
        if (!$this->is_acfml_group($field)) {
            return $value;
        }
        $language = $this->acfml->get_current_language();
        $value = !empty($value[$language]) ? $value[$language] : ($value[$this->acfml->get_default_language()] ?? null);
        return $value;
    }


    /**
     * Applies custom "acfml_sanitize_callback" to field values before saving to the database.
     * Used for slugs
     */
    public function before_update_multilingual_value(mixed $value, int|string $post_id, array $field, mixed $value_before): mixed
    {
        if (!$this->is_acfml_group($field)) {
            return $value;
        }
        if (\is_array($value) && !empty($field['acfml_sanitize_callback']) && \function_exists($field['acfml_sanitize_callback'])) {
            $value = \array_map($field['acfml_sanitize_callback'], $value);
        }
        return $value;
    }

    /**
     * Write the default language's $value to the group $value itself
     */
    public function after_update_multilingual_value(mixed $value, int|string $post_id, array $field, mixed $value_before): mixed
    {
        if (!$this->is_acfml_group($field)) {
            return $value;
        }
        $default_language = $this->acfml->get_default_language();
        $field_key = "{$field['key']}_$default_language";

        $default_language_value = $value_before[$field_key] ?? $value;

        return $default_language_value;
    }

    /**
     * Get the ui style for a field
     */
    private function get_field_ui_style(array $field): string
    {
        $ui_style = $field['acfml_ui_style'] ?? 'tabs';
        if (!$this->validate_ui_style($ui_style)) {
            throw new \Exception(
                \sprintf(
                    /* translators: 1: Unkown UI style name, 2: Comma-separated list of available UI styles */
                    '[ACFML] ' . \__('Unknown field UI style "%1$s". Please use one of %2$s.', 'acfml'),
                    $ui_style,
                    $this->make_array_readable($this->available_ui_styles)
                )
            );
        }
        return $ui_style;
    }

    /**
     * Checks an UI style for available styles
     */
    private function validate_ui_style(string $ui_style): bool
    {
        return \in_array($ui_style, $this->available_ui_styles);
    }

    /**
     * Convert an array to a readable string
     */
    private function make_array_readable(array $array, bool $quote_items = true): string
    {

        if ($quote_items) {
            $array = \array_map(function ($item) {
                return "'$item'";
            }, $array);
        }

        if (\count($array) < 2) {
            return \implode(', ', $array);
        }

        $last = \array_pop($array);
        return \implode(', ', $array) . ' or ' . $last;
    }

    /**
     * Renders Language Tabs for multilingual fields
     */
    public function render_multilingual_field(array $field): void
    {
        if (!$this->is_acfml_group($field)) {
            return;
        }
        $default_field_language = $this->get_active_language_tab($field);
        $languages = $this->acfml->get_languages();
        if (\count($languages) < 2) {
            return;
        }
        if (($field['acfml_show_ui'] ?? true) === false) {
            return;
        }
        if ($this->get_field_ui_style($field) === 'tabs') {
            $this->render_language_tabs($languages, $default_field_language);
        }

    }

    /**
     * Renders language tabs for a field
     */
    private function render_language_tabs(array $languages, string $default_field_language): void
    {
        \ob_start(); ?>
    <div class="acfml-tabs-wrap">
      <div class="acfml-tabs acf-js-tooltip" title="<?= \__('Double-click to switch globally', 'acfml') ?>">
      <?php foreach ($languages as $id => $language) : ?>
      <button class="acfml-tab <?= $language['slug'] === $default_field_language ? 'is-active' : '' ?>" data-language="<?= $language['slug'] ?>">
        <?= $language['name'] ?>
      </button>
      <?php endforeach; ?>
      </div>
    </div>
    <?php echo \ob_get_clean();
    }

    /**
     * Get the default language for an ACF field in the admin
     */
    private function get_active_language_tab(array $field): string
    {
        $cookie = (array) $this->acfml->get_admin_cookie('acfml_language_tabs');
        return $cookie[$field['key']] ?? $this->acfml->get_default_language();
    }

    /**
     * Check if a field is multilingual
     */
    private function is_acfml_group(array|bool $field): bool
    {
        return \is_array($field) && $field['type'] === 'group' && !empty($field['acfml_multilingual']);
    }

    /**
     * Filter field wrapper
     */
    public function field_wrapper_attributes(array $wrapper, array $field): array
    {
        if ($switch_with = $field['acfml_ui_listen_to'] ?? null) {
            $wrapper['data-acfml-ui-listen-to'] = $switch_with;
        }
        if (!empty($field['acfml_multilingual_subfield']) && $this->acfml->is_default_language($field['_name'])) {
            $wrapper['class'] .= ' acfml-is-default-language';
        }
        if (!empty($field['acfml_field_language'])) {
            $wrapper['data-acfml-field-language'] = $field['acfml_field_language'];
        }

        return $wrapper;
    }

    /**
     * Sets delayed initialization to true for hidden acfml wysiwyg fields
     */
    public function maybe_delay_wysiwyg(mixed $field): mixed
    {
        if (empty($field)) {
            return $field;
        }
        $is_hidden = $field['acfml_field_is_hidden'] ?? null;
        if (!$is_hidden) {
            return $field;
        }
        $field['delay'] = 1;
        return $field;
    }
}
