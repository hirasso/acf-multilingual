/**
 * This runs inside the ddev container, since PNPM is
 * installed inside it, not on the host machine
 */
export default {
  "**/*.{js,css}": ["prettier --write"],
  "**/*.php": [
    "vendor/bin/phpstan analyze --memory-limit=1G",
    "vendor/bin/pint",
  ],
};
