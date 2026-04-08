/**
 * This runs anytime before committing files
 */
export default {
  "**/*.{js,css}": ["prettier --write"],
  "**/*.php": [
    "pnpm run format:php",
    () => "pnpm run analyse:php", // ← ignore files (otherwise pest files would be analysed, too)
    () => "tools/make-pot.sh", // ← ignore files
    () => "git add ./languages", // ← ignore files
  ],
};
