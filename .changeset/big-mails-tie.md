---
"acf-multilingual": patch
---

Fix injecting previous monolingual values after making an existing field multilingual: Actually check if the field already contains a translated value before assuming that it should be overridden.
