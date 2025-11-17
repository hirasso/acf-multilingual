/**
 * This runs anytime before committing files
 */
export default {
  "**/*.{js,css}": ["prettier --write"],
  "**/*.php": [
    "vendor/bin/phpstan analyze --memory-limit=1G",
    "vendor/bin/pint",
    "tools/make-pot.sh",
  ],
};
