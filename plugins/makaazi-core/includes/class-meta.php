<?php
/**
 * Property meta registration.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers meta so it is typed, sanitised and exposed to REST.
 * Exact coordinates are registered without REST exposure.
 */
class Makaazi_Meta {

	/**
	 * Meta schema: key => [type, rest].
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'price'            => array( 'number', true ),
			'price_period'     => array( 'string', true ),
			'price_on_request' => array( 'boolean', true ),
			'availability'     => array( 'string', true ),
			'area_sqm'         => array( 'number', true ),
			'land_size'        => array( 'number', true ),
			'land_unit'        => array( 'string', true ),
			'bedrooms'         => array( 'integer', true ),
			'bathrooms'        => array( 'number', true ),
			'parking'          => array( 'integer', true ),
			'year_built'       => array( 'integer', true ),
			'featured'         => array( 'boolean', true ),
			'gallery'          => array( 'array', true ),
			'location_label'   => array( 'string', true ),
			'lat'              => array( 'number', false ),
			'lng'              => array( 'number', false ),
			'map_radius'       => array( 'integer', true ),
			'exact_pin'        => array( 'boolean', true ),
			'ref'              => array( 'string', true ),
			'attributes'       => array( 'array', true ),
			'video_url'        => array( 'string', true ),
			'leads'            => array( 'integer', false ),
		);
	}

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 11 );
		add_action( 'save_post_property', array( __CLASS__, 'after_save' ), 20, 2 );
		add_action( 'deleted_post', array( __CLASS__, 'maybe_bump' ) );
		add_action( 'trashed_post', array( __CLASS__, 'maybe_bump' ) );
	}

	/**
	 * Register post meta.
	 */
	public static function register() {
		foreach ( self::fields() as $key => $f ) {
			$args = array(
				'type'          => $f[0],
				'single'        => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'show_in_rest'  => $f[1],
			);
			if ( 'array' === $f[0] && $f[1] ) {
				$args['show_in_rest'] = array(
					'schema' => array(
						'type'  => 'array',
						'items' => 'gallery' === $key ? array( 'type' => 'integer' ) : array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				);
			}
			register_post_meta( 'property', '_mk_' . $key, $args );
		}
	}

	/**
	 * Ensure a reference number and default flags exist, and clear caches.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function after_save( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}
		if ( '' === (string) get_post_meta( $post_id, '_mk_ref', true ) ) {
			update_post_meta( $post_id, '_mk_ref', self::next_ref() );
		}
		if ( '' === (string) get_post_meta( $post_id, '_mk_featured', true ) ) {
			update_post_meta( $post_id, '_mk_featured', 0 );
		}
		foreach ( array( '_mk_price', '_mk_area_sqm' ) as $k ) {
			if ( '' === (string) get_post_meta( $post_id, $k, true ) ) {
				update_post_meta( $post_id, $k, 0 );
			}
		}
		makaazi_bump_cache();
	}

	/**
	 * Next sequential reference number.
	 *
	 * @return string
	 */
	public static function next_ref() {
		$n = (int) get_option( 'makaazi_ref_counter', 100 ) + 1;
		update_option( 'makaazi_ref_counter', $n, false );
		return makaazi_settings( 'ref_prefix' ) . str_pad( (string) $n, 5, '0', STR_PAD_LEFT );
	}

	/**
	 * Clear caches when a property is removed.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function maybe_bump( $post_id ) {
		if ( 'property' === get_post_type( $post_id ) ) {
			makaazi_bump_cache();
		}
	}
}
