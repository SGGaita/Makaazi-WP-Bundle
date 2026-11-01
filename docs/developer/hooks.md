# Hooks reference

Put customisations in a child theme's `functions.php` or a small plugin such as `wp-content/mu-plugins/makaazi-custom.php`. Never edit the Makaazi files themselves.

## Filters

### Settings

#### `makaazi_default_settings`
`( array $defaults )`: default settings. Add your own keys, or change a default for new installs.

```php
add_filter( 'makaazi_default_settings', function ( $d ) {
	$d['currency']  = 'USD';
	$d['per_page']  = 9;
	$d['my_option'] = 'x'; // now readable with makaazi_settings( 'my_option' )
	return $d;
} );
```

#### `makaazi_settings`
`( array $settings )`: the merged settings at runtime. Use it to force a value regardless of what is saved (for example on a staging site). It does not change the saved option.

```php
add_filter( 'makaazi_settings', function ( $s ) {
	if ( wp_get_environment_type() !== 'production' ) {
		$s['enquiry_email'] = 'dev@example.com';
	}
	return $s;
} );
```

### Search and listings

#### `makaazi_query_args`
`( array $args, array $filters )`: `WP_Query` arguments for every listing, grid, AJAX page and count. `$filters` holds the parsed filters (`status`, `location`, `type`, `use`, `category`, `features`, `min_price`, `max_price`, `beds`, `baths`, `parking`, `q`, `sort`, `pg`).

```php
// Hide listings older than a year everywhere.
add_filter( 'makaazi_query_args', function ( $args ) {
	$args['date_query'] = array( array( 'after' => '1 year ago' ) );
	return $args;
} );
```

#### `makaazi_sorts`
`( array $sorts )`: sort options (`key => label`). Add a key here and handle it in `makaazi_query_args`:

```php
add_filter( 'makaazi_sorts', function ( $s ) {
	$s['beds_desc'] = __( 'Most bedrooms', 'my-site' );
	return $s;
} );
add_filter( 'makaazi_query_args', function ( $args, $f ) {
	if ( 'beds_desc' === $f['sort'] ) {
		$args['meta_key'] = '_mk_bedrooms';
		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
	}
	return $args;
}, 10, 2 );
```

#### `makaazi_statuses`
`( array $statuses, bool $all )`: the listing types offered in search (`sale`, `rent`, `stay`). Each has `tab`, `badge`, `period`, `closed` and `price_label`. Keys must match `property_status` term slugs. `$all` is true when switched-off types are included (labels of existing listings).

```php
// Rename the Short Stay tab.
add_filter( 'makaazi_statuses', function ( $s ) {
	if ( isset( $s['stay'] ) ) {
		$s['stay']['tab'] = __( 'Holiday lets', 'my-site' );
	}
	return $s;
} );
```

#### `makaazi_related`
`( array $rows, int $property_id )`: similar listings. Each row is `array( 'id' => int, 'why' => string[] )`; `why` holds the short reasons shown on the card ("Same area"). The result is cached, so call `makaazi_bump_cache()` after changing this filter's logic.

### Cards and templates

#### `makaazi_card_data`
`( array $data, int $id, array $options )`: everything `templates/card.php` prints. Useful keys: `title`, `url`, `price`, `meta_line`, `location`, `specs` (array of `icon`, `label`, `html`; `html` is printed as-is, so escape it), `button_text`, `images`, `status_label`, `featured`, `is_new`, `closed`, `favourite`, `compare`.

```php
// Add a "Furnished" spec to every card.
add_filter( 'makaazi_card_data', function ( $card, $id ) {
	if ( has_term( 'furnished', 'property_category', $id ) ) {
		$card['specs'][] = array( 'icon' => 'check', 'label' => __( 'Furnished', 'my-site' ), 'html' => esc_html__( 'Furnished', 'my-site' ) );
	}
	return $card;
}, 10, 2 );
```

#### `makaazi_template_args`
`( array $args, string $name )`: variables passed to any template file (`card.php` today). See [Templates](templates.md).

#### `makaazi_property_description`
`( string $html, int $id )`: the description printed by `[makaazi_property_description]` (page builders).

### Enquiries

#### `makaazi_enquiry_recipients`
`( string[] $to, int $enquiry_id, int $property_id )`: who receives the enquiry email.

```php
add_filter( 'makaazi_enquiry_recipients', function ( $to, $eid, $pid ) {
	if ( has_term( 'commercial', 'property_use', $pid ) ) {
		$to[] = 'commercial@example.com';
	}
	return $to;
}, 10, 3 );
```

### Accounts and dashboard

#### `makaazi_dashboard_tabs`
`( array $tabs, WP_User $user )`: tabs in `[makaazi_dashboard]` (`key => label`). Render a custom tab with `makaazi_dashboard_tab_{key}`.

#### `makaazi_dashboard_tab_{key}`
`( string $html, WP_User $user )`: HTML for a custom tab.

```php
add_filter( 'makaazi_dashboard_tabs', function ( $tabs ) {
	$tabs['payments'] = __( 'Payments', 'my-site' );
	return $tabs;
} );
add_filter( 'makaazi_dashboard_tab_payments', function ( $html, $user ) {
	return $html . '<p>' . esc_html__( 'No payments yet.', 'my-site' ) . '</p>';
}, 10, 2 );
```

### Help, shortcodes and Elementor

#### `makaazi_help_articles`
`( array $articles, string $context )`: Help articles. `$context` is `admin` (Help Center and help tabs) or `account` (dashboard Help tab). See [Help Center content](help-content.md).

#### `makaazi_developer_docs_url`
`( string $url )`: link shown in the Help Center's "For developers" box and the help-tab sidebar. Empty by default (the box then names `docs/developer`).

```php
add_filter( 'makaazi_developer_docs_url', function () {
	return 'https://github.com/your-org/makaazi/tree/main/docs/developer';
} );
```

#### `makaazi_shortcode_docs`
`( array $docs )`: entries on Properties › Shortcodes. Add one for your own shortcode so editors can find it (`tag`, `group`, `desc`, `atts` as `name => array( default, description )`, `examples`).

#### `makaazi_elementor_widgets`
`( array $widgets )`: Elementor widget classes (`class => title`). Remove a key to hide that widget, or add your own class (see [Shortcodes and Elementor](shortcodes-elementor.md)).

## Actions

#### `makaazi_enquiry_created`
`( int $enquiry_id, int $property_id, array $data )`: after an enquiry is saved and emailed. `$data` has `name`, `phone`, `email`, `msg`, `aid` (agent ID). `$property_id` is `0` for general enquiries.

```php
// Send leads to a CRM.
add_action( 'makaazi_enquiry_created', function ( $eid, $pid, $d ) {
	wp_remote_post( 'https://crm.example.com/hooks/lead', array(
		'timeout' => 5,
		'body'    => array(
			'name'     => $d['name'],
			'phone'    => $d['phone'],
			'email'    => $d['email'],
			'message'  => $d['msg'],
			'property' => $pid ? get_permalink( $pid ) : '',
		),
	) );
}, 10, 3 );
```

#### `makaazi_property_submitted`
`( int $property_id, string $status, string $intent, int $user_id )`: after an agent or owner saves a listing from the dashboard. `$status` is the resulting post status (`draft`, `pending`, `publish`); `$intent` is `draft` or `submit`.

#### `makaazi_listing_approved`
`( int $property_id )`: a moderator approved a submission (Properties › Pending › Approve).

#### `makaazi_listing_rejected`
`( int $property_id, string $reason )`: a moderator sent a submission back. The reason is also stored in `_mk_rejected` and emailed to the author.

## WordPress hooks worth knowing

- `save_post_property`: Makaazi's own handler (`Makaazi_Meta::after_save`) runs at priority 20. Hook later if you need the reference number.
- `shortcode_atts_{tag}`: core filter for shortcodes that pass their tag to `shortcode_atts()`: `makaazi_properties`, `makaazi_locations`, `makaazi_related_properties`, `makaazi_agents`, `makaazi_agent`, `makaazi_favourites`, `makaazi_compare`, `makaazi_account_link`. Example: `shortcode_atts_makaazi_properties` to change defaults site-wide.
- `pre_render_block`: Makaazi renders its own Shortcode blocks here (see [Architecture](architecture.md#two-block-theme-pitfalls-already-handled)).
