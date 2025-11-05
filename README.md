#### Disclaimer

> [!WARNING]
> This project is open source, which does not mean it includes free support. It is aimed primarily at professional WordPress developers that aren't happy with the currently available multilingual solutions and want to explore a new option

# ACF Multilingual

An experimental plugin for building multilingual websites using [WordPress](https://github.com/WordPress/WordPress) and [Advanced Custom Fields](https://github.com/AdvancedCustomFields/acf) 🌀

## Project status

This is an experimental plugin. There is no real documentation yet, you will have to look at the source code to find out how it works.

## Why did I make this public?

I made this public in the hopes to make it more sustainable and fail-proof. You will probably not want to use this plugin in big corporate projects, just yet (or ever). Use it for a personal website or the likes and report bugs and problems.

## Limitations

Does NOT integrate with plugins that add additional fields to the WordPress Admin, like e.g. Yoast SEO. Works best with fully customized pure WordPress/ACF setups.

# Usage

## Setup

Add as many languages as you like. The languages will be injected into the URL, like this:

```
https://yoursite.tld/your-post/ < default language
https://yoursite.tld/de/dein-eintrag/ < german translation
https://yoursite.tld/es/tu-entrada/ < spanish translation
```

Put a file `acfml.config.json` in your theme root, for example with these contents:

```json
{
  "languages": {
    "en": {
      "locale": "en_US",
      "name": "English"
    },
    "de": {
      "locale": "de_DE",
      "name": "Deutsch"
    }
  },
  "post_types": {
    "page": true,
    "project": {
      "de": {
        "rewrite_slug": "projekt",
        "archive_slug": "projekte"
      }
    }
  },
  "taxonomies": {
    "filter": true
  }
}
```

This config will make:

- English the default language
- Deutsch the second language
- The post type "page" translatable
- The post type "project" translatable, with custom rewrite slugs for german
- The custom taxonomy "filter" translatable

## API

To get an idea about what the plugin can do, it's probably quickest to have a look at [the API](https://github.com/hirasso/acf-multilingual/blob/main/src/api.php).

## Make built-in ACF fields multilingual

Optionally set ACF fields to be `multilingual`, so that they can be translated for every language. like e.g. `Text`, `Textarea`, `WYSIWYG`, ... (for the full list see `$multilingual_field_types` in the class `FieldsController`). If you use `vinkla/extended-acf`, you can make fields multilingual like so:

```php
\Extended\ACF\Fields\Text::make('Text')
    ->withSettings(['acfml_multilingual' => true]);
```

If a field is not marked multilingual, it will display the same value in both languages.

# Todo

- Make Tests work again
- Multilingual slugs for taxonomy terms
- A more complete readme ;)
