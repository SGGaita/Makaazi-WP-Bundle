# Changelog

Makaazi property listing template by [Ascension Dynamics](https://ascensiondynamics.co/).

## 1.0.0 (2026-11-01)

- **Short Stay** listing type next to Buy and Rent (nightly prices, own price brackets, search tab, home section, owner form). Switch in Settings › Features.
- **SEO**: Schema.org `RealEstateListing`, `RealEstateAgent`, `BreadcrumbList` and `ItemList`; share previews; titles and canonical URLs for the Properties page; `noindex` for multi-filter searches. Works with **Yoast SEO** (schema graph, snippet variables, sitemap images, content analysis) and **Rank Math**.
- **Search index table** for price, rooms, size and sorts, with background build, Settings button and `wp makaazi index`.
- **Accessibility**: contrast fixes (brass text colour `accent-ink`), heading structure, keyboard photo strips, named links; axe checks in CI.
- **Translations**: `.pot` files for all three text domains.
- Sticky header shadow on scroll; "Developed by Ascension Dynamics" in the footer.
- Tests: 66 PHPUnit tests (WordPress 6.6 and 7.0, PHP 7.4–8.3) and 28 Playwright end-to-end checks; GitHub Actions CI and tagged releases with installable zips.
- Fixes: `[makaazi_search]` now accepts `tabs` and `stay_label`; `/me/saved` accepts form bodies; settings re-read after saving in the same request.

## 0.3.0

Help Center, contextual help, dashboard Help, developer documentation, extension hooks, Elementor widgets.

## 0.2.0

Favourites and compare; agents; owner accounts and submissions with moderation; feature switches.

## 0.1.0

Core listings, property pages, filters and map, hero slider, block theme, demo importer, shortcode reference.
