# Shortcodes and Elementor

## Shortcodes

The full list with every attribute is generated from code on **Properties › Shortcodes** (data in `Makaazi_Shortcode_Docs::all()` plus the `makaazi_shortcode_docs` filter). Main ones:

| Shortcode | Renderer |
|---|---|
| `[makaazi_properties]` | `Makaazi_Shortcodes::properties()` → `Makaazi_Render::cards()` |
| `[makaazi_property_listing]` | `Makaazi_Render::listing()` |
| `[makaazi_search]` | `Makaazi_Render::search_form()` |
| `[makaazi_locations]` | `Makaazi_Shortcodes::locations()` |
| `[makaazi_property]` and `[makaazi_property_{header,gallery,facts,description,details,features,map,contact}]` | `Makaazi_Render` property parts |
| `[makaazi_related_properties]` | `Makaazi_Related` + cards |
| `[makaazi_agents]`, `[makaazi_agent]` | `Makaazi_Agents` |
| `[makaazi_favourites]`, `[makaazi_compare]`, `[makaazi_favourites_link]` | `Makaazi_Shortlist` |
| `[makaazi_dashboard]`, `[makaazi_account_link]` | `Makaazi_Accounts` |
| `[makaazi_unit_toggle]` | m² / ft² switch |
| `[makaazi_slider]` | Hero Slider plugin, `MKS_Render::render()` |

Renderers call `Makaazi_Assets::enqueue()` themselves, so a shortcode works on any page without extra setup.

### Adding your own shortcode

```php
add_action( 'init', function () {
	add_shortcode( 'makaazi_price_ticker', function ( $atts ) {
		$a = shortcode_atts( array( 'count' => 5 ), $atts, 'makaazi_price_ticker' );
		Makaazi_Assets::enqueue();
		$q = new WP_Query( Makaazi_Query::args( Makaazi_Query::parse( array( 'sort' => 'newest' ) ), (int) $a['count'] ) );
		// … build HTML with makaazi_price( $id ) etc.
	} );
} );
add_filter( 'makaazi_shortcode_docs', function ( $docs ) {
	$docs[] = array(
		'tag'      => 'makaazi_price_ticker',
		'group'    => 'Custom',
		'desc'     => 'Latest prices in a ticker.',
		'atts'     => array( 'count' => array( '5', 'How many' ) ),
		'examples' => array( '[makaazi_price_ticker count="8"]' ),
	);
	return $docs;
} );
```

Using the `makaazi_` prefix means the block theme's Shortcode-block fix applies to it too.

## Elementor widgets

`includes/elementor/` loads only when Elementor is active (`Makaazi_Elementor::init()` only adds Elementor hooks).

| Widget | Name | Shortcode |
|---|---|---|
| Hero Slider | `makaazi-slider` | `[makaazi_slider]` (only when the Hero Slider plugin is active) |
| Properties | `makaazi-properties` | `[makaazi_properties]` |
| Property Listing | `makaazi-property-listing` | `[makaazi_property_listing]` |
| Property Search | `makaazi-search` | `[makaazi_search]` |
| Locations | `makaazi-locations` | `[makaazi_locations]` |
| Property Section | `makaazi-property-section` | part shortcodes, `[makaazi_property]`, `[makaazi_related_properties]` |
| Agents | `makaazi-agents` | `[makaazi_agents]` |
| Favourites & Compare | `makaazi-shortlist` | `[makaazi_favourites]`, `[makaazi_compare]`, `[makaazi_favourites_link]` |
| My Account | `makaazi-account` | `[makaazi_dashboard]`, `[makaazi_account_link]` |

### How a widget works

`Makaazi_Elementor_Widget` (`class-widget.php`) extends `\Elementor\Widget_Base`. A widget only declares its shortcode and fields:

```php
class My_Widget extends Makaazi_Elementor_Widget {
	protected function tag() { return 'makaazi_properties'; }
	public function get_title() { return 'Latest rentals'; }
	public function get_icon() { return 'eicon-posts-grid'; }
	protected function fields() {
		return array(
			'source'  => array( 'type' => 'select', 'label' => 'Show', 'options' => array( 'rent' => 'To rent' ), 'default' => 'rent' ),
			'count'   => array( 'type' => 'number', 'label' => 'How many', 'default' => 4 ),
			'columns' => array( 'type' => 'select', 'label' => 'Per row', 'options' => array( '2' => '2', '4' => '4' ), 'default' => '4', 'responsive' => true ),
		);
	}
}
add_filter( 'makaazi_elementor_widgets', function ( $w ) {
	$w['My_Widget'] = 'Latest rentals';
	return $w;
} );
```

Declare the class after Elementor's base classes exist, for example inside an `elementor/widgets/register` callback with priority 5, or in a file you `require` there.

The base class then:

- builds controls from `fields()` (types `text`, `textarea`, `number`, `select`, `multi`, `switch`; `responsive` adds tablet/mobile values that become `columns_tablet` / `columns_mobile` attributes),
- groups them into the sections from `sections()` using each field's `section` key,
- adds a **Style** tab: colour controls that set the `--mk-*` variables on the widget, a corner radius, and typography controls for the selectors in `typography_targets()`,
- renders `do_shortcode()` with the settings as attributes (`multi` values are joined with commas, switches become `yes`/`no`, empty values are left out so shortcode defaults apply),
- shows a dashed placeholder in the editor when the shortcode prints nothing,
- returns `true` from `is_dynamic_content()` so Elementor never caches listing HTML,
- links the panel's "Need help?" to the matching Help Center article (`help_article()`).

Fields with `'virtual' => true` are controls that are not attributes (the Property Section's `part`, for example); override `render()` to use them.

### Scripts in the editor

Elementor re-renders a widget every time a control changes. `assets/js/elementor.js` listens for `frontend/element_ready/widget` and, for `makaazi-*` widgets, calls `window.MakaaziInit( el )` and `window.MakaaziSliderInit( el )`. Both are idempotent. In the preview, `Makaazi_Elementor::preview_assets()` loads all Makaazi CSS/JS and Leaflet up front, because a widget can be dropped in at any time.

Widgets declare their handles in `get_script_depends()` / `get_style_depends()` (`makaazi`, `leaflet`, `makaazi-slider`, `makaazi-elementor`), so on the live site Elementor loads only what the page uses.
