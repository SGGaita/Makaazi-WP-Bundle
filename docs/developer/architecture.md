# Architecture

## The big picture

```
themes/makaazi-theme          block theme: templates and patterns, mostly Shortcode blocks
        │  uses
        ▼
plugins/makaazi-core           data + rendering + behaviour
  ├─ includes/                 front end and shared code (always loaded)
  ├─ admin/                    wp-admin screens (loaded when is_admin())
  ├─ includes/elementor/       Elementor widgets (only when Elementor is active)
  ├─ includes/help/            Help Center article content
  ├─ templates/card.php        overridable card template
  └─ assets/                   makaazi.js, makaazi.css, admin.*, help.js, elementor.js, Leaflet
plugins/makaazi-hero-slider    mk_slider post type, slider admin, [makaazi_slider]
```

The theme has no PHP logic for listings. Every section is a shortcode rendered by Makaazi Core, so the same output works in:

- the block theme (Shortcode blocks in templates and patterns),
- any other theme (paste the shortcodes),
- Elementor (each widget renders a shortcode with the panel settings as attributes).

That gives one renderer, one set of options, and one place to fix bugs.

## Core classes

| Class | File | Responsibility |
|---|---|---|
| `Makaazi_Post_Types` | `includes/class-post-types.php` | `property` post type, taxonomies, image sizes, default terms |
| `Makaazi_Meta` | `includes/class-meta.php` | Registers `_mk_*` meta; `after_save()` adds the reference number, normalises numbers, bumps the cache |
| `Makaazi_Query` | `includes/class-query.php` | Turns URL/shortcode filters into `WP_Query` args (`parse()` → `args()`), sorting, counts |
| `Makaazi_Related` | `includes/class-related.php` | Scores similar listings (area, type, category, status, price), cached in a transient |
| `Makaazi_Render` | `includes/class-render.php` | All HTML: cards, search form, listing page, property page parts, contact, section headings |
| `Makaazi_Shortcodes` | `includes/class-shortcodes.php` | Registers shortcodes, defaults, and the Shortcode-block fix (below) |
| `Makaazi_Rest` | `includes/class-rest.php` | `/listing`, `/cards`, `/enquiry` |
| `Makaazi_Enquiries` | `includes/class-enquiries.php` | `mk_enquiry` post type; saves and emails enquiries |
| `Makaazi_Assets` | `includes/class-assets.php` | Registers scripts and styles, `MAKAAZI` JS config; `enqueue()` is called by renderers |
| `Makaazi_Shortlist` | `includes/class-shortlist.php` | Favourites and compare: shortcodes, REST, user-meta sync |
| `Makaazi_Roles` | `includes/class-roles.php` | Capabilities, `mk_agent` / `mk_owner` roles, wp-admin access |
| `Makaazi_Agents` | `includes/class-agents.php` | `mk_agent` post type, profiles, agent box on properties, agent shortcodes |
| `Makaazi_Accounts` | `includes/class-accounts.php` | Sign in / sign up / verify, `[makaazi_dashboard]` |
| `Makaazi_Submission` | `includes/class-submission.php` | Front-end listing form, uploads, moderation, view counts |
| `Makaazi_Help` | `includes/class-help.php` | Help Center, contextual help tabs, dashboard Help tab |
| `Makaazi_Elementor` | `includes/elementor/class-elementor.php` | Elementor category, widget registration, editor assets |

Admin-only: `Makaazi_Admin` (list columns, featured star, location images), `Makaazi_Meta_Box` (Property Details), `Makaazi_Settings`, `Makaazi_Shortcodes_Page`, `Makaazi_Demo`.

Each class has a static `init()` that adds its hooks; `makaazi-core.php` requires the files and calls the `init()` methods in order.

## Request flow: the Properties page

1. The visitor opens `/properties/?status=rent&location=westlands`.
2. `archive-property.html` contains `[makaazi_property_listing]`.
3. `Makaazi_Shortcodes` → `Makaazi_Render::listing()` reads the query string with `Makaazi_Query::parse()`, builds args with `Makaazi_Query::args()` (filter: `makaazi_query_args`), runs `WP_Query` and prints filters, chips, results and pagination. The listing config is stored in `data-config` on the wrapper.
4. `makaazi.js` (`initListing`) takes over. When a filter changes it calls `GET /makaazi/v1/listing` with the form values and config, swaps in the returned HTML and updates the address with `history.pushState`. The page also works without JavaScript: filters are a normal GET form.

## Request flow: a property page

`single-property.html` is a column layout of part shortcodes (`[makaazi_property_header]`, `…_gallery`, `…_facts`, the Content block, `…_details`, `…_features`, `…_map`, `…_contact`, `[makaazi_related_properties]`). Each part reads the current post ID, or an `id` attribute.

The map gets only a **public location**: `makaazi_public_location()` returns a centre offset deterministically from the real pin, inside the privacy radius. Exact `_mk_lat` / `_mk_lng` are registered without REST exposure and are never printed unless the listing has `exact_pin`.

## Two block-theme pitfalls (already handled)

1. **Order of processing.** Block templates run `do_shortcode()` before `do_blocks()`, so a shortcode inside a pattern can print as text, and core runs `wpautop` over Shortcode-block content, which breaks grid markup.
2. **The fix.** `Makaazi_Shortcodes::pre_render_shortcode_block()` hooks `pre_render_block`. For a Shortcode block containing a Makaazi shortcode it returns `do_shortcode( shortcode_unautop( $inner ) )`, which skips core's wrapper. Other shortcodes are left alone.

If you add your own shortcode to the theme, register it with a `makaazi_` prefix to get the same treatment, or render it yourself.

## Caching

- `makaazi_bump_cache()` increments the `makaazi_cache_ver` option whenever a property is saved, deleted or changes status. Cache keys (related listings, counts) include `makaazi_cache_ver()`, so they all expire together. Call it after you change listings in bulk.
- Related listings are cached for 12 hours per property and cache version.
- Elementor widgets declare `is_dynamic_content() === true`, so Elementor's element cache never stores listing HTML.

## Front-end JavaScript

`assets/js/makaazi.js` is one IIFE with small modules: image strips, carousels, unit toggle, search form, listing (AJAX), load more, lightbox, share, enquiry form, maps (Leaflet, lazy via IntersectionObserver), the favourites/compare store `SL`, and the dashboard listing form.

- Config comes from `window.MAKAAZI` (REST base, nonce, unit, tiles, strings).
- Initialisers are idempotent: elements are marked when set up.
- `window.MakaaziInit( rootElement )` sets up HTML added after load. `window.MakaaziSliderInit( rootElement )` does the same for sliders. `assets/js/elementor.js` calls both on `frontend/element_ready/widget` for `makaazi-*` widgets.
- The custom event `mk:updated` (on `document`) re-renders favourite/compare state; dispatch it after you inject cards.

## Settings

All settings are one option, `makaazi_settings`, merged over `makaazi_default_settings()`. Read with `makaazi_settings( 'key' )`. Defaults are translated only after `init`, because capability checks can read settings earlier. See [Data model](data-model.md#settings) for the keys and [Hooks](hooks.md) for `makaazi_default_settings` and `makaazi_settings`.
