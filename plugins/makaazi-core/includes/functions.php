<?php
/**
 * Helper functions used across the plugin and available to themes.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default settings.
 *
 * @return array
 */
function makaazi_default_settings() {
	// Translate only once WordPress has loaded translations (settings can be read earlier, e.g. in capability checks).
	$t = function ( $text ) {
		return did_action( 'init' ) ? __( $text, 'makaazi' ) : $text; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
	};
	$defaults = array(
		'currency'           => 'KES',
		'short_price'        => 0,
		'unit'               => 'sqm',
		'unit_toggle'        => 1,
		'button_text'        => $t( 'View Property' ),
		'card_images'        => 5,
		'card_show'          => array( 'category', 'use', 'type', 'location', 'beds', 'baths', 'parking', 'area', 'badges' ),
		'new_days'           => 7,
		'image_ratio'        => '4:3',
		'per_page'           => 12,
		'default_sort'       => 'newest',
		'filters'            => array( 'status', 'location', 'type', 'use', 'category', 'price', 'beds', 'baths', 'parking', 'features', 'keyword' ),
		'privacy_radius'     => 500,
		'related_count'      => 8,
		'related_same_status' => 1,
		'contact_name'       => $t( 'Makaazi Homes' ),
		'contact_phone'      => '+254 700 123 456',
		'whatsapp'           => '254700123456',
		'enquiry_email'      => '',
		'brackets_sale'      => '5000000,10000000,20000000,35000000,50000000,75000000,100000000,150000000',
		'brackets_rent'      => '20000,50000,100000,150000,250000,500000',
		'ref_prefix'         => 'MK-',
		'card_favourite'     => 1,
		'card_compare'       => 1,
		'favourites_page'    => 0,
		'compare_page'       => 0,
		'agents_enabled'     => 1,
		'agent_review'       => 0,
		'enquiry_cc_admin'   => 1,
		'owners_enabled'     => 1,
		'owner_registration' => 'open',
		'owner_email_verify' => 1,
		'owner_autopublish'  => 0,
		'owner_edit_review'  => 1,
		'max_photos'         => 20,
		'dashboard_page'     => 0,
		'map_tiles'          => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
		'map_center'         => '-1.2864,36.8172',
	);
	/**
	 * Filter the default plugin settings (add your own keys here).
	 *
	 * @param array $defaults Default settings.
	 */
	return apply_filters( 'makaazi_default_settings', $defaults );
}

/**
 * Get all settings, or one setting.
 *
 * @param string|null $key Setting key.
 * @return mixed
 */
function makaazi_settings( $key = null ) {
	static $cache = null;
	if ( null === $cache || ( ! did_action( 'init' ) ) ) {
		$saved = get_option( 'makaazi_settings', array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), makaazi_default_settings() );
		/**
		 * Filter the merged plugin settings at runtime (does not change what is saved).
		 *
		 * @param array $settings Settings.
		 */
		$cache = apply_filters( 'makaazi_settings', $cache );
	}
	if ( null === $key ) {
		return $cache;
	}
	return isset( $cache[ $key ] ) ? $cache[ $key ] : null;
}

/**
 * Read a property meta value.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key without the _mk_ prefix.
 * @param mixed  $default Default value.
 * @return mixed
 */
function makaazi_meta( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, '_mk_' . $key, true );
	return ( '' === $value || null === $value ) ? $default : $value;
}

/**
 * Price brackets for sale or rent.
 *
 * @param string $status sale|rent.
 * @return int[]
 */
function makaazi_brackets( $status ) {
	$raw = makaazi_settings( 'rent' === $status ? 'brackets_rent' : 'brackets_sale' );
	$out = array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) );
	sort( $out );
	return array_values( $out );
}

/**
 * Short money format: KES 25M, KES 180K.
 *
 * @param float $n Amount.
 * @return string
 */
function makaazi_short_money( $n ) {
	$cur = makaazi_settings( 'currency' );
	if ( $n >= 1000000 ) {
		$v = round( $n / 1000000, 1 );
		return $cur . ' ' . ( floor( $v ) == $v ? number_format( $v ) : $v ) . 'M';
	}
	if ( $n >= 1000 ) {
		return $cur . ' ' . number_format( round( $n / 1000 ) ) . 'K';
	}
	return $cur . ' ' . number_format( $n );
}

/**
 * Money format following the short-price setting.
 *
 * @param float $n Amount.
 * @return string
 */
function makaazi_money( $n ) {
	if ( makaazi_settings( 'short_price' ) ) {
		return makaazi_short_money( $n );
	}
	return makaazi_settings( 'currency' ) . ' ' . number_format( (float) $n );
}

/**
 * Price period labels.
 *
 * @return array
 */
function makaazi_periods() {
	return array(
		'total' => '',
		'month' => __( '/month', 'makaazi' ),
		'year'  => __( '/year', 'makaazi' ),
		'night' => __( '/night', 'makaazi' ),
	);
}

/**
 * Price HTML for a property.
 *
 * @param int  $post_id Post ID.
 * @param bool $html    Wrap the period in <small>.
 * @return string
 */
function makaazi_price( $post_id, $html = true ) {
	if ( makaazi_meta( $post_id, 'price_on_request' ) ) {
		return esc_html__( 'Price on request', 'makaazi' );
	}
	$price = (float) makaazi_meta( $post_id, 'price', 0 );
	if ( ! $price ) {
		return esc_html__( 'Price on request', 'makaazi' );
	}
	$periods = makaazi_periods();
	$period  = makaazi_meta( $post_id, 'price_period', 'total' );
	$suffix  = isset( $periods[ $period ] ) ? $periods[ $period ] : '';
	$out     = esc_html( makaazi_money( $price ) );
	if ( $suffix ) {
		$out .= $html ? ' <small>' . esc_html( $suffix ) . '</small>' : ' ' . esc_html( $suffix );
	}
	return $out;
}

/**
 * The visitor's area unit (cookie), falling back to the site default.
 *
 * @return string sqm|sqft
 */
function makaazi_unit() {
	if ( makaazi_settings( 'unit_toggle' ) && isset( $_COOKIE['mk_unit'] ) && in_array( $_COOKIE['mk_unit'], array( 'sqm', 'sqft' ), true ) ) {
		return sanitize_key( $_COOKIE['mk_unit'] );
	}
	return 'sqft' === makaazi_settings( 'unit' ) ? 'sqft' : 'sqm';
}

/**
 * Area text for a value stored in square metres.
 *
 * @param float $sqm Area in m².
 * @return string
 */
function makaazi_area_text( $sqm ) {
	if ( 'sqft' === makaazi_unit() ) {
		return number_format( round( $sqm * 10.7639 ) ) . ' ft²';
	}
	return number_format( round( $sqm ) ) . ' m²';
}

/**
 * Area HTML that the unit toggle can update without a reload.
 *
 * @param float $sqm Area in m².
 * @return string
 */
function makaazi_area_html( $sqm ) {
	return '<span class="mk-area" data-sqm="' . esc_attr( (float) $sqm ) . '">' . esc_html( makaazi_area_text( $sqm ) ) . '</span>';
}

/**
 * Land size text, e.g. "0.5 acres".
 *
 * @param int $post_id Post ID.
 * @return string
 */
function makaazi_land_text( $post_id ) {
	$size = makaazi_meta( $post_id, 'land_size' );
	if ( '' === $size ) {
		return '';
	}
	$units = array(
		'acres'    => __( 'acres', 'makaazi' ),
		'hectares' => __( 'hectares', 'makaazi' ),
		'sqm'      => 'm²',
	);
	$unit  = makaazi_meta( $post_id, 'land_unit', 'acres' );
	return ( floor( (float) $size ) == $size ? number_format( (float) $size ) : $size ) . ' ' . ( isset( $units[ $unit ] ) ? $units[ $unit ] : $unit );
}

/**
 * Gallery attachment IDs, falling back to the featured image.
 *
 * @param int $post_id Post ID.
 * @return int[]
 */
function makaazi_gallery_ids( $post_id ) {
	$ids = makaazi_meta( $post_id, 'gallery', array() );
	if ( is_string( $ids ) ) {
		$ids = explode( ',', $ids );
	}
	$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
	if ( ! $ids && has_post_thumbnail( $post_id ) ) {
		$ids = array( get_post_thumbnail_id( $post_id ) );
	}
	return $ids;
}

/**
 * Placeholder image URL.
 *
 * @return string
 */
function makaazi_placeholder() {
	return MAKAAZI_URL . 'assets/img/placeholder.svg';
}

/**
 * First term of a taxonomy for a post.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy.
 * @return WP_Term|null
 */
function makaazi_term( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	return $terms[0];
}

/**
 * Term names for a post.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy.
 * @return string[]
 */
function makaazi_term_names( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}
	return wp_list_pluck( $terms, 'name' );
}

/**
 * Listing status slug: sale or rent.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function makaazi_status( $post_id ) {
	$t = makaazi_term( $post_id, 'property_status' );
	return $t ? $t->slug : 'sale';
}

/**
 * Status label.
 *
 * @param string $slug sale|rent.
 * @return string
 */
function makaazi_status_label( $slug ) {
	$t = get_term_by( 'slug', $slug, 'property_status' );
	if ( $t ) {
		return $t->name;
	}
	return 'rent' === $slug ? __( 'For Rent', 'makaazi' ) : __( 'For Sale', 'makaazi' );
}

/**
 * Deepest location term for a post.
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null
 */
function makaazi_location_term( $post_id ) {
	$terms = get_the_terms( $post_id, 'property_location' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	$deepest = $terms[0];
	$depth   = -1;
	foreach ( $terms as $t ) {
		$d = count( get_ancestors( $t->term_id, 'property_location', 'taxonomy' ) );
		if ( $d > $depth ) {
			$depth   = $d;
			$deepest = $t;
		}
	}
	return $deepest;
}

/**
 * "Westlands, Nairobi" style location text.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function makaazi_location_text( $post_id ) {
	$t = makaazi_location_term( $post_id );
	if ( ! $t ) {
		return '';
	}
	$names = array( $t->name );
	if ( $t->parent ) {
		$p = get_term( $t->parent, 'property_location' );
		if ( $p && ! is_wp_error( $p ) ) {
			$names[] = $p->name;
		}
	}
	return implode( ', ', $names );
}

/**
 * Whether the property is sold or let.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function makaazi_is_closed( $post_id ) {
	return in_array( makaazi_meta( $post_id, 'availability', 'available' ), array( 'sold', 'let' ), true );
}

/**
 * Property use slugs (residential/commercial).
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function makaazi_uses( $post_id ) {
	$terms = get_the_terms( $post_id, 'property_use' );
	return ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'slug' ) : array();
}

/**
 * Approximate public centre for the map: a deterministic offset so the exact
 * point is never revealed and the circle does not move between visits.
 *
 * @param int $post_id Post ID.
 * @return array|null [lat, lng, radius_m, exact]
 */
function makaazi_public_location( $post_id ) {
	$lat = makaazi_meta( $post_id, 'lat' );
	$lng = makaazi_meta( $post_id, 'lng' );
	if ( '' === $lat || '' === $lng ) {
		return null;
	}
	$lat    = (float) $lat;
	$lng    = (float) $lng;
	$radius = (int) makaazi_meta( $post_id, 'map_radius', makaazi_settings( 'privacy_radius' ) );
	if ( makaazi_meta( $post_id, 'exact_pin' ) ) {
		return array( round( $lat, 6 ), round( $lng, 6 ), 0, true );
	}
	$h      = crc32( 'makaazi-offset-' . $post_id . wp_salt( 'nonce' ) );
	$angle  = ( $h % 360 ) * M_PI / 180;
	$dist   = $radius * ( 0.25 + ( ( $h >> 9 ) % 50 ) / 100 ); // 25%–75% of the radius.
	$dlat   = ( $dist * cos( $angle ) ) / 111320;
	$dlng   = ( $dist * sin( $angle ) ) / ( 111320 * max( 0.2, cos( deg2rad( $lat ) ) ) );
	return array( round( $lat + $dlat, 4 ), round( $lng + $dlng, 4 ), $radius, false );
}

/**
 * Locate a template, allowing themes to override in /makaazi/.
 *
 * @param string $name File name, e.g. card.php.
 * @return string
 */
function makaazi_locate_template( $name ) {
	$theme = locate_template( array( 'makaazi/' . $name ) );
	return $theme ? $theme : MAKAAZI_DIR . 'templates/' . $name;
}

/**
 * Render a template to a string.
 *
 * @param string $name Template file.
 * @param array  $args Variables.
 * @return string
 */
function makaazi_template( $name, $args = array() ) {
	/**
	 * Filter the variables passed to a template file.
	 *
	 * @param array  $args Template variables.
	 * @param string $name Template name, e.g. card.php.
	 */
	$args = apply_filters( 'makaazi_template_args', $args, $name );
	$file = makaazi_locate_template( $name );
	if ( ! file_exists( $file ) ) {
		return '';
	}
	ob_start();
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( $args, EXTR_SKIP );
	include $file;
	return ob_get_clean();
}

/**
 * Inline SVG icon.
 *
 * @param string $name Icon name.
 * @param int    $size Size in px.
 * @return string
 */
function makaazi_icon( $name, $size = 16 ) {
	static $icons = array(
		'bed'     => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 14h18M6 10V7.5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1V10M3 18v2M21 18v2"/>',
		'bath'    => '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-3zM6 12V6a2 2 0 0 1 4 0M7 19l-1 2M17 19l1 2"/>',
		'car'     => '<path d="M5 16v-5l2-5h10l2 5v5M5 16h14M5 16v2M19 16v2M3 11h18"/><circle cx="8" cy="13.5" r="1"/><circle cx="16" cy="13.5" r="1"/>',
		'area'    => '<path d="M4 4h16v16H4zM4 9h4M15 20v-4M9 4v3"/>',
		'pin'     => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'share'   => '<circle cx="6" cy="12" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><path d="M8 11l8-4M8 13l8 4"/>',
		'wa'      => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-1.8-1.8l.8-1-1-2z"/>',
		'phone'   => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'search'  => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
		'grid'    => '<rect x="4" y="4" width="7" height="7"/><rect x="13" y="4" width="7" height="7"/><rect x="4" y="13" width="7" height="7"/><rect x="13" y="13" width="7" height="7"/>',
		'list'    => '<path d="M9 6h11M9 12h11M9 18h11M4 6h1M4 12h1M4 18h1"/>',
		'map'     => '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2zM9 4v14M15 6v14"/>',
		'left'    => '<path d="M15 6l-6 6 6 6"/>',
		'right'   => '<path d="M9 6l6 6-6 6"/>',
		'close'   => '<path d="M6 6l12 12M18 6L6 18"/>',
		'zoom'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4M8 11h6M11 8v6"/>',
		'check'   => '<path d="M5 12l5 5 9-10"/>',
		'filter'  => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'photo'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-8 8"/>',
		'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'eye'     => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'heart'   => '<path d="M12 20s-7-4.4-9-9a4.6 4.6 0 0 1 8-4l1 1 1-1a4.6 4.6 0 0 1 8 4c-2 4.6-9 9-9 9z"/>',
		'compare' => '<path d="M7 4v13M7 4L4 7M7 4l3 3M17 20V7M17 20l-3-3M17 20l3-3"/>',
	);
	$path = isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['check'];
	return '<svg class="mk-ic" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/**
 * Bump the cache version (related properties, filter counts).
 */
function makaazi_bump_cache() {
	update_option( 'makaazi_cache_ver', time(), false );
}

/**
 * Current cache version.
 *
 * @return string
 */
function makaazi_cache_ver() {
	return (string) get_option( 'makaazi_cache_ver', '1' );
}

/**
 * Properties archive URL with query args.
 *
 * @param array $args Query args.
 * @return string
 */
function makaazi_archive_url( $args = array() ) {
	$url = get_post_type_archive_link( 'property' );
	if ( ! $url ) {
		$url = home_url( '/properties/' );
	}
	return $args ? add_query_arg( array_map( 'rawurlencode', $args ), $url ) : $url;
}

/**
 * WhatsApp link with a prefilled message.
 *
 * @param string $number  International number, digits only.
 * @param string $message Message.
 * @return string
 */
function makaazi_whatsapp_url( $number, $message ) {
	$number = preg_replace( '/\D+/', '', (string) $number );
	return 'https://wa.me/' . $number . '?text=' . rawurlencode( $message );
}

/**
 * URL of the Favourites or Compare page.
 *
 * @param string $which favourites|compare.
 * @return string
 */
function makaazi_shortlist_url( $which ) {
	$id = (int) makaazi_settings( 'favourites' === $which ? 'favourites_page' : 'compare_page' );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return get_permalink( $id );
	}
	$p = get_page_by_path( 'favourites' === $which ? 'favourites' : 'compare' );
	return $p ? get_permalink( $p ) : '';
}

/**
 * Agents feature switched on?
 *
 * @return bool
 */
function makaazi_agents_on() {
	return (bool) makaazi_settings( 'agents_enabled' );
}

/**
 * Owner submissions switched on?
 *
 * @return bool
 */
function makaazi_owners_on() {
	return (bool) makaazi_settings( 'owners_enabled' );
}

/**
 * My account / dashboard URL.
 *
 * @param array $args Query args.
 * @return string
 */
function makaazi_dashboard_url( $args = array() ) {
	$id  = (int) makaazi_settings( 'dashboard_page' );
	$url = ( $id && 'publish' === get_post_status( $id ) ) ? get_permalink( $id ) : '';
	if ( ! $url ) {
		$p   = get_page_by_path( 'my-account' );
		$url = $p ? get_permalink( $p ) : '';
	}
	return ( $url && $args ) ? add_query_arg( $args, $url ) : $url;
}
