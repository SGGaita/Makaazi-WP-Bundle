<?php
/**
 * Elementor integration: a "Makaazi" widget category with one widget per section.
 *
 * Loads only when Elementor is active. Every widget renders the matching shortcode, so the
 * output, settings and template overrides are the same as everywhere else.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Makaazi widgets with Elementor.
 */
class Makaazi_Elementor {

	/**
	 * Hook in. Safe to call when Elementor is not installed: the hooks simply never fire.
	 */
	public static function init() {
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'widgets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( __CLASS__, 'register_scripts' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( __CLASS__, 'preview_assets' ) );
		add_action( 'elementor/preview/enqueue_styles', array( __CLASS__, 'preview_assets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( __CLASS__, 'editor_styles' ) );
	}

	/**
	 * Is Elementor loaded?
	 *
	 * @return bool
	 */
	public static function active() {
		return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Widget_Base' );
	}

	/**
	 * The Makaazi category, placed first in the panel.
	 *
	 * @param \Elementor\Elements_Manager $manager Manager.
	 */
	public static function category( $manager ) {
		$manager->add_category(
			'makaazi',
			array(
				'title' => __( 'Makaazi', 'makaazi' ),
				'icon'  => 'eicon-home',
			)
		);
	}

	/**
	 * Widget classes, keyed by file.
	 *
	 * @return array
	 */
	public static function list() {
		$list = array(
			'Makaazi_EW_Properties'  => 'Properties',
			'Makaazi_EW_Listing'     => 'Property Listing',
			'Makaazi_EW_Search'      => 'Property Search',
			'Makaazi_EW_Locations'   => 'Locations',
			'Makaazi_EW_Property'    => 'Property Section',
			'Makaazi_EW_Agents'      => 'Agents',
			'Makaazi_EW_Shortlist'   => 'Favourites & Compare',
			'Makaazi_EW_Account'     => 'My Account',
		);
		if ( shortcode_exists( 'makaazi_slider' ) ) {
			$list = array( 'Makaazi_EW_Slider' => 'Hero Slider' ) + $list;
		}
		/**
		 * Filter the Makaazi Elementor widgets (class => title). Remove one to hide it from the panel.
		 *
		 * @param array $list Widgets.
		 */
		return apply_filters( 'makaazi_elementor_widgets', $list );
	}

	/**
	 * Register widgets.
	 *
	 * @param \Elementor\Widgets_Manager $manager Manager.
	 */
	public static function widgets( $manager ) {
		require_once MAKAAZI_DIR . 'includes/elementor/class-widget.php';
		require_once MAKAAZI_DIR . 'includes/elementor/widgets.php';
		foreach ( array_keys( self::list() ) as $class ) {
			if ( class_exists( $class ) ) {
				$manager->register( new $class() );
			}
		}
	}

	/**
	 * Make sure our handles exist on Elementor pages, plus the small re-init script.
	 */
	public static function register_scripts() {
		if ( ! wp_script_is( 'makaazi', 'registered' ) ) {
			Makaazi_Assets::register();
		}
		if ( class_exists( 'MKS_Render' ) && ! wp_script_is( 'makaazi-slider', 'registered' ) ) {
			MKS_Render::register_assets();
		}
		wp_register_script( 'makaazi-elementor', MAKAAZI_URL . 'assets/js/elementor.js', array( 'jquery', 'elementor-frontend' ), MAKAAZI_VERSION . '.' . (int) filemtime( MAKAAZI_DIR . 'assets/js/elementor.js' ), true );
	}

	/**
	 * In the editor preview every widget can be added at any time, so load everything up front.
	 */
	public static function preview_assets() {
		self::register_scripts();
		Makaazi_Assets::enqueue( true );
		if ( wp_script_is( 'makaazi-slider', 'registered' ) ) {
			wp_enqueue_style( 'makaazi-slider' );
			wp_enqueue_script( 'makaazi-slider' );
		}
		wp_enqueue_script( 'makaazi-elementor' );
	}

	/**
	 * Panel icon colour.
	 */
	public static function editor_styles() {
		wp_add_inline_style( 'elementor-editor', '.elementor-element .icon .mk-ew-icon{color:#B9924A}' );
	}
}
