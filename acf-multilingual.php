<?php

/**
 * Plugin Name: ACF Multilingual
 * Version: 2.0.2
 * Author: Rasso Hilber
 * Description: A lightweight solution to support multiple languages with WordPress and Advanced Custom Fields
 * Author URI: https://rassohilber.com
 * Text Domain: acfml
 * Requires PHP: 8.2
 * Domain Path: /lang
**/

if (!\defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

use Hirasso\ACFML\ACFMultilingual;
use Hirasso\ACFML\Config;

\define('ACFML', true);
\define('ACFML_PATH', \plugin_dir_path(__FILE__));
\define('ACFML_BASENAME', \plugin_basename(__FILE__));
\define('ACFML_URL', \plugins_url('/', __FILE__));

/**
 * Require the autoloader
 * - vendor/autoload.php in development (composer)
 * - autoload.dist.php in production (not composer)
 */
require_once match(\is_readable(__DIR__ . '/vendor/autoload.php')) {
    true => __DIR__ . '/vendor/autoload.php',
    default => __DIR__ . '/autoload.dist.php'
};

/**
 * The public API
 */
require_once(__DIR__ . '/api.php');

/**
 * acfml
 *
 * The main function responsible for returning the one true acfml instance to functions everywhere.
 * Use this function like you would a global variable, except without needing to declare the global.
 *
 * Example: <?php $acfml = acfml(); ?>
 *
 * @param	void
 * @return ACFMultilingual
 */
function acfml(): ACFMultilingual
{
    static $acfml;

    // Instantiate only once.
    if (isset($acfml)) {
        return $acfml;
    }

    $config = new Config();
    $config->load();

    $acfml = new ACFMultilingual($config);
    $acfml->initialize();

    return $acfml;
}

\add_action('plugins_loaded', 'acfml'); // Instantiate
