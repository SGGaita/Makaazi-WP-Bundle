# REST API

Namespace: `makaazi/v1` (for example `https://example.com/wp-json/makaazi/v1/listing`). All responses are JSON. Endpoints that return markup return `{ "html": "…" }` so the front end can swap it in.

For data access (headless, apps), the standard `wp/v2/property` endpoint is available too (`show_in_rest` is on), with public `_mk_*` meta under `meta`. Exact coordinates are not exposed there.

| Method | Route | Auth | Purpose |
|---|---|---|---|
| GET | `/listing` | public | One page of the Properties listing |
| GET | `/cards` | public | Next page of cards for "Load more" |
| POST | `/enquiry` | public, rate-limited | Send an enquiry |
| GET | `/saved/cards` | public | Cards for a list of IDs (favourites page) |
| GET | `/saved/compare` | public | Compare table for up to 4 IDs |
| GET | `/saved/mini` | public | Title, image, price for IDs (compare bar) |
| GET, POST | `/me/saved` | signed in + `X-WP-Nonce` | Read or replace the user's favourites and compare lists |
| POST | `/upload` | signed in, can list | Upload a photo from the dashboard form |

## GET /listing

Query: any filter (`status`, `location`, `type`, `use`, `category`, `features[]`, `min_price`, `max_price`, `beds`, `baths`, `parking`, `q`, `sort`, `pg`), plus:

- `view`: `grid`, `list` or `map`
- `cfg`: JSON of the listing config (the shortcode's options, from `data-config`)
- `base`: the page URL to build links against (must be on this site)

Returns `{ html }` with the results bar, cards or map, and pagination.

## GET /cards

- `atts`: JSON of `[makaazi_properties]` attributes
- `page`: page number (2 or more)

Returns `{ html, more }`, where `more` is true while there are further pages.

## POST /enquiry

Body (form or JSON): `name`, `phone`, `email` (optional), `message`, `property_id` (0 for general), `agent_id` (optional), plus the spam fields `website` (must be empty) and `ts` (form render time in seconds; submissions within 3 seconds, or with `website` filled, are silently dropped). One IP address can send 5 enquiries per hour; more returns 429. Returns `{ ok: true, message }` or an error with status 400/429.

Fires `makaazi_enquiry_created`.

## Saved lists

- `GET /saved/cards?ids=12,15,18`, `GET /saved/compare?ids=12,15`, `GET /saved/mini?ids=12,15`.
- `GET /me/saved` returns `{ favs: [ids], compare: [ids] }`.
- `POST /me/saved` with JSON `{ "favs": [ids], "compare": [ids] }` replaces either list (send only the one you change). Requires the `wp_rest` nonce in `X-WP-Nonce`; `MAKAAZI.nonce` holds it on the front end.

IDs are cleaned: only published properties, no duplicates, max 200 favourites and 4 compare.

## POST /upload

Multipart field `file` (JPG, PNG or WebP, up to 8 MB). Requires a signed-in user who may list (agent or approved owner) and the `wp_rest` nonce. Returns `{ id, thumb }` (attachment ID and thumbnail URL) for the new attachment, owned by the user. The dashboard form resizes photos in the browser before upload.
