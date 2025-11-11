<?php


/**
 * This function is documented in acfml.php > get_language_switcher
 */
function acfml_get_language_switcher(?array $args = [])
{
    return \acfml()->get_language_switcher($args);
}

/**
 * This function is documented in acfml.php > convert_url
 */
function acfml_convert_url(?string $url = null, ?string $lang = null): string
{
    return \acfml()->convert_url($url, $lang);
}

/**
 * This function is documented in acfml.php > get_converted_urls
 */
function acfml_get_converted_urls(?string $url = null): array
{
    return \acfml()->get_converted_urls($url);
}

/**
 * This function is documented in inc/class.post-types-controller.php > get_post_urls
 *
 * @param WP_Post $post
 * @return array
 */
function acfml_get_post_permalinks(WP_Post $post): array
{
    return \acfml()->post_types_controller->get_post_urls($post);
}

/**
 * This function is documented in acfml.php > home_url
 *
 */
function acfml_home_url(string $path = '', ?string $lang = null): string
{
    return \acfml()->home_url($path, $lang);
}

/**
 * This function is documented in acfml.php > get_curret_language
 */
function acfml_get_current_language()
{
    return \acfml()->get_current_language();
}

/**
 * This function is documented in acfml.php > get_languages
 */
function acfml_get_languages(?string $format = null)
{
    return \acfml()->get_languages($format);
}

/**
 * Like ACF's get_field(), but with the language slug specified
 *
 * @param string $language        The language slug, e.g. "en" or "de"
 * @param string $selector        The field name or key.
 * @param string|int $post_id     The post_id of which the value is saved against.
 * @param bool $format_value      Whether or not to format the value as described above.
 * @param bool $escape_html       If we're formatting the value, make sure it's also HTML safe.
 *
 * @return mixed
 */
function acfml_get_field(
    string $language,
    string $selector,
    int|string $post_id,
    bool $format_value = true,
    bool $escape_html = false
) {
    return \acfml()->get_field_in_language(...\func_get_args());
}
