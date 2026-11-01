<?php
/**
 * Favourites and compare: shortcodes, REST endpoints and account sync.
 *
 * Lists live in the visitor's browser (localStorage). Signed-in users also keep
 * them in user meta so they follow them between devices.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortlist feature.
 */
class Makaazi_Shortlist {

	const MAX_FAVS    = 200;
	const MAX_COMPARE = 4;

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'shortcodes' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_filter( 'makaazi_shortcode_docs', array( __CLASS__, 'docs' ) );
	}

	/**
	 * Register shortcodes.
	 */
	public static function shortcodes() {
		add_shortcode( 'makaazi_favourites', array( __CLASS__, 'favourites' ) );
		add_shortcode( 'makaazi_compare', array( __CLASS__, 'compare' ) );
		add_shortcode( 'makaazi_favourites_link', array( __CLASS__, 'link' ) );
	}

	/**
	 * Clean a list of property IDs: published properties only, unique, capped.
	 *
	 * @param mixed $ids IDs (array or comma string).
	 * @param int   $max Maximum.
	 * @return int[]
	 */
	public static function clean( $ids, $max ) {
		if ( is_string( $ids ) ) {
			$ids = explode( ',', $ids );
		}
		$out = array();
		foreach ( (array) $ids as $id ) {
			$id = absint( $id );
			if ( $id && ! in_array( $id, $out, true ) && 'property' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
				$out[] = $id;
			}
			if ( count( $out ) >= $max ) {
				break;
			}
		}
		return $out;
	}

	/* ---------------------------------------------------------------------
	 * REST
	 * ------------------------------------------------------------------- */

	/**
	 * Routes.
	 */
	public static function routes() {
		$ns = Makaazi_Rest::NS;
		register_rest_route(
			$ns,
			'/saved/cards',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_cards' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/saved/compare',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_compare' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/saved/mini',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_mini' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/me/saved',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_me_get' ),
					'permission_callback' => 'is_user_logged_in',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_me_set' ),
					'permission_callback' => 'is_user_logged_in',
				),
			)
		);
	}

	/**
	 * Cards for a list of IDs.
	 *
	 * @param WP_REST_Request $r Request.
	 * @return WP_REST_Response
	 */
	public static function rest_cards( $r ) {
		$ids  = self::clean( (string) $r->get_param( 'ids' ), self::MAX_FAVS );
		$cols = max( 1, min( 4, (int) $r->get_param( 'columns' ) ) );
		return rest_ensure_response(
			array(
				'ids'  => $ids,
				'html' => $ids ? Makaazi_Render::cards(
					$ids,
					array(
						'columns' => $cols ? $cols : 3,
						'eager'   => true,
					)
				) : '',
			)
		);
	}

	/**
	 * Compare table for a list of IDs.
	 *
	 * @param WP_REST_Request $r Request.
	 * @return WP_REST_Response
	 */
	public static function rest_compare( $r ) {
		$ids = self::clean( (string) $r->get_param( 'ids' ), self::MAX_COMPARE );
		return rest_ensure_response(
			array(
				'ids'  => $ids,
				'html' => count( $ids ) ? self::table( $ids ) : '',
			)
		);
	}

	/**
	 * Title, thumbnail and URL for the compare bar.
	 *
	 * @param WP_REST_Request $r Request.
	 * @return WP_REST_Response
	 */
	public static function rest_mini( $r ) {
		$out = array();
		foreach ( self::clean( (string) $r->get_param( 'ids' ), self::MAX_FAVS ) as $id ) {
			$g     = makaazi_gallery_ids( $id );
			$out[] = array(
				'id'    => $id,
				'title' => get_the_title( $id ),
				'url'   => get_permalink( $id ),
				'thumb' => $g ? wp_get_attachment_image_url( $g[0], 'thumbnail' ) : makaazi_placeholder(),
			);
		}
		return rest_ensure_response( $out );
	}

	/**
	 * The signed-in user's saved lists.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_me_get() {
		$uid = get_current_user_id();
		return rest_ensure_response(
			array(
				'favs'    => self::clean( get_user_meta( $uid, '_mk_favs', true ), self::MAX_FAVS ),
				'compare' => self::clean( get_user_meta( $uid, '_mk_compare', true ), self::MAX_COMPARE ),
			)
		);
	}

	/**
	 * Replace the signed-in user's saved lists.
	 *
	 * @param WP_REST_Request $r Request.
	 * @return WP_REST_Response
	 */
	public static function rest_me_set( $r ) {
		$uid = get_current_user_id();
		$p   = $r->get_json_params();
		$p   = is_array( $p ) ? $p : array();
		if ( isset( $p['favs'] ) ) {
			update_user_meta( $uid, '_mk_favs', self::clean( $p['favs'], self::MAX_FAVS ) );
		}
		if ( isset( $p['compare'] ) ) {
			update_user_meta( $uid, '_mk_compare', self::clean( $p['compare'], self::MAX_COMPARE ) );
		}
		return self::rest_me_get();
	}

	/* ---------------------------------------------------------------------
	 * Compare table
	 * ------------------------------------------------------------------- */

	/**
	 * Compare table HTML.
	 *
	 * @param int[] $ids Property IDs (2–4).
	 * @return string
	 */
	public static function table( $ids ) {
		$cell  = function ( $id, $key ) {
			$type = makaazi_term( $id, 'property_type' );
			switch ( $key ) {
				case 'price':
					return wp_strip_all_tags( makaazi_price( $id, false ) );
				case 'status':
					return makaazi_status_label( makaazi_status( $id ) );
				case 'location':
					return makaazi_location_text( $id );
				case 'type':
					return $type ? $type->name : '';
				case 'use':
					return implode( ', ', makaazi_term_names( $id, 'property_use' ) );
				case 'category':
					return implode( ', ', makaazi_term_names( $id, 'property_category' ) );
				case 'bedrooms':
				case 'bathrooms':
				case 'parking':
					$v = (float) makaazi_meta( $id, $key, 0 );
					return $v ? (string) ( 0 + $v ) : '';
				case 'area':
					$a = (float) makaazi_meta( $id, 'area_sqm', 0 );
					return $a ? array( 'html' => makaazi_area_html( $a ), 'raw' => (string) round( $a ) ) : '';
				case 'land':
					return makaazi_land_text( $id );
				case 'year':
					$y = (int) makaazi_meta( $id, 'year_built', 0 );
					return $y ? (string) $y : '';
				case 'ref':
					return makaazi_meta( $id, 'ref' );
			}
			return '';
		};
		$rows = array(
			'price'     => __( 'Price', 'makaazi' ),
			'status'    => __( 'Status', 'makaazi' ),
			'location'  => __( 'Location', 'makaazi' ),
			'type'      => __( 'Type', 'makaazi' ),
			'use'       => __( 'Use', 'makaazi' ),
			'category'  => __( 'Category', 'makaazi' ),
			'bedrooms'  => __( 'Bedrooms', 'makaazi' ),
			'bathrooms' => __( 'Bathrooms', 'makaazi' ),
			'parking'   => __( 'Parking', 'makaazi' ),
			'area'      => __( 'Area', 'makaazi' ),
			'land'      => __( 'Land size', 'makaazi' ),
			'year'      => __( 'Year built', 'makaazi' ),
			'ref'       => __( 'Reference', 'makaazi' ),
		);

		$h  = '<div class="mk-ctable-wrap"><table class="mk-ctable" style="--mk-n:' . count( $ids ) . '"><caption class="screen-reader-text">' . esc_html__( 'Property comparison', 'makaazi' ) . '</caption>';
		$h .= '<thead><tr><td class="mk-ctable__corner"></td>';
		foreach ( $ids as $id ) {
			$g  = makaazi_gallery_ids( $id );
			$h .= '<th scope="col" class="mk-ctable__head"><a class="mk-ctable__img" href="' . esc_url( get_permalink( $id ) ) . '" tabindex="-1">' . ( $g ? wp_get_attachment_image( $g[0], 'makaazi-card', false, array( 'alt' => '' ) ) : '' ) . '</a>'
				. '<a class="mk-ctable__title" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>'
				. '<span class="mk-ctable__price">' . makaazi_price( $id ) . '</span>'
				. '<span class="mk-ctable__acts"><a class="mk-btn mk-btn--outline mk-btn--sm" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'View', 'makaazi' ) . '</a>'
				/* translators: %s: property title */
				. '<button type="button" class="mk-btn mk-btn--ghost mk-btn--sm" data-mk-cmp-remove="' . (int) $id . '" aria-label="' . esc_attr( sprintf( __( 'Remove %s from compare', 'makaazi' ), get_the_title( $id ) ) ) . '">' . esc_html__( 'Remove', 'makaazi' ) . '</button></span></th>';
		}
		$h .= '</tr></thead><tbody>';

		$row = function ( $label, $values, $group = '' ) {
			$raws = array();
			foreach ( $values as $v ) {
				$raws[] = is_array( $v ) ? $v['raw'] : (string) $v;
			}
			$diff = count( array_unique( $raws ) ) > 1;
			$out  = '<tr class="' . ( $diff ? 'is-diff' : 'is-same' ) . ( $group ? ' ' . $group : '' ) . '"><th scope="row">' . esc_html( $label ) . ( $diff ? '<span class="screen-reader-text"> ' . esc_html__( '(differs)', 'makaazi' ) . '</span>' : '' ) . '</th>';
			foreach ( $values as $v ) {
				if ( is_array( $v ) ) {
					$out .= '<td>' . $v['html'] . '</td>';
				} elseif ( '✓' === $v ) {
					$out .= '<td class="mk-yes">' . makaazi_icon( 'check', 16 ) . '<span class="screen-reader-text">' . esc_html__( 'Yes', 'makaazi' ) . '</span></td>';
				} elseif ( '' === $v || '–' === $v ) {
					$out .= '<td class="mk-no"><span aria-hidden="true">–</span><span class="screen-reader-text">' . esc_html__( 'No', 'makaazi' ) . '</span></td>';
				} else {
					$out .= '<td>' . esc_html( $v ) . '</td>';
				}
			}
			return $out . '</tr>';
		};

		foreach ( $rows as $key => $label ) {
			$vals = array();
			foreach ( $ids as $id ) {
				$vals[] = $cell( $id, $key );
			}
			if ( '' === implode( '', array_map( function ( $v ) { return is_array( $v ) ? $v['raw'] : $v; }, $vals ) ) ) { // phpcs:ignore
				continue;
			}
			$h .= $row( $label, $vals );
		}

		// Features: union of all features.
		$feats = array();
		$have  = array();
		foreach ( $ids as $id ) {
			$terms       = get_the_terms( $id, 'property_feature' );
			$have[ $id ] = array();
			foreach ( ( $terms && ! is_wp_error( $terms ) ) ? $terms : array() as $t ) {
				$feats[ $t->slug ]       = $t->name;
				$have[ $id ][ $t->slug ] = true;
			}
		}
		if ( $feats ) {
			asort( $feats );
			$h .= '<tr class="mk-ctable__group"><th scope="rowgroup" colspan="' . ( count( $ids ) + 1 ) . '">' . esc_html__( 'Features', 'makaazi' ) . '</th></tr>';
			foreach ( $feats as $slug => $name ) {
				$vals = array();
				foreach ( $ids as $id ) {
					$vals[] = isset( $have[ $id ][ $slug ] ) ? '✓' : '–';
				}
				$h .= $row( $name, $vals );
			}
		}

		// Custom attributes by label.
		$attrs = array();
		foreach ( $ids as $id ) {
			foreach ( (array) makaazi_meta( $id, 'attributes', array() ) as $a ) {
				if ( is_array( $a ) && ! empty( $a[0] ) ) {
					$attrs[ $a[0] ][ $id ] = isset( $a[1] ) ? $a[1] : '';
				}
			}
		}
		if ( $attrs ) {
			$h .= '<tr class="mk-ctable__group"><th scope="rowgroup" colspan="' . ( count( $ids ) + 1 ) . '">' . esc_html__( 'More details', 'makaazi' ) . '</th></tr>';
			foreach ( $attrs as $label => $by ) {
				$vals = array();
				foreach ( $ids as $id ) {
					$vals[] = isset( $by[ $id ] ) ? $by[ $id ] : '';
				}
				$h .= $row( $label, $vals );
			}
		}
		return $h . '</tbody></table></div>';
	}

	/* ---------------------------------------------------------------------
	 * Shortcodes
	 * ------------------------------------------------------------------- */

	/**
	 * Shared list from ?ids= in the URL.
	 *
	 * @param int $max Maximum.
	 * @return int[]
	 */
	private static function shared_ids( $max ) {
		return isset( $_GET['ids'] ) ? self::clean( sanitize_text_field( wp_unslash( $_GET['ids'] ) ), $max ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * [makaazi_favourites].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function favourites( $atts ) {
		$a      = shortcode_atts(
			array(
				'columns' => 3,
				'title'   => __( 'Saved properties', 'makaazi' ),
				'eyebrow' => __( 'Your shortlist', 'makaazi' ),
			),
			$atts,
			'makaazi_favourites'
		);
		$shared = self::shared_ids( self::MAX_FAVS );
		Makaazi_Assets::enqueue();
		$h  = '<div class="mk-block mk-shortlist" data-mk-favs-page data-columns="' . max( 1, min( 4, (int) $a['columns'] ) ) . '"' . ( $shared ? ' data-shared="' . esc_attr( implode( ',', $shared ) ) . '"' : '' ) . '>';
		$h .= '<div class="mk-sechead"><div><p class="mk-eyebrow">' . esc_html( $shared ? __( 'Shared with you', 'makaazi' ) : $a['eyebrow'] ) . '</p><h2 class="mk-sechead__title">' . esc_html( $shared ? __( 'Shared shortlist', 'makaazi' ) : $a['title'] ) . ' <span class="mk-shortlist__n" data-mk-count></span></h2></div>';
		$h .= '<div class="mk-shortlist__acts">';
		if ( $shared ) {
			$h .= '<button type="button" class="mk-btn mk-btn--sm" data-mk-save-all>' . makaazi_icon( 'heart', 15 ) . ' ' . esc_html__( 'Save all to my favourites', 'makaazi' ) . '</button>';
		} else {
			$h .= '<button type="button" class="mk-btn mk-btn--ghost mk-btn--sm" data-mk-share-list="favs" hidden>' . makaazi_icon( 'share', 15 ) . ' ' . esc_html__( 'Share list', 'makaazi' ) . '</button>'
				. '<button type="button" class="mk-link" data-mk-clear="favs" hidden>' . esc_html__( 'Remove all', 'makaazi' ) . '</button>';
		}
		$h .= '</div></div>';
		$h .= '<div class="mk-shortlist__body" data-mk-body aria-live="polite">';
		if ( $shared ) {
			$h .= Makaazi_Render::cards( $shared, array( 'columns' => (int) $a['columns'] ) );
		} else {
			$h .= '<p class="mk-shortlist__loading">' . esc_html__( 'Loading your saved properties…', 'makaazi' ) . '</p>';
		}
		$h .= '</div>';
		$h .= '<div class="mk-empty" data-mk-empty hidden>' . makaazi_icon( 'heart', 28 ) . '<h3>' . esc_html__( 'No saved properties yet', 'makaazi' ) . '</h3><p>' . esc_html__( 'Tap the heart on any property to keep it here. Your list stays on this device, and on your account when you are signed in.', 'makaazi' ) . '</p><a class="mk-btn mk-btn--sm" href="' . esc_url( makaazi_archive_url() ) . '">' . esc_html__( 'Browse properties', 'makaazi' ) . '</a></div>';
		return $h . '</div>';
	}

	/**
	 * [makaazi_compare].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function compare( $atts ) {
		$a      = shortcode_atts(
			array(
				'title'   => __( 'Compare properties', 'makaazi' ),
				'eyebrow' => __( 'Side by side', 'makaazi' ),
			),
			$atts,
			'makaazi_compare'
		);
		$shared = self::shared_ids( self::MAX_COMPARE );
		Makaazi_Assets::enqueue();
		$h  = '<div class="mk-block mk-compare" data-mk-compare-page' . ( $shared ? ' data-shared="' . esc_attr( implode( ',', $shared ) ) . '"' : '' ) . '>';
		$h .= '<div class="mk-sechead"><div><p class="mk-eyebrow">' . esc_html( $shared ? __( 'Shared with you', 'makaazi' ) : $a['eyebrow'] ) . '</p><h2 class="mk-sechead__title">' . esc_html( $a['title'] ) . ' <span class="mk-shortlist__n" data-mk-count></span></h2></div>';
		$h .= '<div class="mk-shortlist__acts"><label class="mk-switch"><input type="checkbox" data-mk-diff> <span>' . esc_html__( 'Show only differences', 'makaazi' ) . '</span></label>';
		if ( $shared ) {
			$h .= '<button type="button" class="mk-btn mk-btn--sm" data-mk-use-compare>' . esc_html__( 'Use this comparison', 'makaazi' ) . '</button>';
		} else {
			$h .= '<button type="button" class="mk-btn mk-btn--ghost mk-btn--sm" data-mk-share-list="compare" hidden>' . makaazi_icon( 'share', 15 ) . ' ' . esc_html__( 'Share', 'makaazi' ) . '</button>'
				. '<button type="button" class="mk-link" data-mk-clear="compare" hidden>' . esc_html__( 'Clear all', 'makaazi' ) . '</button>';
		}
		$h .= '</div></div><div class="mk-compare__body" data-mk-body aria-live="polite">';
		$h .= $shared && count( $shared ) > 1 ? self::table( $shared ) : '<p class="mk-shortlist__loading">' . esc_html__( 'Loading…', 'makaazi' ) . '</p>';
		$h .= '</div><div class="mk-empty" data-mk-empty hidden>' . makaazi_icon( 'compare', 28 ) . '<h3 data-mk-empty-title data-one="' . esc_attr__( 'Add one more property to compare', 'makaazi' ) . '" data-none="' . esc_attr__( 'Nothing to compare yet', 'makaazi' ) . '">' . esc_html__( 'Nothing to compare yet', 'makaazi' ) . '</h3><p>' . esc_html__( 'Use the compare button on any property card. You can compare up to four.', 'makaazi' ) . '</p><a class="mk-btn mk-btn--sm" href="' . esc_url( makaazi_archive_url() ) . '">' . esc_html__( 'Browse properties', 'makaazi' ) . '</a></div>';
		return $h . '</div>';
	}

	/**
	 * [makaazi_favourites_link] header heart with count.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function link( $atts ) {
		if ( ! makaazi_settings( 'card_favourite' ) ) {
			return '';
		}
		$a   = shortcode_atts( array( 'label' => '' ), $atts, 'makaazi_favourites_link' );
		$url = makaazi_shortlist_url( 'favourites' );
		Makaazi_Assets::enqueue();
		return '<a class="mk-favlink" href="' . esc_url( $url ? $url : makaazi_archive_url() ) . '" aria-label="' . esc_attr__( 'Saved properties', 'makaazi' ) . '">' . makaazi_icon( 'heart', 18 ) . ( $a['label'] ? '<span class="mk-favlink__label">' . esc_html( $a['label'] ) . '</span>' : '' ) . '<span class="mk-favlink__n" data-mk-fav-count hidden>0</span></a>';
	}

	/**
	 * Shortcode reference entries.
	 *
	 * @param array $docs Docs.
	 * @return array
	 */
	public static function docs( $docs ) {
		$docs[] = array(
			'tag'      => 'makaazi_favourites',
			'group'    => __( 'Favourites & compare', 'makaazi' ),
			'desc'     => __( 'The visitor\'s saved properties, with Share list and Remove all. Opening a shared link (?ids=…) shows that shortlist with a "Save all" button.', 'makaazi' ),
			'atts'     => array(
				'columns' => array( '3', '1–4' ),
				'title'   => array( 'Saved properties', '' ),
				'eyebrow' => array( 'Your shortlist', '' ),
			),
			'examples' => array( '[makaazi_favourites]' ),
		);
		$docs[] = array(
			'tag'      => 'makaazi_compare',
			'group'    => __( 'Favourites & compare', 'makaazi' ),
			'desc'     => __( 'Side-by-side table of up to 4 properties. Rows that differ are highlighted, with a "Show only differences" switch.', 'makaazi' ),
			'atts'     => array(
				'title'   => array( 'Compare properties', '' ),
				'eyebrow' => array( 'Side by side', '' ),
			),
			'examples' => array( '[makaazi_compare]' ),
		);
		$docs[] = array(
			'tag'      => 'makaazi_favourites_link',
			'group'    => __( 'Favourites & compare', 'makaazi' ),
			'desc'     => __( 'Heart icon with a live count, linking to the Favourites page. The Makaazi theme puts it in the header.', 'makaazi' ),
			'atts'     => array( 'label' => array( '', __( 'Optional text next to the heart', 'makaazi' ) ) ),
			'examples' => array( '[makaazi_favourites_link]', '[makaazi_favourites_link label="Saved"]' ),
		);
		return $docs;
	}
}
