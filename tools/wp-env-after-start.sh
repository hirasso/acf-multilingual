#!/usr/bin/env bash
set -e

wp-env run cli wp theme activate twentytwentyfive
wp-env run tests-cli wp core language install ar
wp-env run tests-cli wp core language install en_US
wp-env run tests-cli wp core language install de_DE
pnpm run wp:cli -- bash -c 'cp acfml.config.sample.json /var/www/html/wp-content/themes/twentytwentyfive/acfml.config.json'