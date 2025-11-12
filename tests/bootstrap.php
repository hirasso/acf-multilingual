<?php

/**
 * wp-phpunit bootstrap file
 *
 * @see https://github.com/wp-phpunit/example-plugin/blob/master/tests/bootstrap.php
 */

// Load wp-env's config file in the container, but still use our own wp-phpunit
\putenv('WP_PHPUNIT__TESTS_CONFIG=/wordpress-phpunit/wp-tests-config.php');

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once \dirname(__DIR__) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once \getenv('WP_PHPUNIT__DIR') . '/includes/functions.php';

/**
 * Delete the languages directory
 */
function _cleanup_languages_dir()
{
    echo "\nCleaning up languages directory...\n\n";
    require_once ABSPATH . 'wp-admin/includes/file.php';
    global $wp_filesystem;
    \WP_Filesystem();
    // @phpstan-ignore constant.notFound
    $wp_filesystem->delete(WP_LANG_DIR, true);
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugins()
{
    // Clean up the languages dir before running tests, so we can test downloading them
    \_cleanup_languages_dir();
    // require ACF, which is a dependency of ACFML
    require_once(\dirname(\dirname(\dirname(__FILE__))) . '/advanced-custom-fields/acf.php');
    // require the main plugin file
    require_once(\dirname(\dirname(__FILE__)) . '/acf-multilingual.php');
    // don't autamatically load acfml in tests
    \remove_action('plugins_loaded', 'acfml');
}
\tests_add_filter('muplugins_loaded', '_manually_load_plugins');

// Start up the WP testing environment.
require \getenv('WP_PHPUNIT__DIR') . '/includes/bootstrap.php';
