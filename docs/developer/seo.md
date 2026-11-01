# SEO and Schema.org

`includes/class-seo.php` (`Makaazi_SEO`).

## What is output

| Page | Schema.org | Own tags (no SEO plugin) |
|---|---|---|
| Property | `RealEstateListing` with `about` (Apartment, House, SingleFamilyResidence, Landform, Accommodation or Place: address, rooms, bathrooms, floor size in m², amenities), `offers` (`Offer` with price, currency, availability, `UnitPriceSpecification` for month/year/night, `offeredBy` agent or site contact), images, reference; `BreadcrumbList` | description, Open Graph, Twitter card |
| Agent | `RealEstateAgent` (name, photo, phone, email, job title, social links) | description, Open Graph |
| Properties page, taxonomy archives | `ItemList` of the listings matching the current filters | canonical (keeps one clean filter such as `?status=rent`), titles like "Properties to rent" |

Coordinates are added (`geo`) only when the listing's exact pin is public.

Filtered search URLs with more than one filter (or array filters) get `noindex, follow` through `wp_robots`, `wpseo_robots` or `rank_math/frontend/robots`.

## SEO plugin detection

`Makaazi_SEO::plugin()` returns `yoast`, `rankmath`, `seopress`, `aioseo` or `''` (filter `makaazi_seo_plugin`).

| Plugin | Makaazi does |
|---|---|
| none | prints description, OG/Twitter, canonical for archives, JSON-LD, archive titles |
| Yoast SEO | adds nodes to Yoast's graph (`wpseo_schema_graph`, listing linked as the WebPage's `mainEntity`), fills empty meta/OG/Twitter descriptions, adds the cover as OG image, gallery images to the XML sitemap, excludes enquiries/sliders (and agents when off) from sitemaps, registers `%%mk_price%%`, `%%mk_location%%`, `%%mk_status%%`, `%%mk_type%%`, `%%mk_beds%%`, `%%mk_ref%%`, and feeds the Property Details into Yoast's content analysis (`assets/js/yoast.js`) |
| Rank Math | adds nodes to `rank_math/json_ld`, fills empty descriptions, robots, sitemap images |
| SEOPress, AIOSEO | prints only the JSON-LD and the robots rule |

`property` is a public post type with `show_in_rest`, an archive and pretty permalinks, so SEO plugins list it under their content types with their own meta box on the classic property screen.

## Hooks

- `makaazi_schema` `( array $nodes )`: all nodes for the page.
- `makaazi_schema_listing` `( array $node, int $id )`: the RealEstateListing node.
- `makaazi_schema_country` `( string $code )`: `addressCountry`, default `KE`.
- `makaazi_seo_plugin` `( string $plugin )`: return `''` to force Makaazi's own tags.

```php
// Add the number of guests for short stays.
add_filter( 'makaazi_schema_listing', function ( $node, $id ) {
	if ( 'stay' === makaazi_status( $id ) ) {
		$node['about']['occupancy'] = array( '@type' => 'QuantitativeValue', 'maxValue' => 4 );
	}
	return $node;
}, 10, 2 );
```

Validate with Google's Rich Results Test and the Schema Markup Validator after changes.
