#!/usr/bin/env bash
set -e

wp-env run cli wp theme activate twentytwentyfive
pnpm run wp:cli -- bash -c 'cp acfml.config.sample.json /var/www/html/wp-content/themes/twentytwentyfive/acfml.config.json'