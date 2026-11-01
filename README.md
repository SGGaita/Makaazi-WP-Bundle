# Makaazi Homes: WordPress template

Property listing template for Makaazi Homes: a block theme plus two plugins, built around shortcodes.

| Folder | What it is |
|---|---|
| `themes/makaazi-theme` | Block theme: brand colours and fonts (theme.json), header, footer, and templates assembled from Makaazi shortcodes |
| `plugins/makaazi-core` | Properties, taxonomies, Property Details box, settings, enquiries, demo importer, shortcode reference and all listing shortcodes |
| `plugins/makaazi-hero-slider` | Hero sliders with a Buy/Rent search bar, placed with `[makaazi_slider]` |

This repository is the site's `wp-content` folder. `.gitignore` tracks only the three Makaazi folders.

## Install (XAMPP)

1. Copy or clone into `wp-content` (e.g. `C:\xampp\htdocs\makaazi\wp-content`).
2. **Plugins** › activate **Makaazi Core**, then **Makaazi Hero Slider**.
3. **Appearance › Themes** › activate **Makaazi**.
4. **Properties › Demo Content** › **Import demo content** (15 listings, slider, Home and Contact pages, menu).
5. **Settings › Permalinks** › Save once if property pages return 404.

Requirements: WordPress 6.6+ (tested on 7.0.2), PHP 7.4+ with GD.

## Shortcodes

The full reference with every attribute and copy buttons is in **Properties › Shortcodes**.

```
[makaazi_slider slug="home-hero"]
[makaazi_search fields="location,type,price"]
[makaazi_properties source="featured" count="4" columns="4" title="Featured properties"]
[makaazi_properties source="rent" layout="carousel" button_text="See details"]
[makaazi_property_listing filters="sidebar" views="grid,list,map"]
[makaazi_locations count="6"]
[makaazi_unit_toggle]
[makaazi_property]   (or the separate _header, _gallery, _facts, _details, _features, _map, _contact parts)
[makaazi_related_properties]
```

## Notes

- Exact coordinates are never sent to the browser. The map shows a shaded area around an offset centre unless "Show the exact pin" is ticked.
- Enquiries are saved under **Properties › Enquiries** and emailed to the address in Settings. XAMPP does not send mail by default, so check the Enquiries screen when testing locally.
- Card template can be overridden by copying `plugins/makaazi-core/templates/card.php` to `themes/makaazi-theme/makaazi/card.php`.
- Maps use OpenStreetMap tiles and need an internet connection.

## Roadmap

Round 2: agent profiles, favourites and compare, owner submission and dashboard, Elementor widgets. See `docs/makaazi-development-plan.md` in the project.
