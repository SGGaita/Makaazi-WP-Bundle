# Templates and theming

## Overriding the property card

The card is the only PHP template file, because it is the piece most sites want to change.

1. Copy `plugins/makaazi-core/templates/card.php` to `your-theme/makaazi/card.php` (a child theme of Makaazi, or any theme).
2. Edit the copy. It receives `$card`, the array built by `Makaazi_Render::card_data()` (see `makaazi_card_data` in [Hooks](hooks.md#makaazi_card_data)).

`makaazi_locate_template( 'card.php' )` checks `makaazi/card.php` in the child and parent theme first, then falls back to the plugin. Keep these hooks in your copy so the scripts keep working:

| Markup | Used by |
|---|---|
| `data-mk-strip` on the photo strip, `.mk-strip__dots` | Swipeable photos |
| `data-mk-fav="{id}"` button | Favourites heart |
| `data-mk-cmp="{id}"` button | Compare |
| `.mk-card__price` containing `makaazi_price()` output | Price and unit toggle |
| `data-sqm` elements from `makaazi_area_html()` | m² / ft² switch |

Prefer the `makaazi_card_data` filter for small changes (extra spec, different button text). Override the file only for layout changes, and re-check it after updates.

## CSS custom properties

Every Makaazi block declares its colours as custom properties that follow the theme's presets:

| Variable | Theme preset | Default |
|---|---|---|
| `--mk-primary` | `primary` | `#13223F` |
| `--mk-deep` | `primary-dark` | `#0B1528` |
| `--mk-accent` | `accent` | `#B9924A` |
| `--mk-ink` | `contrast` | `#1A1F2B` |
| `--mk-muted` | `muted` | `#5E6678` |
| `--mk-line` | `line` | `#E2E5EA` |
| `--mk-surface` | `surface` | `#F4F5F7` |
| `--mk-bg` | `base` | `#FFFFFF` |
| `--mk-radius` | | `6px` |
| `--mk-display` | `display` font | Montserrat |

So the quickest rebrand is to change the colours in **Appearance › Editor › Styles** (or `theme.json` in a child theme). Any theme that defines presets with the same slugs gets the same result.

To override in CSS, target the blocks themselves, because the variables are declared on `.mk-block, .mk-listing, .mk-single, .mk-search, .mk-property, .mk-card` (not on `:root`):

```css
.mk-block, .mk-listing, .mk-single, .mk-search, .mk-property, .mk-card {
	--mk-primary: #7a1f3d;
	--mk-radius: 0;
}
```

The slider uses `--mks-accent`, `--mks-deep` and `--mks-display`, declared on `.mks-wrap`.

## The block theme

```
themes/makaazi-theme/
├─ theme.json            colours, fonts (bundled Montserrat + Inter), spacing, layout sizes
├─ style.css             header only
├─ functions.php         asset loading, logo fallback, "install the plugins" notice
├─ assets/css/theme.css  header, footer, page chrome
├─ parts/header.html     → pattern makaazi/header
├─ parts/footer.html     → pattern makaazi/footer
├─ patterns/             header.php, footer.php, home.php (PHP so strings are translatable)
└─ templates/            front-page, page, page-full, single, index, archive, search, 404,
                         single-property, archive-property, taxonomy,
                         single-mk_agent, archive-mk_agent
```

Templates are mostly Shortcode blocks, so they can be rearranged in the Site Editor. For example, to move the map above the details on property pages, open **Appearance › Editor › Templates › Single item: Property** and drag the `[makaazi_property_map]` block.

Changes made in the Site Editor are saved in the database and override the files. **Templates › … › Reset** returns to the file version.

### Child theme

```
makaazi-child/
├─ style.css      /* Theme Name: Makaazi Child  Template: makaazi-theme */
├─ theme.json     {"version":3,"settings":{"color":{"palette":[ … ]}}}
├─ makaazi/card.php            (optional card override)
└─ templates/single-property.html   (optional template override)
```

Block themes merge a child's `theme.json` over the parent's, and a child template with the same name replaces the parent's.

### Using Makaazi with another theme

Makaazi Core works with any theme. Create pages with the shortcodes (or Elementor widgets). For property pages, either:

- add `single-property.php` / `archive-property.php` to your theme that call `echo do_shortcode( '[makaazi_property]' );` and `[makaazi_property_listing]`, or
- in Elementor Pro, build a Single template with the **Property Section** widget set to "Current property".
