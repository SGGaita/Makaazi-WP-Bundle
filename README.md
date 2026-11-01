# Makaazi: WordPress property listing template

Property listing template by [Ascension Dynamics](https://ascensiondynamics.co/): the Makaazi block theme plus two plugins, built around shortcodes. The demo content uses a sample agency, Makaazi Homes.

| Folder | What it is |
|---|---|
| `themes/makaazi-theme` | Block theme: brand colours and fonts (theme.json), header, footer, and templates assembled from Makaazi shortcodes |
| `plugins/makaazi-core` | Properties, taxonomies, Property Details box, settings, enquiries, agents, owner accounts, favourites & compare, demo importer, Help Center, Elementor widgets and all listing shortcodes |
| `plugins/makaazi-hero-slider` | Hero sliders with a Buy/Rent search bar, placed with `[makaazi_slider]` |

This repository is the site's `wp-content` folder. `.gitignore` tracks only the three Makaazi folders and `docs/`.

## Documentation

- **Site staff, agents and owners:** in WordPress, under **Properties › Help**, the **Help** button on every Makaazi screen, and the **Help** tab in My account.
- **Developers:** [`docs/developer/`](docs/developer/README.md), covering architecture, data model, hooks, templates, REST API, roles, shortcodes and Elementor, Help content, and build/test.

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
[makaazi_favourites]  [makaazi_compare]  [makaazi_favourites_link]
[makaazi_agents]  [makaazi_agent]  [makaazi_dashboard]  [makaazi_account_link]
```

## Notes

- Exact coordinates are never sent to the browser. The map shows a shaded area around an offset centre unless "Show the exact pin" is ticked.
- Enquiries are saved under **Properties › Enquiries** and emailed to the address in Settings. XAMPP does not send mail by default, so check the Enquiries screen when testing locally.
- Card template can be overridden by copying `plugins/makaazi-core/templates/card.php` to `themes/makaazi-theme/makaazi/card.php`.
- Maps use OpenStreetMap tiles and need an internet connection.
- Roles: **Agent** (edits own and assigned listings, front-end dashboard plus limited admin) and **Property owner** (front-end only, never sees wp-admin). Pending accounts can be activated from **Users** (Activate link).
- Form fields avoid WordPress query-var names (`name`, `year`, …); posting those makes WordPress return a 404.

## Elementor (optional)

With Elementor active, a **Makaazi** category appears in the widget panel: Hero Slider, Properties, Property Listing, Property Search, Locations, Property Section, Agents, Favourites & Compare, My Account. Each has the shortcode's options plus Style controls for colours, corners and fonts.

## Roadmap

Done: favourites and compare (device storage, synced to the account for signed-in users, shareable ?ids= links).
Done: agent profiles and sign-in, owner sign-up (open / approval / invite), 5-step listing form, owner and agent dashboard, moderation. Each can be switched off in Properties › Settings › Features.
Done: Help Center, contextual help, dashboard Help, developer docs, extension hooks, Elementor widgets.
Next: see `docs/makaazi-development-plan.md` in the project.
