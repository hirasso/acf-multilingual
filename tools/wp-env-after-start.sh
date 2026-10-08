#!/usr/bin/env bash
set -e

# Activate the plugin (mounted via "mappings", so not auto-activated) and the theme
wp-env run cli wp plugin activate acf-multilingual
wp-env run cli wp theme activate twentytwentyfive
# Rename the plugin config file
wp-env run cli bash -c 'cp /var/www/html/wp-content/plugins/acf-multilingual/acfml.config.sample.json /var/www/html/wp-content/themes/twentytwentyfive/acfml.config.json'
