# Data model

## Post types

| Post type | Public URL | Registered by | Notes |
|---|---|---|---|
| `property` | `/property/{slug}/`, archive `/properties/` | Core | `capability_type` `array( 'property', 'properties' )` with `map_meta_cap`. Supports title, editor, excerpt, thumbnail, revisions, author, custom fields. Classic editor screen with the Property Details meta box. |
| `mk_agent` | `/agents/{slug}/`, archive `/agents/` | Core | Public only while **Agents** is switched on. Title = name, content = bio, featured image = photo. |
| `mk_enquiry` | none | Core | Private record of every enquiry. Listed under Properties › Enquiries. |
| `mk_slider` | none | Hero Slider | Slides and settings stored as post meta. Listed under Properties › Hero Sliders. |

## Taxonomies (all attached to `property`)

| Taxonomy | URL base | Hierarchical | Admin box | Default terms |
|---|---|---|---|---|
| `property_status` | `/status/` | no | radio | `sale` (For Sale), `rent` (For Rent), `stay` (Short Stay) |
| `property_use` | `/use/` | no | radio | `residential`, `commercial` |
| `property_type` | `/property-type/` | no | radio | Apartment, Maisonette, Townhouse, Villa, Bungalow, Office, Shop, Warehouse, Land |
| `property_category` | `/property-category/` | yes | checkboxes | |
| `property_location` | `/location/` | yes (County › Area) | radio | |
| `property_feature` | `/feature/` | yes | checkboxes | |

Location terms can carry an image (term meta `mk_image`, an attachment ID) used by `[makaazi_locations]` tiles.

The listing statuses and their labels, default price period and closed state come from `makaazi_statuses()` (filter `makaazi_statuses`). Statuses switched off in Settings are left out of search, filters and forms, and their listings are excluded from results.

Query values use **slugs**: `?status=rent&location=westlands&type=apartment`.

## Property meta

Registered in `Makaazi_Meta::register()` with `_mk_` prefix. "REST" means `show_in_rest`.

| Key | Type | REST | Meaning |
|---|---|---|---|
| `_mk_price` | number | yes | Asking price in the site currency |
| `_mk_price_period` | string | yes | `total` (sale), `month`, `year`, `night` |
| `_mk_price_on_request` | boolean | yes | Show "Price on request" |
| `_mk_availability` | string | yes | `available`, `under_offer`, `sold`, `let`. Sold and let are kept online but excluded from search unless `include_closed` |
| `_mk_area_sqm` | number | yes | Floor area, **always stored in m²**. Display converts with `makaazi_area_html()` |
| `_mk_land_size` / `_mk_land_unit` | number / string | yes | Land size and unit (`acres`, `hectares`, `sqm`) |
| `_mk_bedrooms`, `_mk_bathrooms`, `_mk_parking` | int / number / int | yes | Specs (bathrooms allow .5) |
| `_mk_year_built` | integer | yes | |
| `_mk_featured` | boolean | yes | Featured flag |
| `_mk_gallery` | array | yes | Attachment IDs in order; first is the cover. Use `makaazi_gallery_ids( $id )` |
| `_mk_location_label` | string | yes | Public road/landmark text |
| `_mk_lat`, `_mk_lng` | number | **no** | Exact pin. Never sent to the browser unless `_mk_exact_pin` |
| `_mk_map_radius` | integer | yes | Public privacy radius in metres |
| `_mk_exact_pin` | boolean | yes | Show the exact pin |
| `_mk_ref` | string | yes | Reference, e.g. `MK-00101`, created on first save from `makaazi_ref_counter` |
| `_mk_attributes` | array | yes | Extra Details rows: `array( array( 'label' => …, 'value' => … ) )` |
| `_mk_video_url` | string | yes | Video or virtual tour link |
| `_mk_leads` | integer | no | Enquiry count |
| `_mk_views` | integer | no | Page views (staff, the listing's author and bots are not counted) |
| `_mk_agent_id` | integer | no | Assigned `mk_agent` post ID |
| `_mk_rejected` | string | no | Moderator's reason when a submission was sent back |

Read meta with `makaazi_meta( $id, 'price' )` (prefix added for you; returns registered defaults).

## Agent meta (`mk_agent`)

`_mk_role` (job title), `_mk_office`, `_mk_phone`, `_mk_whatsapp`, `_mk_email`, `_mk_facebook`, `_mk_instagram`, `_mk_linkedin`, `_mk_user_id` (linked WordPress user who can sign in). Use `Makaazi_Agents::data( $agent_id )` for a normalised array and `Makaazi_Agents::for_user( $user_id )` to go from a user to their agent profile.

## Enquiry meta (`mk_enquiry`)

`_mk_property_id`, `_mk_agent_id`, `_mk_name`, `_mk_phone`, `_mk_email`. The message is `post_content`. The dashboard finds a user's enquiries by matching `_mk_property_id` against the listings they own (or, for agents, their `_mk_agent_id`).

## User meta

| Key | Meaning |
|---|---|
| `_mk_favs` | Favourite property IDs (synced from the browser on sign-in) |
| `_mk_compare` | Compare IDs (max 4) |
| `_mk_phone` | Phone number given at sign-up |
| `_mk_pending` | Set while an owner account waits for admin approval |
| `_mk_verify` | Email verification token |

## Options

| Option | Meaning |
|---|---|
| `makaazi_settings` | All plugin settings (array, see below) |
| `makaazi_cache_ver` | Cache version; bumped by `makaazi_bump_cache()` |
| `makaazi_ref_counter` | Last reference number used |
| `makaazi_roles_ver` | Installed roles version (roles are re-installed when `Makaazi_Roles::VER` increases) |
| `makaazi_flush_rules` | Flag: flush rewrite rules on the next request (set when Agents is toggled) |
| `makaazi_demo_imported`, `makaazi_demo_terms`, `makaazi_demo_prev_front` | Demo importer bookkeeping, used to remove only demo content |

## Settings

`makaazi_settings( 'key' )` returns one value; `makaazi_settings()` returns all. Keys and defaults:

| Key | Default | Settings tab |
|---|---|---|
| `currency` | `KES` | General |
| `short_price` | `0` | General (KES 85M style) |
| `unit` | `sqm` | General (`sqm` or `sqft`) |
| `unit_toggle` | `1` | General |
| `ref_prefix` | `MK-` | General |
| `button_text` | `View Property` | Cards |
| `card_images` | `5` | Cards |
| `card_show` | category, use, type, location, beds, baths, parking, area, badges | Cards |
| `image_ratio` | `4:3` | Cards |
| `new_days` | `7` | Cards |
| `per_page` | `12` | Properties page |
| `default_sort` | `newest` | Properties page |
| `filters` | status, location, type, use, category, price, beds, baths, parking, features, keyword | Properties page |
| `agents_enabled`, `agent_review`, `enquiry_cc_admin` | `1`, `0`, `1` | Features |
| `owners_enabled`, `owner_registration`, `owner_email_verify`, `owner_autopublish`, `owner_edit_review` | `1`, `open`, `1`, `0`, `1` | Features (`owner_registration`: `open`, `approval`, `invite`) |
| `max_photos`, `dashboard_page` | `20`, `0` | Features |
| `card_favourite`, `card_compare`, `favourites_page`, `compare_page` | `1`, `1`, `0`, `0` | Favourites & compare |
| `contact_name`, `contact_phone`, `whatsapp`, `enquiry_email` | | Property page |
| `privacy_radius`, `related_count`, `related_same_status` | `500`, `8`, `1` | Property page |
| `map_tiles`, `map_center` | OSM tiles, Nairobi | Property page |
| `brackets_sale`, `brackets_rent`, `brackets_stay` | comma-separated amounts | Price brackets |
| `stays_enabled` | `1` | Features (Short Stay listing type) |

Add your own keys with the `makaazi_default_settings` filter (they are then readable with `makaazi_settings()`); see [Hooks](hooks.md).

## Search index table

`{prefix}mk_index` (`Makaazi_Index`) has one row per property with typed, indexed columns: `post_id`, `status`, `price`, `por` (price on request), `beds`, `baths`, `parking`, `area`, `featured`, `closed` (sold or let), `agent_id`.

- Rows are written at the end of any request that saves a property, changes one of those meta keys or its status term, or deletes it.
- A full build runs on install (in batches through WP-Cron for large sites); searches use meta queries until it completes (`makaazi_index_ready`).
- Rebuild: **Properties › Settings › General › Rebuild search index**, or `wp makaazi index rebuild` (`wp makaazi index status` shows progress).
- When ready, `Makaazi_Query::args()` moves price, rooms, availability and numeric sorts into the `mk_index` query var, and `Makaazi_Index::clauses()` (`posts_clauses`) joins the table. Listings with "Price on request" sort after priced ones.
- Turn it off with `add_filter( 'makaazi_use_index', '__return_false' );`.
- Uninstalling the plugin drops this table only; listings and settings stay.

Measured with 2,000 listings (SQLite test site): full Properties page 80–100 ms warm, filter requests 50 ms.

## Image sizes

`makaazi-card` (cards) and `makaazi-wide` (gallery mosaic, lightbox, slider fallback). Regenerate thumbnails after changing them.
