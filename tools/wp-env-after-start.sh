#!/usr/bin/env bash
set -e

# Activate the plugin (mounted via "mappings", so not auto-activated) and the theme
wp-env run cli wp plugin activate acf-multilingual
wp-env run cli wp theme activate twentytwentyfive
# Rename the plugin config file
wp-env run cli bash -c 'cp /var/www/html/wp-content/plugins/acf-multilingual/acfml.config.sample.json /var/www/html/wp-content/themes/twentytwentyfive/acfml.config.json'
# Delete the default 'WP_LANG_DIR' define
wp-env run cli sed -i "/define( 'WP_LANG_DIR'/d" /wordpress-phpunit/wp-tests-config.php
