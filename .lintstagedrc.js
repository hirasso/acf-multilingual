/**
 * This runs anytime before committing files
 */
export default {
  "**/*.{js,css}": ["prettier --write"],
  "**/*.php": [
    "composer format",
    () => "composer analyse", // ← ignore files (otherwise pest files would be analysed, too)
    () => "tools/make-pot.sh", // ← ignore files
    () => "git add ./languages", // ← ignore files
  ],
};
