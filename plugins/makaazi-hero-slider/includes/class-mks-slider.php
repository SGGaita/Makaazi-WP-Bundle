<?php
/**
 * Slider post type and data access.
 *
 * @package MakaaziSlider
 */

defined( 'ABSPATH' ) || exit;

/**
 * mk_slider post type.
 */
class MKS_Slider {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'makaazi_shortcode_docs', array( __CLASS__, 'docs' ) );
	}

	/**
	 * Register.
	 */
	public static function register() {
		register_post_type(
			'mk_slider',
			array(
				'labels'       => array(
					'name'          => __( 'Hero Sliders', 'makaazi-slider' ),
					'singular_name' => __( 'Hero Slider', 'makaazi-slider' ),
					'add_new'       => __( 'Add New Slider', 'makaazi-slider' ),
					'add_new_item'  => __( 'Add New Hero Slider', 'makaazi-slider' ),
					'edit_item'     => __( 'Edit Hero Slider', 'makaazi-slider' ),
					'all_items'     => __( 'Hero Sliders', 'makaazi-slider' ),
					'not_found'     => __( 'No sliders yet.', 'makaazi-slider' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => post_type_exists( 'property' ) ? 'edit.php?post_type=property' : true,
				'menu_icon'    => 'dashicons-images-alt2',
				'supports'     => array( 'title' ),
				'show_in_rest' => false,
			)
		);
	}

	/**
	 * Default slider settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'source'          => 'manual',
			'featured_count'  => 3,
			'effect'          => 'fade',
			'autoplay'        => 1,
			'delay'           => 6,
			'speed'           => 800,
			'pause_hover'     => 1,
			'loop'            => 1,
			'arrows'          => 1,
			'dots'            => 1,
			'progress'        => 1,
			'full'            => 0,
			'height_desktop'  => 620,
			'height_tablet'   => 540,
			'height_mobile'   => 600,
			'search'          => 1,
			'search_position' => 'overlap',
			'search_style'    => 'solid',
			'search_fields'   => 'location,type,price',
			'buy_label'       => __( 'Buy', 'makaazi-slider' ),
			'rent_label'      => __( 'Rent', 'makaazi-slider' ),
			'default_status'  => 'sale',
			'button_text'     => __( 'Search', 'makaazi-slider' ),
		);
	}

	/**
	 * Default slide.
	 *
	 * @return array
	 */
	public static function slide_defaults() {
		return array(
			'image'        => 0,
			'mobile_image' => 0,
			'video'        => '',
			'eyebrow'      => '',
			'title'        => '',
			'text'         => '',
			'btn1_text'    => '',
			'btn1_url'     => '',
			'btn2_text'    => '',
			'btn2_url'     => '',
			'align'        => 'left',
			'overlay'      => 55,
			'from'         => '',
			'until'        => '',
		);
	}

	/**
	 * Settings for a slider.
	 *
	 * @param int $id Slider ID.
	 * @return array
	 */
	public static function settings( $id ) {
		$s = get_post_meta( $id, '_mks_settings', true );
		return wp_parse_args( is_array( $s ) ? $s : array(), self::defaults() );
	}

	/**
	 * Slides for a slider.
	 *
	 * @param int $id Slider ID.
	 * @return array
	 */
	public static function slides( $id ) {
		$s   = get_post_meta( $id, '_mks_slides', true );
		$out = array();
		foreach ( is_array( $s ) ? $s : array() as $slide ) {
			$out[] = wp_parse_args( $slide, self::slide_defaults() );
		}
		return $out;
	}

	/**
	 * Shortcode reference entry (shown on Properties › Shortcodes when Makaazi Core is active).
	 *
	 * @param array $docs Docs.
	 * @return array
	 */
	public static function docs( $docs ) {
		array_unshift(
			$docs,
			array(
				'tag'      => 'makaazi_slider',
				'group'    => __( 'Hero slider', 'makaazi-slider' ),
				'desc'     => __( 'A hero slider from Properties › Hero Sliders, with its Buy/Rent search bar. Attributes override the slider\'s own settings.', 'makaazi-slider' ),
				'atts'     => array(
					'id'              => array( '', __( 'Slider ID (shown on the slider screen)', 'makaazi-slider' ) ),
					'slug'            => array( '', __( 'Or the slider slug, e.g. home-hero (portable between sites)', 'makaazi-slider' ) ),
					'effect'          => array( '', 'fade, slide, kenburns' ),
					'autoplay'        => array( '', 'yes, no' ),
					'delay'           => array( '', __( 'Seconds per slide', 'makaazi-slider' ) ),
					'height'          => array( '', __( 'Desktop height in px, or full', 'makaazi-slider' ) ),
					'search'          => array( '', 'yes, no' ),
					'search_position' => array( '', 'overlap, inside, below' ),
					'search_style'    => array( '', 'solid, glass, minimal' ),
					'search_fields'   => array( '', 'location,type,price,beds,keyword' ),
				),
				'examples' => array(
					'[makaazi_slider slug="home-hero"]',
					'[makaazi_slider id="12" effect="kenburns" search_style="glass" search_position="inside"]',
				),
			)
		);
		return $docs;
	}
}
