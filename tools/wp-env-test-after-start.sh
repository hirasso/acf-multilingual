#!/usr/bin/env bash
set -e

# Delete the default 'WP_LANG_DIR' define
wp-env --config .wp-env.test.json run cli sed -i "/define( 'WP_LANG_DIR'/d" /wordpress-phpunit/wp-tests-config.php
