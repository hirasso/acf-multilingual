#!/usr/bin/env bash
set -e

wp-env run cli wp theme activate twentytwentyfive
wp-env run cli bash -c 'cp /var/www/html/wp-content/plugins/acf-multilingual/acfml.config.sample.json /var/www/html/wp-content/themes/twentytwentyfive/acfml.config.json'
wp-env run cli sed -i "/define( 'WP_LANG_DIR'/d" /wordpress-phpunit/wp-tests-config.php