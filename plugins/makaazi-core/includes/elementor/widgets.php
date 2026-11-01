<?php
/**
 * The Makaazi Elementor widgets. Each maps its controls to a shortcode's attributes.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared option lists.
 */
class Makaazi_EW_Options {

	/**
	 * Buy/Rent.
	 *
	 * @param string $any Label for "any".
	 * @return array
	 */
	public static function status( $any ) {
		return array(
			''     => $any,
			'sale' => __( 'For sale', 'makaazi' ),
			'rent' => __( 'To rent', 'makaazi' ),
		);
	}

	/**
	 * Residential/commercial.
	 *
	 * @return array
	 */
	public static function uses() {
		return array(
			''            => __( 'Any', 'makaazi' ),
			'residential' => __( 'Residential', 'makaazi' ),
			'commercial'  => __( 'Commercial', 'makaazi' ),
		);
	}

	/**
	 * Layouts.
	 *
	 * @return array
	 */
	public static function layouts() {
		return array(
			'grid'     => __( 'Grid', 'makaazi' ),
			'carousel' => __( 'Carousel', 'makaazi' ),
			'list'     => __( 'List', 'makaazi' ),
		);
	}

	/**
	 * Columns 1–6.
	 *
	 * @param int $max Max.
	 * @return array
	 */
	public static function cols( $max = 6 ) {
		$o = array( '' => __( 'Default', 'makaazi' ) );
		for ( $i = 1; $i <= $max; $i++ ) {
			$o[ (string) $i ] = (string) $i;
		}
		return $o;
	}

	/**
	 * Search fields.
	 *
	 * @return array
	 */
	public static function search_fields() {
		return array(
			'location' => __( 'Location', 'makaazi' ),
			'type'     => __( 'Property type', 'makaazi' ),
			'price'    => __( 'Price range', 'makaazi' ),
			'beds'     => __( 'Bedrooms', 'makaazi' ),
			'keyword'  => __( 'Keyword or reference', 'makaazi' ),
		);
	}

	/**
	 * Default / yes / no for overrides.
	 *
	 * @return array
	 */
	public static function tri() {
		return array(
			''    => __( 'Slider setting', 'makaazi' ),
			'yes' => __( 'Yes', 'makaazi' ),
			'no'  => __( 'No', 'makaazi' ),
		);
	}
}

/**
 * Hero Slider.
 */
class Makaazi_EW_Slider extends Makaazi_Elementor_Widget {
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_slider';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'Hero Slider', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-slider-full-screen mk-ew-icon';
	}
	/** @inheritDoc */
	protected function help_article() {
		return 'hero-slider';
	}
	/** @inheritDoc */
	public function get_script_depends() {
		return array( 'makaazi-slider', 'makaazi', 'makaazi-elementor' );
	}
	/** @inheritDoc */
	public function get_style_depends() {
		return array( 'makaazi-slider', 'makaazi' );
	}
	/** @inheritDoc */
	protected function sections() {
		return array(
			'content' => __( 'Slider', 'makaazi' ),
			'search'  => __( 'Search bar', 'makaazi' ),
		);
	}
	/** @inheritDoc */
	protected function fields() {
		return array(
			'id'              => array(
				'type'    => 'select',
				'label'   => __( 'Slider', 'makaazi' ),
				'options' => function () {
					return self::posts( 'mk_slider', __( '— Newest slider —', 'makaazi' ) );
				},
				'default' => '',
				'help'    => __( 'Create and edit slides under Properties › Hero Sliders. The options below override that slider’s own settings.', 'makaazi' ),
			),
			'effect'          => array(
				'type'    => 'select',
				'label'   => __( 'Effect', 'makaazi' ),
				'options' => array(
					''         => __( 'Slider setting', 'makaazi' ),
					'fade'     => __( 'Fade', 'makaazi' ),
					'slide'    => __( 'Slide', 'makaazi' ),
					'kenburns' => __( 'Ken Burns (slow zoom)', 'makaazi' ),
				),
			),
			'autoplay'        => array(
				'type'    => 'select',
				'label'   => __( 'Autoplay', 'makaazi' ),
				'options' => Makaazi_EW_Options::tri(),
			),
			'delay'           => array(
				'type'  => 'number',
				'label' => __( 'Seconds per slide', 'makaazi' ),
				'min'   => 2,
				'max'   => 20,
			),
			'height'          => array(
				'type'        => 'text',
				'label'       => __( 'Height (desktop)', 'makaazi' ),
				'placeholder' => __( 'e.g. 640 or full', 'makaazi' ),
			),
			'search'          => array(
				'type'    => 'select',
				'label'   => __( 'Show search bar', 'makaazi' ),
				'options' => Makaazi_EW_Options::tri(),
				'section' => 'search',
			),
			'search_position' => array(
				'type'    => 'select',
				'label'   => __( 'Position', 'makaazi' ),
				'options' => array(
					''        => __( 'Slider setting', 'makaazi' ),
					'overlap' => __( 'Overlapping the bottom edge', 'makaazi' ),
					'inside'  => __( 'Inside the slide', 'makaazi' ),
					'below'   => __( 'Below the slider', 'makaazi' ),
				),
				'section' => 'search',
			),
			'search_style'    => array(
				'type'    => 'select',
				'label'   => __( 'Style', 'makaazi' ),
				'options' => array(
					''        => __( 'Slider setting', 'makaazi' ),
					'solid'   => __( 'Solid', 'makaazi' ),
					'glass'   => __( 'Glass (over the image)', 'makaazi' ),
					'minimal' => __( 'Minimal', 'makaazi' ),
				),
				'section' => 'search',
			),
			'search_fields'   => array(
				'type'    => 'multi',
				'label'   => __( 'Fields (in this order)', 'makaazi' ),
				'options' => Makaazi_EW_Options::search_fields(),
				'help'    => __( 'Leave empty to use the slider’s fields.', 'makaazi' ),
				'section' => 'search',
			),
		);
	}
	/** @inheritDoc */
	protected function typography_targets() {
		return array(
			'heading' => array( __( 'Slide heading', 'makaazi' ), '{{WRAPPER}} .mks__title' ),
			'text'    => array( __( 'Slide text', 'makaazi' ), '{{WRAPPER}} .mks__text' ),
		);
	}
	/** @inheritDoc */
	protected function var_selector() {
		return '{{WRAPPER}} .mks-wrap, {{WRAPPER}} .mk-search';
	}
	/** @inheritDoc */
	protected function empty_message() {
		return __( 'Add a slider under Properties › Hero Sliders, then choose it here.', 'makaazi' );
	}
}

/**
 * Properties: grid, carousel or list.
 */
class Makaazi_EW_Properties extends Makaazi_Elementor_Widget {
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_properties';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'Properties', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-posts-grid mk-ew-icon';
	}
	/** @inheritDoc */
	protected function help_article() {
		return 'shortcodes';
	}
	/** @inheritDoc */
	protected function sections() {
		return array(
			'content' => __( 'Which properties', 'makaazi' ),
			'layout'  => __( 'Layout', 'makaazi' ),
			'heading' => __( 'Heading', 'makaazi' ),
			'card'    => __( 'Cards', 'makaazi' ),
		);
	}
	/** @inheritDoc */
	protected function fields() {
		return array(
			'source'         => array(
				'type'    => 'select',
				'label'   => __( 'Show', 'makaazi' ),
				'options' => array(
					'all'      => __( 'All properties', 'makaazi' ),
					'featured' => __( 'Featured', 'makaazi' ),
					'sale'     => __( 'For sale', 'makaazi' ),
					'rent'     => __( 'To rent', 'makaazi' ),
					'ids'      => __( 'Hand-picked', 'makaazi' ),
				),
				'default' => 'featured',
			),
			'ids'            => array(
				'type'      => 'multi',
				'label'     => __( 'Properties', 'makaazi' ),
				'options'   => function () {
					return self::posts( 'property', '', 300 );
				},
				'condition' => array( 'source' => 'ids' ),
			),
			'count'          => array(
				'type'    => 'number',
				'label'   => __( 'Number of properties', 'makaazi' ),
				'min'     => 1,
				'max'     => 48,
				'default' => 6,
			),
			'status'         => array(
				'type'    => 'select',
				'label'   => __( 'Buy / Rent', 'makaazi' ),
				'options' => Makaazi_EW_Options::status( __( 'Any', 'makaazi' ) ),
				'default' => '',
			),
			'location'       => array(
				'type'    => 'select',
				'label'   => __( 'Location', 'makaazi' ),
				'options' => function () {
					return self::terms( 'property_location', __( 'Any', 'makaazi' ) );
				},
				'default' => '',
				'help'    => __( 'Includes the areas inside it.', 'makaazi' ),
			),
			'type'           => array(
				'type'    => 'multi',
				'label'   => __( 'Property types', 'makaazi' ),
				'options' => function () {
					return self::terms( 'property_type' );
				},
			),
			'category'       => array(
				'type'    => 'multi',
				'label'   => __( 'Categories', 'makaazi' ),
				'options' => function () {
					return self::terms( 'property_category' );
				},
			),
			'use'            => array(
				'type'    => 'select',
				'label'   => __( 'Use', 'makaazi' ),
				'options' => Makaazi_EW_Options::uses(),
				'default' => '',
			),
			'agent'          => array(
				'type'    => 'select',
				'label'   => __( 'Agent', 'makaazi' ),
				'options' => function () {
					return self::posts( 'mk_agent', __( 'Any', 'makaazi' ) );
				},
				'default' => '',
			),
			'orderby'        => array(
				'type'    => 'select',
				'label'   => __( 'Order', 'makaazi' ),
				'options' => array(
					'newest'     => __( 'Newest', 'makaazi' ),
					'featured'   => __( 'Featured first', 'makaazi' ),
					'price_asc'  => __( 'Price: low to high', 'makaazi' ),
					'price_desc' => __( 'Price: high to low', 'makaazi' ),
					'largest'    => __( 'Largest', 'makaazi' ),
					'random'     => __( 'Random', 'makaazi' ),
				),
				'default' => 'newest',
			),
			'include_closed' => array(
				'type'  => 'switch',
				'label' => __( 'Include sold and let', 'makaazi' ),
			),
			'layout'         => array(
				'type'    => 'select',
				'label'   => __( 'Layout', 'makaazi' ),
				'options' => Makaazi_EW_Options::layouts(),
				'default' => 'grid',
				'section' => 'layout',
			),
			'columns'        => array(
				'type'           => 'select',
				'label'          => __( 'Per row', 'makaazi' ),
				'options'        => Makaazi_EW_Options::cols( 6 ),
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'responsive'     => true,
				'section'        => 'layout',
			),
			'pagination'     => array(
				'type'      => 'select',
				'label'     => __( 'More properties', 'makaazi' ),
				'options'   => array(
					'none'     => __( 'None', 'makaazi' ),
					'loadmore' => __( 'Load more button', 'makaazi' ),
					'numbered' => __( 'Page numbers', 'makaazi' ),
				),
				'default'   => 'none',
				'condition' => array( 'layout!' => 'carousel' ),
				'section'   => 'layout',
			),
			'eyebrow'        => array(
				'type'    => 'text',
				'label'   => __( 'Small label', 'makaazi' ),
				'default' => __( 'Hand-picked', 'makaazi' ),
				'section' => 'heading',
			),
			'title'          => array(
				'type'    => 'text',
				'label'   => __( 'Title', 'makaazi' ),
				'default' => __( 'Featured properties', 'makaazi' ),
				'section' => 'heading',
			),
			'subtitle'       => array(
				'type'    => 'text',
				'label'   => __( 'Subtitle', 'makaazi' ),
				'section' => 'heading',
			),
			'view_all'       => array(
				'type'    => 'switch',
				'label'   => __( '“View all” link', 'makaazi' ),
				'default' => true,
				'section' => 'heading',
			),
			'view_all_text'  => array(
				'type'      => 'text',
				'label'     => __( 'Link text', 'makaazi' ),
				'condition' => array( 'view_all' => 'yes' ),
				'section'   => 'heading',
			),
			'button_text'    => array(
				'type'    => 'text',
				'label'   => __( 'Button text', 'makaazi' ),
				'help'    => __( 'Empty uses Settings › Cards.', 'makaazi' ),
				'section' => 'card',
			),
			'image_ratio'    => array(
				'type'    => 'select',
				'label'   => __( 'Photo shape', 'makaazi' ),
				'options' => array(
					''     => __( 'Default', 'makaazi' ),
					'4:3'  => '4:3',
					'3:2'  => '3:2',
					'16:9' => '16:9',
					'1:1'  => '1:1',
				),
				'section' => 'card',
			),
			'hide'           => array(
				'type'    => 'multi',
				'label'   => __( 'Hide on cards', 'makaazi' ),
				'options' => self::card_parts(),
				'section' => 'card',
			),
		);
	}
	/** @inheritDoc */
	protected function empty_message() {
		return __( 'No properties match these settings.', 'makaazi' );
	}
}

/**
 * Full Properties page with filters.
 */
class Makaazi_EW_Listing extends Makaazi_Elementor_Widget {
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_property_listing';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'Property Listing', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-archive-posts mk-ew-icon';
	}
	/** @inheritDoc */
	public function get_script_depends() {
		return array( 'leaflet', 'makaazi', 'makaazi-elementor' );
	}
	/** @inheritDoc */
	public function get_style_depends() {
		return array( 'leaflet', 'makaazi' );
	}
	/** @inheritDoc */
	protected function sections() {
		return array(
			'content' => __( 'Listing', 'makaazi' ),
			'fixed'   => __( 'Limit to', 'makaazi' ),
		);
	}
	/** @inheritDoc */
	protected function fields() {
		return array(
			'filters'      => array(
				'type'    => 'select',
				'label'   => __( 'Filters', 'makaazi' ),
				'options' => array(
					'sidebar' => __( 'Sidebar', 'makaazi' ),
					'top'     => __( 'Bar on top', 'makaazi' ),
					'none'    => __( 'None', 'makaazi' ),
				),
				'default' => 'sidebar',
			),
			'views'        => array(
				'type'    => 'multi',
				'label'   => __( 'View buttons', 'makaazi' ),
				'options' => array(
					'grid' => __( 'Grid', 'makaazi' ),
					'list' => __( 'List', 'makaazi' ),
					'map'  => __( 'Map', 'makaazi' ),
				),
				'default' => array( 'grid', 'list', 'map' ),
			),
			'default_view' => array(
				'type'    => 'select',
				'label'   => __( 'Start in', 'makaazi' ),
				'options' => array(
					'grid' => __( 'Grid', 'makaazi' ),
					'list' => __( 'List', 'makaazi' ),
					'map'  => __( 'Map', 'makaazi' ),
				),
				'default' => 'grid',
			),
			'columns'      => array(
				'type'    => 'select',
				'label'   => __( 'Per row', 'makaazi' ),
				'options' => Makaazi_EW_Options::cols( 4 ),
				'default' => '3',
			),
			'per_page'     => array(
				'type'  => 'number',
				'label' => __( 'Per page', 'makaazi' ),
				'min'   => 1,
				'max'   => 48,
				'help'  => __( 'Empty uses Settings › Properties page.', 'makaazi' ),
			),
			'title'        => array(
				'type'  => 'text',
				'label' => __( 'Heading', 'makaazi' ),
				'help'  => __( 'Empty shows “12 properties for sale…”.', 'makaazi' ),
			),
			'button_text'  => array(
				'type'  => 'text',
				'label' => __( 'Card button text', 'makaazi' ),
			),
			'status'       => array(
				'type'    => 'select',
				'label'   => __( 'Buy / Rent', 'makaazi' ),
				'options' => Makaazi_EW_Options::status( __( 'Visitor chooses', 'makaazi' ) ),
				'default' => '',
				'section' => 'fixed',
			),
			'location'     => array(
				'type'    => 'select',
				'label'   => __( 'Location', 'makaazi' ),
				'options' => function () {
					return self::terms( 'property_location', __( 'Visitor chooses', 'makaazi' ) );
				},
				'default' => '',
				'section' => 'fixed',
			),
			'type'         => array(
				'type'    => 'select',
				'label'   => __( 'Type', 'makaazi' ),
				'options' => function () {
					return self::terms( 'property_type', __( 'Visitor chooses', 'makaazi' ) );
				},
				'default' => '',
				'section' => 'fixed',
			),
			'category'     => array(
				'type'    => 'select',
				'label'   => __( 'Category', 'makaazi' ),
				'options' => function () {
					return self::terms( 'property_category', __( 'Any', 'makaazi' ) );
				},
				'default' => '',
				'section' => 'fixed',
			),
			'use'          => array(
				'type'    => 'select',
				'label'   => __( 'Use', 'makaazi' ),
				'options' => Makaazi_EW_Options::uses(),
				'default' => '',
				'section' => 'fixed',
			),
		);
	}
	/** @inheritDoc */
	protected function typography_targets() {
		return array(
			'heading'    => array( __( 'Results heading', 'makaazi' ), '{{WRAPPER}} .mk-results__title' ),
			'card_title' => array( __( 'Card title', 'makaazi' ), '{{WRAPPER}} .mk-card__title' ),
			'card_price' => array( __( 'Price', 'makaazi' ), '{{WRAPPER}} .mk-card__price' ),
			'button'     => array( __( 'Buttons', 'makaazi' ), '{{WRAPPER}} .mk-btn' ),
		);
	}
}

/**
 * Search bar.
 */
class Makaazi_EW_Search extends Makaazi_Elementor_Widget {
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_search';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'Property Search', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-search-bold mk-ew-icon';
	}
	/** @inheritDoc */
	protected function sections() {
		return array(
			'content' => __( 'Search', 'makaazi' ),
			'labels'  => __( 'Labels', 'makaazi' ),
		);
	}
	/** @inheritDoc */
	protected function fields() {
		return array(
			'fields'         => array(
				'type'    => 'multi',
				'label'   => __( 'Fields (in this order)', 'makaazi' ),
				'options' => Makaazi_EW_Options::search_fields(),
				'default' => array( 'location', 'type', 'price' ),
			),
			'style'          => array(
				'type'    => 'select',
				'label'   => __( 'Style', 'makaazi' ),
				'options' => array(
					'solid'   => __( 'Solid', 'makaazi' ),
					'glass'   => __( 'Glass (over an image)', 'makaazi' ),
					'minimal' => __( 'Minimal', 'makaazi' ),
				),
				'default' => 'solid',
			),
			'show_tabs'      => array(
				'type'    => 'switch',
				'label'   => __( 'Buy / Rent tabs', 'makaazi' ),
				'default' => true,
			),
			'default_status' => array(
				'type'    => 'select',
				'label'   => __( 'Open on', 'makaazi' ),
				'options' => array(
					'sale' => __( 'Buy', 'makaazi' ),
					'rent' => __( 'Rent', 'makaazi' ),
				),
				'default' => 'sale',
			),
			'button_text'    => array(
				'type'    => 'text',
				'label'   => __( 'Button', 'makaazi' ),
				'default' => __( 'Search', 'makaazi' ),
				'section' => 'labels',
			),
			'buy_label'      => array(
				'type'    => 'text',
				'label'   => __( 'Buy tab', 'makaazi' ),
				'default' => __( 'Buy', 'makaazi' ),
				'section' => 'labels',
			),
			'rent_label'     => array(
				'type'    => 'text',
				'label'   => __( 'Rent tab', 'makaazi' ),
				'default' => __( 'Rent', 'makaazi' ),
				'section' => 'labels',
			),
			'location_label' => array(
				'type'    => 'text',
				'label'   => __( 'Location', 'makaazi' ),
				'default' => __( 'Location', 'makaazi' ),
				'section' => 'labels',
			),
			'type_label'     => array(
				'type'    => 'text',
				'label'   => __( 'Property type', 'makaazi' ),
				'default' => __( 'Property type', 'makaazi' ),
				'section' => 'labels',
			),
		);
	}
	/** @inheritDoc */
	protected function typography_targets() {
		return array(
			'button' => array( __( 'Button', 'makaazi' ), '{{WRAPPER}} .mk-btn' ),
			'labels' => array( __( 'Field labels', 'makaazi' ), '{{WRAPPER}} .mk-fg__label' ),
		);
	}
}

/**
 * Browse by location.
 */
class Makaazi_EW_Locations extends Makaazi_Elementor_Widget {
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_locations';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'Locations', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-google-maps mk-ew-icon';
	}
	/** @inheritDoc */
	protected function help_article() {
		return 'taxonomies';
	}
	/** @inheritDoc */
	protected function fields() {
		return array_merge(
			array(
				'locations' => array(
					'type'    => 'multi',
					'label'   => __( 'Locations (in this order)', 'makaazi' ),
					'options' => function () {
						return self::terms( 'property_location' );
					},
					'help'    => __( 'Empty shows the most popular areas.', 'makaazi' ),
				),
				'parent'    => array(
					'type'      => 'select',
					'label'     => __( 'Only areas inside', 'makaazi' ),
					'options'   => function () {
						return self::terms( 'property_location', __( 'Anywhere', 'makaazi' ) );
					},
					'default'   => '',
					'help'      => __( 'Used when no locations are picked above.', 'makaazi' ),
				),
				'count'     => array(
					'type'    => 'number',
					'label'   => __( 'How many', 'makaazi' ),
					'min'     => 1,
					'max'     => 24,
					'default' => 6,
				),
				'layout'    => array(
					'type'    => 'select',
					'label'   => __( 'Layout', 'makaazi' ),
					'options' => array(
						'tiles' => __( 'Photo tiles', 'makaazi' ),
						'list'  => __( 'List', 'makaazi' ),
					),
					'default' => 'tiles',
				),
				'columns'   => array(
					'type'    => 'select',
					'label'   => __( 'Per row', 'makaazi' ),
					'options' => Makaazi_EW_Options::cols( 6 ),
					'default' => '3',
				),
				'status'    => array(
					'type'    => 'select',
					'label'   => __( 'Link to', 'makaazi' ),
					'options' => Makaazi_EW_Options::status( __( 'All listings', 'makaazi' ) ),
					'default' => '',
				),
			),
			array(
				'eyebrow' => array(
					'type'    => 'text',
					'label'   => __( 'Small label', 'makaazi' ),
					'default' => __( 'Neighbourhoods', 'makaazi' ),
				),
				'title'   => array(
					'type'    => 'text',
					'label'   => __( 'Title', 'makaazi' ),
					'default' => __( 'Browse by location', 'makaazi' ),
				),
			)
		);
	}
	/** @inheritDoc */
	protected function typography_targets() {
		return array(
			'heading' => array( __( 'Section title', 'makaazi' ), '{{WRAPPER}} .mk-sechead__title' ),
			'tile'    => array( __( 'Tile name', 'makaazi' ), '{{WRAPPER}} .mk-loc b' ),
		);
	}
}

/**
 * Agents grid.
 */
class Makaazi_EW_Agents extends Makaazi_Elementor_Widget {
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_agents';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'Agents', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-person mk-ew-icon';
	}
	/** @inheritDoc */
	protected function help_article() {
		return 'agents';
	}
	/** @inheritDoc */
	protected function fields() {
		return array(
			'ids'     => array(
				'type'    => 'multi',
				'label'   => __( 'Agents (in this order)', 'makaazi' ),
				'options' => function () {
					return self::posts( 'mk_agent' );
				},
				'help'    => __( 'Empty shows all agents.', 'makaazi' ),
			),
			'count'   => array(
				'type'    => 'number',
				'label'   => __( 'How many', 'makaazi' ),
				'min'     => 1,
				'max'     => 48,
				'default' => 4,
			),
			'columns' => array(
				'type'    => 'select',
				'label'   => __( 'Per row', 'makaazi' ),
				'options' => Makaazi_EW_Options::cols( 6 ),
				'default' => '4',
			),
			'orderby' => array(
				'type'    => 'select',
				'label'   => __( 'Order', 'makaazi' ),
				'options' => array(
					'name'     => __( 'Name', 'makaazi' ),
					'listings' => __( 'Most listings', 'makaazi' ),
				),
				'default' => 'name',
			),
			'eyebrow' => array(
				'type'    => 'text',
				'label'   => __( 'Small label', 'makaazi' ),
				'default' => __( 'Our team', 'makaazi' ),
			),
			'title'   => array(
				'type'    => 'text',
				'label'   => __( 'Title', 'makaazi' ),
				'default' => __( 'Meet our agents', 'makaazi' ),
			),
		);
	}
	/** @inheritDoc */
	protected function typography_targets() {
		return array(
			'heading' => array( __( 'Section title', 'makaazi' ), '{{WRAPPER}} .mk-sechead__title' ),
			'name'    => array( __( 'Agent name', 'makaazi' ), '{{WRAPPER}} .mk-agentcard__body h3' ),
		);
	}
	/** @inheritDoc */
	protected function empty_message() {
		return makaazi_agents_on() ? __( 'No agents yet. Add them under Properties › Agents.', 'makaazi' ) : __( 'Agents are switched off in Properties › Settings › Features.', 'makaazi' );
	}
}

/**
 * One part of a property page.
 */
class Makaazi_EW_Property extends Makaazi_Elementor_Widget {
	/**
	 * Parts.
	 *
	 * @return array
	 */
	protected static function parts() {
		return array(
			'all'         => __( 'Whole property page', 'makaazi' ),
			'header'      => __( 'Header (title, price, share)', 'makaazi' ),
			'gallery'     => __( 'Photo gallery', 'makaazi' ),
			'facts'       => __( 'Key facts', 'makaazi' ),
			'description' => __( 'Description', 'makaazi' ),
			'details'     => __( 'Details table', 'makaazi' ),
			'features'    => __( 'Features', 'makaazi' ),
			'map'         => __( 'Map', 'makaazi' ),
			'contact'     => __( 'Contact and enquiry', 'makaazi' ),
			'related'     => __( 'Similar properties', 'makaazi' ),
		);
	}
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_property';
	}
	/** @inheritDoc */
	public function get_name() {
		return 'makaazi-property-section';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'Property Section', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-single-post mk-ew-icon';
	}
	/** @inheritDoc */
	protected function help_article() {
		return 'add-property';
	}
	/** @inheritDoc */
	public function get_keywords() {
		return array_merge( parent::get_keywords(), array( 'gallery', 'map', 'single', 'contact', 'enquiry', 'related' ) );
	}
	/** @inheritDoc */
	public function get_script_depends() {
		return array( 'leaflet', 'makaazi', 'makaazi-elementor' );
	}
	/** @inheritDoc */
	public function get_style_depends() {
		return array( 'leaflet', 'makaazi' );
	}
	/** @inheritDoc */
	protected function fields() {
		return array(
			'part'    => array(
				'type'    => 'select',
				'label'   => __( 'Section', 'makaazi' ),
				'options' => self::parts(),
				'default' => 'gallery',
				'virtual' => true,
			),
			'id'      => array(
				'type'    => 'select',
				'label'   => __( 'Property', 'makaazi' ),
				'options' => function () {
					return self::posts( 'property', __( 'Current property', 'makaazi' ), 300 );
				},
				'default' => '',
				'help'    => __( 'Keep “Current property” in a single-property template. Pick one to place it on any page.', 'makaazi' ),
			),
			'title'   => array(
				'type'      => 'text',
				'label'     => __( 'Section heading', 'makaazi' ),
				'condition' => array( 'part' => array( 'details', 'features', 'map', 'description', 'related' ) ),
			),
			'eyebrow' => array(
				'type'      => 'text',
				'label'     => __( 'Small label', 'makaazi' ),
				'condition' => array( 'part' => 'related' ),
			),
			'count'   => array(
				'type'      => 'number',
				'label'     => __( 'How many', 'makaazi' ),
				'min'       => 1,
				'max'       => 24,
				'default'   => 8,
				'condition' => array( 'part' => 'related' ),
			),
			'layout'  => array(
				'type'      => 'select',
				'label'     => __( 'Layout', 'makaazi' ),
				'options'   => Makaazi_EW_Options::layouts(),
				'default'   => 'carousel',
				'condition' => array( 'part' => 'related' ),
			),
			'columns' => array(
				'type'      => 'select',
				'label'     => __( 'Per row', 'makaazi' ),
				'options'   => Makaazi_EW_Options::cols( 6 ),
				'default'   => '4',
				'condition' => array( 'part' => 'related' ),
			),
		);
	}
	/** @inheritDoc */
	protected function typography_targets() {
		return array(
			'title' => array( __( 'Property title', 'makaazi' ), '{{WRAPPER}} .mk-phead__title' ),
			'price' => array( __( 'Price', 'makaazi' ), '{{WRAPPER}} .mk-phead__price' ),
			'head'  => array( __( 'Section headings', 'makaazi' ), '{{WRAPPER}} .mk-sec__title, {{WRAPPER}} .mk-sechead__title' ),
		);
	}
	/**
	 * Pick the shortcode for the chosen part.
	 */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$part = isset( $s['part'] ) && isset( self::parts()[ $s['part'] ] ) ? $s['part'] : 'gallery';
		$atts = $this->atts( $s );
		if ( 'related' !== $part ) {
			unset( $atts['count'], $atts['layout'], $atts['columns'], $atts['eyebrow'] );
		}
		if ( ! in_array( $part, array( 'details', 'features', 'map', 'description', 'related' ), true ) ) {
			unset( $atts['title'] );
		}
		$id = isset( $atts['id'] ) ? (int) $atts['id'] : 0;
		if ( ! $id && 'property' !== get_post_type() ) {
			if ( self::editing() ) {
				// Preview with the newest property so the editor shows something real.
				$latest = get_posts(
					array(
						'post_type'      => 'property',
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				);
				$atts['id'] = $latest ? (string) $latest[0] : '';
			}
			if ( empty( $atts['id'] ) ) {
				echo $this->placeholder( __( 'Choose a property, or use this widget in a single-property template.', 'makaazi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				return;
			}
		}
		$tag = 'all' === $part ? 'makaazi_property' : ( 'related' === $part ? 'makaazi_related_properties' : 'makaazi_property_' . $part );
		Makaazi_Assets::enqueue( in_array( $part, array( 'all', 'map' ), true ) );
		$html = do_shortcode( self::build( $tag, $atts ) );
		echo '' !== trim( $html ) ? $html : $this->placeholder( __( 'This property has nothing to show in this section.', 'makaazi' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * Favourites, compare and the saved-count link.
 */
class Makaazi_EW_Shortlist extends Makaazi_Elementor_Widget {
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_favourites';
	}
	/** @inheritDoc */
	public function get_name() {
		return 'makaazi-shortlist';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'Favourites & Compare', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-heart-o mk-ew-icon';
	}
	/** @inheritDoc */
	protected function help_article() {
		return 'favourites';
	}
	/** @inheritDoc */
	protected function fields() {
		return array(
			'show'    => array(
				'type'    => 'select',
				'label'   => __( 'Show', 'makaazi' ),
				'options' => array(
					'favourites' => __( 'Saved properties page', 'makaazi' ),
					'compare'    => __( 'Compare table', 'makaazi' ),
					'link'       => __( 'Heart link with count', 'makaazi' ),
				),
				'default' => 'favourites',
				'virtual' => true,
			),
			'eyebrow' => array(
				'type'      => 'text',
				'label'     => __( 'Small label', 'makaazi' ),
				'condition' => array( 'show!' => 'link' ),
			),
			'title'   => array(
				'type'      => 'text',
				'label'     => __( 'Title', 'makaazi' ),
				'condition' => array( 'show!' => 'link' ),
			),
			'columns' => array(
				'type'      => 'select',
				'label'     => __( 'Per row', 'makaazi' ),
				'options'   => Makaazi_EW_Options::cols( 4 ),
				'default'   => '3',
				'condition' => array( 'show' => 'favourites' ),
			),
			'label'   => array(
				'type'      => 'text',
				'label'     => __( 'Text next to the heart', 'makaazi' ),
				'condition' => array( 'show' => 'link' ),
			),
		);
	}
	/**
	 * Render the chosen shortcode.
	 */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$show = isset( $s['show'] ) ? $s['show'] : 'favourites';
		$atts = $this->atts( $s );
		if ( 'link' === $show ) {
			$tag  = 'makaazi_favourites_link';
			$atts = array_intersect_key( $atts, array( 'label' => 1 ) );
		} else {
			$tag = 'compare' === $show ? 'makaazi_compare' : 'makaazi_favourites';
			unset( $atts['label'] );
			if ( 'compare' === $show ) {
				unset( $atts['columns'] );
			}
		}
		echo do_shortcode( self::build( $tag, $atts ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * My account / dashboard, or the sign-in link.
 */
class Makaazi_EW_Account extends Makaazi_Elementor_Widget {
	/** @inheritDoc */
	protected function tag() {
		return 'makaazi_dashboard';
	}
	/** @inheritDoc */
	public function get_name() {
		return 'makaazi-account';
	}
	/** @inheritDoc */
	public function get_title() {
		return __( 'My Account', 'makaazi' );
	}
	/** @inheritDoc */
	public function get_icon() {
		return 'eicon-lock-user mk-ew-icon';
	}
	/** @inheritDoc */
	protected function help_article() {
		return 'owners';
	}
	/** @inheritDoc */
	protected function fields() {
		return array(
			'show'       => array(
				'type'    => 'select',
				'label'   => __( 'Show', 'makaazi' ),
				'options' => array(
					'dashboard' => __( 'Account dashboard (sign in, listings, enquiries)', 'makaazi' ),
					'link'      => __( 'Sign in / My account link', 'makaazi' ),
				),
				'default' => 'dashboard',
				'virtual' => true,
			),
			'signed_out' => array(
				'type'      => 'text',
				'label'     => __( 'Text when signed out', 'makaazi' ),
				'default'   => __( 'Sign in', 'makaazi' ),
				'condition' => array( 'show' => 'link' ),
			),
			'signed_in'  => array(
				'type'      => 'text',
				'label'     => __( 'Text when signed in', 'makaazi' ),
				'default'   => __( 'My account', 'makaazi' ),
				'condition' => array( 'show' => 'link' ),
			),
		);
	}
	/**
	 * Render.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		if ( isset( $s['show'] ) && 'link' === $s['show'] ) {
			echo do_shortcode( self::build( 'makaazi_account_link', $this->atts( $s ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			return;
		}
		echo do_shortcode( '[makaazi_dashboard]' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
