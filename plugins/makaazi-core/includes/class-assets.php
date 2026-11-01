<?php
/**
 * Front-end assets, loaded only on pages that use them.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asset loader.
 */
class Makaazi_Assets {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'auto' ), 20 );
	}

	/**
	 * Register styles and scripts.
	 */
	public static function register() {
		$v = MAKAAZI_VERSION . '.' . (int) filemtime( MAKAAZI_DIR . 'assets/js/makaazi.js' );
		wp_register_style( 'leaflet', MAKAAZI_URL . 'assets/vendor/leaflet/leaflet.css', array(), '1.9.4' );
		wp_register_script( 'leaflet', MAKAAZI_URL . 'assets/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
		wp_register_style( 'makaazi', MAKAAZI_URL . 'assets/css/makaazi.css', array(), $v );
		wp_register_script( 'makaazi', MAKAAZI_URL . 'assets/js/makaazi.js', array(), $v, true );
		wp_localize_script(
			'makaazi',
			'MAKAAZI',
			array(
				'rest'   => esc_url_raw( rest_url( Makaazi_Rest::NS ) ),
				'unit'   => makaazi_unit(),
				'tiles'  => makaazi_settings( 'map_tiles' ),
				'attrib' => '&copy; OpenStreetMap contributors',
				'i18n'   => array(
					'close'    => __( 'Close', 'makaazi' ),
					'prev'     => __( 'Previous photo', 'makaazi' ),
					'next'     => __( 'Next photo', 'makaazi' ),
					'zoom'     => __( 'Zoom', 'makaazi' ),
					'viewer'   => __( 'Photo viewer', 'makaazi' ),
					'copied'   => __( 'Link copied.', 'makaazi' ),
					'sending'  => __( 'Sending…', 'makaazi' ),
					'error'    => __( 'Something went wrong. Please try again or call us.', 'makaazi' ),
					'loading'  => __( 'Loading…', 'makaazi' ),
					'loadmore' => __( 'Load more', 'makaazi' ),
					'browse'   => __( '← → to browse, Esc to close', 'makaazi' ),
				),
			)
		);
	}

	/**
	 * Enqueue on property pages even if the theme does not use the shortcodes.
	 */
	public static function auto() {
		if ( is_singular( 'property' ) || is_post_type_archive( 'property' ) || is_tax( get_object_taxonomies( 'property' ) ) ) {
			self::enqueue( true );
		}
	}

	/**
	 * Enqueue front-end assets (safe to call many times).
	 *
	 * @param bool $map Also load Leaflet.
	 */
	public static function enqueue( $map = false ) {
		if ( ! wp_style_is( 'makaazi', 'registered' ) ) {
			self::register();
		}
		wp_enqueue_style( 'makaazi' );
		wp_enqueue_script( 'makaazi' );
		if ( $map ) {
			wp_enqueue_style( 'leaflet' );
			wp_enqueue_script( 'leaflet' );
		}
	}
}
