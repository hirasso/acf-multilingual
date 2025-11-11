---
"acf-multilingual": minor
---

Add new API function `acfml_get_field()`:

```php
/** Get a field in a specific (not currently active) language */
$value = acfml_get_field('en', 'field_selector', $post_id);
```