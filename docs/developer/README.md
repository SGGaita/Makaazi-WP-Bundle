# Makaazi developer documentation

Makaazi is a property listing template for WordPress by [Ascension Dynamics](https://ascensiondynamics.co/). It has three parts:

| Package | Folder | Text domain | What it does |
|---|---|---|---|
| Makaazi (block theme) | `themes/makaazi-theme` | `makaazi-theme` | Layout, header/footer, templates built from shortcodes |
| Makaazi Core | `plugins/makaazi-core` | `makaazi` | Properties, search, property pages, enquiries, agents, owners, favourites & compare, Help, Elementor widgets |
| Makaazi Hero Slider | `plugins/makaazi-hero-slider` | `makaazi-slider` | `mk_slider` post type and the `[makaazi_slider]` shortcode |

This folder is for developers. Site staff, agents and owners use the in-admin Help instead (**Properties › Help** and the **Help** tab in My account).

## Contents

1. [Architecture](architecture.md): how the pieces fit, request flow, and why everything is a shortcode
2. [Data model](data-model.md): post types, taxonomies, meta keys, options, user meta
3. [Hooks reference](hooks.md): every filter and action, with examples
4. [Templates and theming](templates.md): overriding the card, CSS variables, the theme's templates
5. [REST API](rest-api.md): the `makaazi/v1` endpoints
6. [Roles and capabilities](roles-capabilities.md): agents, owners, feature switches
7. [Shortcodes and Elementor](shortcodes-elementor.md): adding attributes and writing your own widget
8. [Help Center content](help-content.md): adding or changing Help articles
9. [Build, test and release](build-test.md): local setup, checks, packaging

## Requirements

- WordPress 6.6 or newer (tested to 7.0), PHP 7.4 or newer.
- No build step. JavaScript is plain ES5 and CSS is hand-written; Leaflet 1.9.4 ships in `assets/vendor`.
- Elementor is optional. The widgets load only when it is active.

## Ground rules

- **Do not edit the plugins or theme in place on a client site.** Use the hooks, a child theme, or a small site plugin (`wp-content/mu-plugins/makaazi-custom.php`), so updates do not overwrite your changes.
- Prefixes: PHP functions `makaazi_`, classes `Makaazi_` (slider: `MKS_`), meta `_mk_`, options `makaazi_`, CSS `.mk-` (slider `.mks-`), REST `makaazi/v1`.
- Code follows the WordPress Coding Standards (tabs, Yoda conditions, escaping late).
