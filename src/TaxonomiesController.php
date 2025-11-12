<?php

namespace Hirasso\ACFML;

if (! \defined('ABSPATH')) {
    exit;
}

class TaxonomiesController
{
    private string $prefix;
    private ?string $default_language = null;
    private string $field_postfix = "term_name";
    private string $field_name;
    private string $field_key;
    private string $field_group_key;
    /** @var array<int, string> */
    private array $taxonomies = [];

    /**
     * Constructor
     */
    public function __construct(private ACFMultilingual $acfml)
    {
        \add_action('acf/init', [$this, 'init']);
    }

    public function init(): void
    {
        // variables
        $this->prefix = $this->acfml->get_prefix();
        $this->default_language = $this->acfml->get_default_language();

        $this->field_name = "{$this->prefix}_{$this->field_postfix}";
        $this->field_key = "field_{$this->field_name}";
        $this->field_group_key = "group_{$this->field_name}";

        // hooks
        \add_filter('admin_body_class', [$this, 'admin_body_class'], 20);
        \add_filter('pre_insert_term', [$this, 'pre_insert_term'], 10, 2);
        \add_filter('wp_update_term_data', [$this, 'update_term_data'], 10, 4);
        // wp_update_term()
        \add_filter('get_term', [$this, 'get_term'], 10, 2);
        \add_filter("acf/load_value/key={$this->field_key}_{$this->default_language}", [$this, "load_default_value"], 10, 3);

        // methods
        \add_action('init', [$this, 'add_title_field_group'], 12);

        // query filters
        \add_action('pre_get_terms', [$this, 'pre_get_terms'], 999);
    }

    /**
     * Adds a custom field group for the title
     */
    public function add_title_field_group(): void
    {

        $taxonomies = $this->get_multilingual_taxonomies();

        // bail early if no post types support `multilingual-title`
        if (!\count($taxonomies)) {
            return;
        }
        // generate location rules for multilingual titles
        $location = [];
        foreach ($taxonomies as $tax) {
            $location[] = [
                [
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => $tax
                ]
            ];
        }

        \acf_add_local_field_group([
            'key' => $this->field_group_key,
            'title' => \__('Name'),
            'menu_order' => -1000,
            'style' => 'seamless',
            'position' => 'acf_after_title',
            'location' => $location,
        ]);

        \acf_add_local_field([
            'key' => $this->field_key,
            'label' => \__('Name'),
            'instructions' => \__('The name is how it appears on your site.'),
            'name' => $this->field_name,
            'type' => 'text',
            'acfml_multilingual' => true,
            'required' => true,
            'parent' => $this->field_group_key,
            'acfml_suppress_filters' => true,
            'wrapper' => [
                'class' => \str_replace('_', '-', $this->field_name)
            ]
        ]);

    }

    /**
     * Get multilingual taxonomies
     */
    public function get_multilingual_taxonomies(): array
    {
        return $this->taxonomies;
    }

    /**
     * Adds taxonomies
     */
    public function add_taxonomies(object $taxonomies): void
    {
        foreach ($taxonomies as $taxonomy_name => $args) {
            $this->add_taxonomy($taxonomy_name);
        }
    }

    /**
     * Add a taxonomy
     */
    public function add_taxonomy(string $taxonomy_name): array
    {
        $taxonomies = \array_unique(\array_merge($this->taxonomies, [$taxonomy_name]));
        $taxonomies = \array_filter($taxonomies, fn ($tax) => \taxonomy_exists($tax));
        $this->taxonomies = $taxonomies;
        return $taxonomies;
    }

    /**
     * Filter Admin Body Class
     */
    public function admin_body_class(string $class): string
    {
        global $pagenow, $taxonomy;
        if (!\in_array($pagenow, ['term.php', 'edit-tags.php'])) {
            return $class;
        }
        if (\in_array($taxonomy, $this->get_multilingual_taxonomies())) {
            $class .= " acfml-multilingual-taxonomy";
        }
        return $class;
    }

    /**
     * Parse Custom Field value for term name
     */
    public function pre_insert_term(mixed $term, mixed $taxonomy): mixed
    {
        $default_language_name = $_POST["acf"][$this->field_key]["{$this->field_key}_{$this->default_language}"] ?? null;
        if ($default_language_name) {
            $term = $default_language_name;
        }
        return $term;
    }

    /**
     * Update term data
     */
    public function update_term_data(mixed $data, mixed $term_id, mixed $taxonomy, mixed $args): mixed
    {
        $default_language_name = $_POST["acf"][$this->field_key]["{$this->field_key}_{$this->default_language}"] ?? null;
        if ($default_language_name) {
            $data['name'] = $default_language_name;
        }
        return $data;
    }

    /**
     * Filter terms
     */
    public function get_term(\WP_Term $term, string $taxonomy): \WP_Term
    {
        global $pagenow;
        if ($pagenow === 'term.php') {
            return $term;
        }
        $language = $this->acfml->get_current_language();
        if ($custom_name = \get_term_meta($term->term_id, "{$this->field_name}_{$language}", true)) {
            $term->name = $custom_name;
        }
        return $term;
    }

    /**
     * Load Default Value
     */
    public function load_default_value(mixed $value, int|string $post_id, array $field): mixed
    {
        global $pagenow, $taxonomy;
        if ($value) {
            return $value;
        }
        if (!\in_array($pagenow, ['term.php'])) {
            return $value;
        }
        if (\is_string($post_id) && \strpos($post_id, '_') !== false) {
            $term_id = \explode('_', $post_id)[1];
            \remove_filter('get_term', [$this, 'get_term']);
            $value = \get_term(\intval($term_id), $taxonomy)->name;
            \add_filter('get_term', [$this, 'get_term'], 10, 2);
        }
        return $value;
    }

    /**
     * Filter WP_Term_Query
     */
    public function pre_get_terms(\WP_Term_Query $query): void
    {
        if ($this->acfml->current_language_is_default()) {
            return;
        }
        $slug = $query->query_vars['slug'];
        if (\is_array($slug)) {
            $slug = $slug[0];
        }
        if ($slug) {
            // @todo: maybe add taxonomy support?!
        }

    }
}
