#!/usr/bin/env bash

wp i18n make-pot . languages/acfml.pot \
  --include="src,acf-multilingual.php,api.php" \
  --slug="acf-multilingual" \
  --headers='{"Report-Msgid-Bugs-To":"https://github.com/hirasso/acf-multilingual/","POT-Creation-Date":""}'