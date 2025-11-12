<?php

namespace Hirasso\ACFML;

if (! \defined('ABSPATH')) {
    exit;
}

/**
 * Sitemaps
 */
class SitemapsProvider extends \WP_Sitemaps_Provider
{
    /**
     * Constructor
     */
    public function __construct(private ACFMultilingual $acfml)
    {

        // inject main class
        $this->name        = 'languages';
        $this->object_type = 'language';
    }

    /**
    * Gets a URL list for a sitemap.
    * @return array[]
    */
    public function get_url_list($page_num, $object_subtype = '')
    {
        global $wp_sitemaps;
        $url_list = [];
        $index_url = $wp_sitemaps->index->get_index_url();
        // get language information
        $languages = $this->acfml->get_languages('slug');
        $default_language = $this->acfml->get_default_language();
        $current_language = $this->acfml->get_current_language();

        foreach ($languages as $lang) {
            // don't generate a link to the default language
            if ($lang === $default_language) {
                continue;
            }
            $sitemaps_entry = [
                'loc' => \esc_url($this->acfml->simple_convert_url($index_url, $lang)),
            ];
            $url_list[] = $sitemaps_entry;
        }

        return $url_list;
    }


    /**
     * Get the URL of a sitemap entry.
     */
    public function get_sitemap_url($name, $page)
    {
        /** @var \WP_Rewrite $wp_rewrite WordPress rewrite component. */
        global $wp_rewrite;

        // Accounts for cases where name is not included, ex: sitemaps-users-1.xml.
        $params = \array_filter(
            [
                'sitemap'         => $this->name,
                'sitemap-subtype' => $name,
                'paged'           => $page,
            ]
        );

        $basename = \sprintf(
            '/wp-sitemap-%1$s.xml',
            \implode('-', $params)
        );

        if (! $wp_rewrite->using_permalinks()) {
            $basename = '/?' . \http_build_query($params, '', '&');
        }

        return \home_url($basename);
    }

    /**
     * Lists sitemap pages exposed by this provider.
     *
     * The returned data is used to populate the sitemap entries of the index.
     *
     * @since 5.5.0
     */
    public function get_sitemap_entries(): array
    {
        global $wp_sitemaps;
        $sitemaps = [];

        $sitemaps[] = [
            'loc' => $this->get_sitemap_url('', 1),
        ];

        return $sitemaps;
    }

    /**
     * Gets the max number of pages available for the object type.
     */
    public function get_max_num_pages($object_subtype = '')
    {
        return 1;
    }

}
