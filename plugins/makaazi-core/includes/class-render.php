<?php
/**
 * HTML renderers shared by shortcodes, REST responses and other plugins.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderers.
 */
class Makaazi_Render {

	/* ---------------------------------------------------------------------
	 * Cards
	 * ------------------------------------------------------------------- */

	/**
	 * Which card elements to show, after shortcode show/hide overrides.
	 *
	 * @param array $o Options with optional 'show' / 'hide' comma lists.
	 * @return string[]
	 */
	public static function card_show( $o = array() ) {
		$show = (array) makaazi_settings( 'card_show' );
		if ( ! empty( $o['show'] ) ) {
			$show = array_map( 'trim', explode( ',', $o['show'] ) );
		}
		if ( ! empty( $o['hide'] ) ) {
			$show = array_diff( $show, array_map( 'trim', explode( ',', $o['hide'] ) ) );
		}
		return $show;
	}

	/**
	 * Parts hidden with hide="…".
	 *
	 * @param array $o Options.
	 * @return string[]
	 */
	public static function hidden( $o ) {
		return empty( $o['hide'] ) ? array() : array_map( 'trim', explode( ',', $o['hide'] ) );
	}

	/**
	 * Prepare card data.
	 *
	 * @param int   $id Post ID.
	 * @param array $o  Options: layout, button_text, image_ratio, show, hide, why.
	 * @return array
	 */
	public static function card_data( $id, $o = array() ) {
		$show   = self::card_show( $o );
		$on     = function ( $k ) use ( $show ) {
			return in_array( $k, $show, true );
		};
		$ids    = makaazi_gallery_ids( $id );
		$max    = max( 1, (int) makaazi_settings( 'card_images' ) );
		$slice  = array_slice( $ids, 0, $max );
		$images = array();
		foreach ( $slice as $i => $aid ) {
			$images[] = wp_get_attachment_image(
				$aid,
				'makaazi-card',
				false,
				array(
					'loading'  => $i ? 'lazy' : ( empty( $o['eager'] ) ? 'lazy' : 'eager' ),
					'decoding' => 'async',
					'sizes'    => '(max-width: 700px) 100vw, 400px',
					'alt'      => $i ? '' : get_the_title( $id ),
				)
			);
		}
		if ( ! $images ) {
			$images[] = '<img src="' . esc_url( makaazi_placeholder() ) . '" alt="" loading="lazy">';
		}

		$status = makaazi_status( $id );
		$type   = makaazi_term( $id, 'property_type' );
		$cat    = makaazi_term( $id, 'property_category' );
		$uses   = makaazi_term_names( $id, 'property_use' );
		$meta   = array();
		if ( $on( 'category' ) && $cat ) {
			$meta[] = $cat->name;
		}
		if ( $on( 'use' ) && 1 === count( $uses ) ) {
			$meta[] = $uses[0];
		}
		if ( $on( 'type' ) && $type ) {
			$meta[] = $type->name;
		}

		$is_land = $type && 'land' === $type->slug;
		$specs   = array();
		$beds    = (int) makaazi_meta( $id, 'bedrooms', 0 );
		$baths   = (float) makaazi_meta( $id, 'bathrooms', 0 );
		$park    = (int) makaazi_meta( $id, 'parking', 0 );
		$area    = (float) makaazi_meta( $id, 'area_sqm', 0 );
		if ( $on( 'beds' ) && $beds ) {
			$specs[] = array( 'icon' => 'bed', 'label' => __( 'Bedrooms', 'makaazi' ), 'html' => esc_html( $beds ) );
		}
		if ( $on( 'baths' ) && $baths ) {
			$specs[] = array( 'icon' => 'bath', 'label' => __( 'Bathrooms', 'makaazi' ), 'html' => esc_html( 0 + $baths ) );
		}
		if ( $on( 'parking' ) && $park ) {
			$specs[] = array( 'icon' => 'car', 'label' => __( 'Parking', 'makaazi' ), 'html' => esc_html( $park ) );
		}
		if ( $on( 'area' ) ) {
			if ( $is_land && makaazi_land_text( $id ) ) {
				$specs[] = array( 'icon' => 'area', 'label' => __( 'Land size', 'makaazi' ), 'html' => esc_html( makaazi_land_text( $id ) ) );
			} elseif ( $area ) {
				$specs[] = array( 'icon' => 'area', 'label' => __( 'Area', 'makaazi' ), 'html' => makaazi_area_html( $area ) );
			}
		}

		$avail  = makaazi_meta( $id, 'availability', 'available' );
		$closed = '';
		if ( 'sold' === $avail ) {
			$closed = __( 'Sold', 'makaazi' );
		} elseif ( 'let' === $avail ) {
			$closed = __( 'Let', 'makaazi' );
		} elseif ( 'under_offer' === $avail ) {
			$closed = __( 'Under offer', 'makaazi' );
		}

		$ratio = isset( $o['image_ratio'] ) && $o['image_ratio'] ? $o['image_ratio'] : makaazi_settings( 'image_ratio' );
		$ratio = str_replace( ':', ' / ', preg_replace( '/[^0-9:]/', '', $ratio ) );

		$data = array(
			'id'           => $id,
			'url'          => get_permalink( $id ),
			'title'        => get_the_title( $id ),
			'layout'       => isset( $o['layout'] ) ? $o['layout'] : 'grid',
			'ratio'        => $ratio ? $ratio : '4 / 3',
			'images'       => $images,
			'extra'        => max( 0, count( $ids ) - count( $slice ) ),
			'show_badges'  => $on( 'badges' ),
			'status'       => $status,
			'status_label' => makaazi_status_label( $status ),
			'featured'     => (bool) makaazi_meta( $id, 'featured' ),
			'is_new'       => ( time() - get_post_time( 'U', true, $id ) ) < DAY_IN_SECONDS * (int) makaazi_settings( 'new_days' ),
			'closed'       => $closed,
			'meta_line'    => implode( ' · ', $meta ),
			'location'     => $on( 'location' ) ? makaazi_location_text( $id ) : '',
			'price'        => makaazi_price( $id ),
			'specs'        => $specs,
			'button_text'  => ! empty( $o['button_text'] ) ? $o['button_text'] : makaazi_settings( 'button_text' ),
			'favourite'    => (bool) makaazi_settings( 'card_favourite' ) && ! in_array( 'favourite', self::hidden( $o ), true ),
			'compare'      => (bool) makaazi_settings( 'card_compare' ) && ! in_array( 'compare', self::hidden( $o ), true ),
			'why'          => isset( $o['why'] ) ? (array) $o['why'] : array(),
		);
		/**
		 * Filter the data used to render a property card (add specs, change the price line…).
		 *
		 * @param array $data Card data.
		 * @param int   $id   Property ID.
		 * @param array $o    Card options from the shortcode/widget.
		 */
		return apply_filters( 'makaazi_card_data', $data, $id, $o );
	}

	/**
	 * Render one card.
	 *
	 * @param int   $id Post ID.
	 * @param array $o  Options.
	 * @return string
	 */
	public static function card( $id, $o = array() ) {
		return makaazi_template( 'card.php', array( 'card' => self::card_data( $id, $o ) ) );
	}

	/**
	 * Render a set of cards as grid, list or carousel.
	 *
	 * @param int[] $ids IDs.
	 * @param array $o   Options: layout, columns, columns_tablet, columns_mobile, plus card options.
	 * @return string
	 */
	public static function cards( $ids, $o = array() ) {
		$layout = isset( $o['layout'] ) ? $o['layout'] : 'grid';
		$cols   = max( 1, min( 6, (int) ( isset( $o['columns'] ) ? $o['columns'] : 3 ) ) );
		$tab    = max( 1, min( 4, (int) ( isset( $o['columns_tablet'] ) ? $o['columns_tablet'] : min( 2, $cols ) ) ) );
		$mob    = max( 1, min( 2, (int) ( isset( $o['columns_mobile'] ) ? $o['columns_mobile'] : 1 ) ) );
		$why    = isset( $o['why_map'] ) ? $o['why_map'] : array();
		$html   = '';
		foreach ( array_values( $ids ) as $i => $id ) {
			$co          = $o;
			$co['eager'] = $i < 2 && ! empty( $o['eager'] );
			if ( isset( $why[ $id ] ) ) {
				$co['why'] = $why[ $id ];
			}
			$html .= self::card( $id, $co );
		}
		if ( 'carousel' === $layout ) {
			return '<div class="mk-carousel" data-mk-carousel style="--mk-cols:' . $cols . ';--mk-cols-t:' . $tab . '">'
				. '<div class="mk-carousel__track">' . $html . '</div>'
				. '<div class="mk-carousel__nav"><button type="button" class="mk-round" data-mk-car="-1" aria-label="' . esc_attr__( 'Previous', 'makaazi' ) . '">' . makaazi_icon( 'left' ) . '</button>'
				. '<button type="button" class="mk-round" data-mk-car="1" aria-label="' . esc_attr__( 'Next', 'makaazi' ) . '">' . makaazi_icon( 'right' ) . '</button></div></div>';
		}
		if ( 'list' === $layout ) {
			return '<div class="mk-list">' . $html . '</div>';
		}
		return '<div class="mk-grid" style="--mk-cols:' . $cols . ';--mk-cols-t:' . $tab . ';--mk-cols-m:' . $mob . '">' . $html . '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Search form
	 * ------------------------------------------------------------------- */

	/**
	 * Location <option>s grouped by parent.
	 *
	 * @param string $selected Selected slug.
	 * @param string $all      Label for "all".
	 * @param bool   $parents  Allow choosing parent terms (counties).
	 * @return string
	 */
	public static function location_options( $selected = '', $all = '', $parents = true ) {
		$all   = $all ? $all : __( 'All locations', 'makaazi' );
		$terms = get_terms(
			array(
				'taxonomy'   => 'property_location',
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		$by    = array();
		foreach ( (array) $terms as $t ) {
			$by[ $t->parent ][] = $t;
		}
		$out = '<option value="">' . esc_html( $all ) . '</option>';
		foreach ( isset( $by[0] ) ? $by[0] : array() as $top ) {
			if ( empty( $by[ $top->term_id ] ) ) {
				$out .= '<option value="' . esc_attr( $top->slug ) . '"' . selected( $selected, $top->slug, false ) . '>' . esc_html( $top->name ) . '</option>';
				continue;
			}
			$out .= '<optgroup label="' . esc_attr( $top->name ) . '">';
			if ( $parents ) {
				/* translators: %s: county or city name */
				$out .= '<option value="' . esc_attr( $top->slug ) . '"' . selected( $selected, $top->slug, false ) . '>' . esc_html( sprintf( __( 'All of %s', 'makaazi' ), $top->name ) ) . '</option>';
			}
			foreach ( $by[ $top->term_id ] as $child ) {
				$out .= '<option value="' . esc_attr( $child->slug ) . '"' . selected( $selected, $child->slug, false ) . '>' . esc_html( $child->name ) . '</option>';
				if ( ! empty( $by[ $child->term_id ] ) ) {
					foreach ( $by[ $child->term_id ] as $g ) {
						$out .= '<option value="' . esc_attr( $g->slug ) . '"' . selected( $selected, $g->slug, false ) . '>&nbsp;&nbsp;' . esc_html( $g->name ) . '</option>';
					}
				}
			}
			$out .= '</optgroup>';
		}
		return $out;
	}

	/**
	 * Price bracket <option>s.
	 *
	 * @param string $status   sale|rent.
	 * @param int    $selected Selected value.
	 * @param string $empty    Empty label.
	 * @return string
	 */
	public static function price_options( $status, $selected, $empty ) {
		$out = '<option value="">' . esc_html( $empty ) . '</option>';
		foreach ( makaazi_brackets( $status ) as $v ) {
			$out .= '<option value="' . (int) $v . '"' . selected( (int) $selected, $v, false ) . '>' . esc_html( makaazi_short_money( $v ) . ( 'rent' === $status ? ' ' . __( '/mo', 'makaazi' ) : ( 'stay' === $status ? ' ' . __( '/night', 'makaazi' ) : '' ) ) ) . '</option>';
		}
		return $out;
	}

	/**
	 * Property search form (used by [makaazi_search] and the hero slider).
	 *
	 * @param array $a Options.
	 * @return string
	 */
	public static function search_form( $a = array() ) {
		$a = wp_parse_args(
			$a,
			array(
				'fields'         => 'location,type,price',
				'style'          => 'solid',
				'button_text'    => __( 'Search', 'makaazi' ),
				'buy_label'      => __( 'Buy', 'makaazi' ),
				'rent_label'     => __( 'Rent', 'makaazi' ),
				'stay_label'     => __( 'Short Stay', 'makaazi' ),
				'tabs'           => '',
				'default_status' => 'sale',
				'show_tabs'      => 'yes',
				'labels'         => array(),
				'action'         => '',
				'class'          => '',
				'id'             => '',
			)
		);
		static $n = 0;
		++$n;
		$uid    = $a['id'] ? sanitize_html_class( $a['id'] ) : 'mk-s' . $n;
		$avail  = makaazi_statuses();
		$tabs   = array();
		$want   = $a['tabs'] ? array_filter( array_map( 'trim', explode( ',', $a['tabs'] ) ) ) : array_keys( $avail );
		foreach ( $want as $k ) {
			if ( isset( $avail[ $k ] ) ) {
				$tabs[ $k ] = isset( $a[ ( 'sale' === $k ? 'buy' : $k ) . '_label' ] ) && '' !== $a[ ( 'sale' === $k ? 'buy' : $k ) . '_label' ] ? $a[ ( 'sale' === $k ? 'buy' : $k ) . '_label' ] : $avail[ $k ]['tab'];
			}
		}
		if ( ! $tabs ) {
			$tabs = array( 'sale' => $a['buy_label'] );
		}
		$status = isset( $tabs[ $a['default_status'] ] ) ? $a['default_status'] : (string) key( $tabs );
		$labels = wp_parse_args(
			(array) $a['labels'],
			array(
				'location' => __( 'Location', 'makaazi' ),
				'type'     => __( 'Property type', 'makaazi' ),
				'price'    => __( 'Price', 'makaazi' ),
				'beds'     => __( 'Bedrooms', 'makaazi' ),
				'keyword'  => __( 'Keyword or ref', 'makaazi' ),
			)
		);
		$fields = array_filter( array_map( 'trim', explode( ',', $a['fields'] ) ) );
		$action = $a['action'] ? $a['action'] : makaazi_archive_url();
		$types  = get_terms(
			array(
				'taxonomy'   => 'property_type',
				'hide_empty' => false,
			)
		);

		$html  = '<form class="mk-search mk-search--' . esc_attr( $a['style'] ) . ' ' . esc_attr( $a['class'] ) . '" method="get" action="' . esc_url( $action ) . '" role="search" data-mk-search>';
		if ( 'no' !== $a['show_tabs'] ) {
			$html .= '<div class="mk-search__tabs" role="radiogroup" aria-label="' . esc_attr__( 'Buy or rent', 'makaazi' ) . '">';
			foreach ( $tabs as $k => $label ) {
				$html .= '<label class="mk-search__tab"><input type="radio" name="status" value="' . esc_attr( $k ) . '"' . checked( $status, $k, false ) . '><span>' . esc_html( $label ) . '</span></label>';
			}
			$html .= '</div>';
		} else {
			$html .= '<input type="hidden" name="status" value="' . esc_attr( $status ) . '">';
		}
		$html .= '<div class="mk-search__fields">';
		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'location':
					$html .= '<label class="mk-field" for="' . $uid . '-loc"><span>' . esc_html( $labels['location'] ) . '</span><select id="' . $uid . '-loc" name="location">' . self::location_options() . '</select></label>';
					break;
				case 'type':
					$opts = '<option value="">' . esc_html__( 'Any type', 'makaazi' ) . '</option>';
					foreach ( (array) $types as $t ) {
						$opts .= '<option value="' . esc_attr( $t->slug ) . '">' . esc_html( $t->name ) . '</option>';
					}
					$html .= '<label class="mk-field" for="' . $uid . '-type"><span>' . esc_html( $labels['type'] ) . '</span><select id="' . $uid . '-type" name="type">' . $opts . '</select></label>';
					break;
				case 'price':
					foreach ( array_keys( $tabs ) as $st ) {
						$off   = $st !== $status;
						$html .= '<div class="mk-search__price" data-mk-price="' . $st . '"' . ( $off ? ' hidden' : '' ) . '>'
							. '<label class="mk-field" for="' . $uid . '-min-' . $st . '"><span>' . esc_html__( 'Min price', 'makaazi' ) . '</span><select id="' . $uid . '-min-' . $st . '" name="min_price"' . ( $off ? ' disabled' : '' ) . '>' . self::price_options( $st, 0, __( 'No min', 'makaazi' ) ) . '</select></label>'
							. '<label class="mk-field" for="' . $uid . '-max-' . $st . '"><span>' . esc_html__( 'Max price', 'makaazi' ) . '</span><select id="' . $uid . '-max-' . $st . '" name="max_price"' . ( $off ? ' disabled' : '' ) . '>' . self::price_options( $st, 0, __( 'No max', 'makaazi' ) ) . '</select></label></div>';
					}
					break;
				case 'beds':
					$opts = '<option value="">' . esc_html__( 'Any', 'makaazi' ) . '</option>';
					for ( $i = 1; $i <= 5; $i++ ) {
						$opts .= '<option value="' . $i . '">' . $i . '+</option>';
					}
					$html .= '<label class="mk-field" for="' . $uid . '-beds"><span>' . esc_html( $labels['beds'] ) . '</span><select id="' . $uid . '-beds" name="beds">' . $opts . '</select></label>';
					break;
				case 'keyword':
					$html .= '<label class="mk-field" for="' . $uid . '-q"><span>' . esc_html( $labels['keyword'] ) . '</span><input type="text" id="' . $uid . '-q" name="q" placeholder="' . esc_attr__( 'e.g. pool, MK-00101', 'makaazi' ) . '"></label>';
					break;
			}
		}
		$html .= '<button type="submit" class="mk-btn mk-search__go">' . makaazi_icon( 'search' ) . ' ' . esc_html( $a['button_text'] ) . '</button>';
		$html .= '</div></form>';
		Makaazi_Assets::enqueue();
		return $html;
	}

	/* ---------------------------------------------------------------------
	 * Listing page
	 * ------------------------------------------------------------------- */

	/**
	 * Normalised listing config.
	 *
	 * @param array $a Raw attributes.
	 * @return array
	 */
	public static function listing_config( $a ) {
		$a = shortcode_atts(
			array(
				'per_page'     => makaazi_settings( 'per_page' ),
				'columns'      => 3,
				'filters'      => 'sidebar',
				'views'        => 'grid,list,map',
				'default_view' => 'grid',
				'status'       => '',
				'location'     => '',
				'type'         => '',
				'use'          => '',
				'category'     => '',
				'button_text'  => '',
				'title'        => '',
			),
			(array) $a,
			'makaazi_property_listing'
		);
		$a['per_page'] = max( 1, min( 60, (int) $a['per_page'] ) );
		$a['columns']  = max( 1, min( 4, (int) $a['columns'] ) );
		$a['filters']  = in_array( $a['filters'], array( 'sidebar', 'top', 'none' ), true ) ? $a['filters'] : 'sidebar';
		$a['views']    = implode( ',', array_intersect( array_map( 'trim', explode( ',', $a['views'] ) ), array( 'grid', 'list', 'map' ) ) );
		if ( ! $a['views'] ) {
			$a['views'] = 'grid';
		}
		foreach ( array( 'status', 'location', 'type', 'use', 'category' ) as $k ) {
			$a[ $k ] = sanitize_title( $a[ $k ] );
		}
		$a['button_text'] = sanitize_text_field( $a['button_text'] );
		$a['title']       = sanitize_text_field( $a['title'] );
		return $a;
	}

	/**
	 * Preset filters from config (fixed for this listing).
	 *
	 * @param array $cfg Config.
	 * @return array
	 */
	public static function listing_preset( $cfg ) {
		$p = array();
		foreach ( array( 'status', 'location', 'use' ) as $k ) {
			if ( $cfg[ $k ] ) {
				$p[ $k ] = $cfg[ $k ];
			}
		}
		foreach ( array( 'type', 'category' ) as $k ) {
			if ( $cfg[ $k ] ) {
				$p[ $k ] = array( $cfg[ $k ] );
			}
		}
		return $p;
	}

	/**
	 * Full listing (outer wrapper + inner).
	 *
	 * @param array $cfg  Config.
	 * @param array $src  Request params.
	 * @param array $base Base URL for links.
	 * @return string
	 */
	public static function listing( $cfg, $src, $base = '' ) {
		$base = $base ? $base : self::current_url();
		$view = isset( $src['view'] ) && in_array( $src['view'], explode( ',', $cfg['views'] ), true ) ? sanitize_key( $src['view'] ) : $cfg['default_view'];
		if ( false === strpos( $cfg['views'], $view ) ) {
			$view = 'grid';
		}
		Makaazi_Assets::enqueue( false !== strpos( $cfg['views'], 'map' ) );
		return '<div class="mk-listing mk-listing--' . esc_attr( $cfg['filters'] ) . '" data-mk-listing data-config="' . esc_attr( wp_json_encode( $cfg ) ) . '" data-base="' . esc_url( $base ) . '">'
			. self::listing_inner( $cfg, $src, $view, $base ) . '</div>';
	}

	/**
	 * The current page URL without query args.
	 *
	 * @return string
	 */
	public static function current_url() {
		if ( is_post_type_archive( 'property' ) ) {
			return makaazi_archive_url();
		}
		if ( is_tax() ) {
			$link = get_term_link( get_queried_object() );
			return is_wp_error( $link ) ? home_url( '/' ) : $link;
		}
		if ( is_singular() ) {
			return get_permalink();
		}
		return home_url( add_query_arg( array(), wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/', PHP_URL_PATH ) ) );
	}

	/**
	 * Listing inner HTML (filters + results). Re-rendered by the REST endpoint.
	 *
	 * @param array  $cfg  Config.
	 * @param array  $src  Request params.
	 * @param string $view grid|list|map.
	 * @param string $base Base URL.
	 * @return string
	 */
	public static function listing_inner( $cfg, $src, $view, $base ) {
		$preset = self::listing_preset( $cfg );
		$f      = Makaazi_Query::parse( $src, $preset );
		$per    = 'map' === $view ? 300 : $cfg['per_page'];
		$q      = new WP_Query( Makaazi_Query::args( $f, $per ) );
		$total  = (int) $q->found_posts;
		$chips  = self::chips( $f, $preset, $base, $view );
		$args   = Makaazi_Query::to_args( $f );
		if ( 'grid' !== $view ) {
			$args['view'] = $view;
		}

		$html = '<div class="mk-listing__inner" data-total="' . $total . '" data-query="' . esc_attr( http_build_query( $args ) ) . '">';
		if ( 'none' !== $cfg['filters'] ) {
			$html .= '<div class="mk-filters-wrap" id="mk-filters" data-mk-drawer>'
				. '<div class="mk-drawer__head"><strong>' . esc_html__( 'Filters', 'makaazi' ) . '</strong><button type="button" class="mk-round" data-mk-drawer-close aria-label="' . esc_attr__( 'Close filters', 'makaazi' ) . '">' . makaazi_icon( 'close' ) . '</button></div>'
				. self::filters( $f, $preset, $base, $view )
				. '<div class="mk-drawer__foot"><a class="mk-btn mk-btn--ghost" href="' . esc_url( $base ) . '" data-mk-link>' . esc_html__( 'Clear', 'makaazi' ) . '</a><button type="button" class="mk-btn" data-mk-drawer-close>'
				/* translators: %d: number of results */
				. esc_html( sprintf( _n( 'Show %d result', 'Show %d results', $total, 'makaazi' ), $total ) ) . '</button></div>'
				. '</div>';
		}
		$html .= '<section class="mk-results" aria-live="polite" aria-busy="false">';
		$html .= '<div class="mk-results__bar"><h2 class="mk-results__title">' . esc_html( $cfg['title'] ? $cfg['title'] : self::results_title( $total, $f ) ) . '</h2>';
		$html .= '<div class="mk-results__ctl">';
		if ( 'none' !== $cfg['filters'] ) {
			$html .= '<button type="button" class="mk-btn mk-btn--ghost mk-btn--sm mk-filters-btn" data-mk-drawer-open aria-controls="mk-filters">' . makaazi_icon( 'filter' ) . ' ' . esc_html__( 'Filters', 'makaazi' ) . ( $chips['n'] ? ' (' . (int) $chips['n'] . ')' : '' ) . '</button>';
		}
		$html .= '<label class="mk-sort"><span class="screen-reader-text">' . esc_html__( 'Sort by', 'makaazi' ) . '</span><select name="sort" form="mk-filter-form" data-mk-sort>';
		foreach ( Makaazi_Query::sorts() as $k => $label ) {
			$html .= '<option value="' . esc_attr( $k ) . '"' . selected( $f['sort'], $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		$html .= '</select></label>';
		$views = explode( ',', $cfg['views'] );
		if ( count( $views ) > 1 ) {
			$html .= '<div class="mk-views" role="group" aria-label="' . esc_attr__( 'View', 'makaazi' ) . '">';
			foreach ( $views as $v ) {
				$va = $args;
				unset( $va['view'], $va['pg'] );
				if ( 'grid' !== $v ) {
					$va['view'] = $v;
				}
				$labels = array(
					'grid' => __( 'Grid view', 'makaazi' ),
					'list' => __( 'List view', 'makaazi' ),
					'map'  => __( 'Map view', 'makaazi' ),
				);
				$html  .= '<a href="' . esc_url( add_query_arg( $va, $base ) ) . '" data-mk-link class="' . ( $v === $view ? 'is-on' : '' ) . '" aria-label="' . esc_attr( $labels[ $v ] ) . '"' . ( $v === $view ? ' aria-current="true"' : '' ) . '>' . makaazi_icon( $v ) . '</a>';
			}
			$html .= '</div>';
		}
		$html .= '</div></div>';
		$html .= $chips['html'];

		if ( ! $q->have_posts() ) {
			$html .= '<div class="mk-empty"><h3>' . esc_html__( 'No properties match these filters', 'makaazi' ) . '</h3><p>' . esc_html__( 'Remove a filter or widen the price range.', 'makaazi' ) . '</p><a class="mk-btn mk-btn--outline mk-btn--sm" href="' . esc_url( $base ) . '" data-mk-link>' . esc_html__( 'Clear all filters', 'makaazi' ) . '</a></div>';
		} elseif ( 'map' === $view ) {
			$html .= self::map_view( $q->posts );
		} else {
			$ids   = wp_list_pluck( $q->posts, 'ID' );
			$html .= self::cards(
				$ids,
				array(
					'layout'         => $view,
					'columns'        => $cfg['columns'],
					'columns_tablet' => 2,
					'button_text'    => $cfg['button_text'],
					'eager'          => true,
				)
			);
			$html .= self::pagination( $q, $args, $base );
		}
		$html .= '</section></div>';
		wp_reset_postdata();
		return $html;
	}

	/**
	 * "12 properties to rent in Westlands".
	 *
	 * @param int   $total Total.
	 * @param array $f     Filters.
	 * @return string
	 */
	public static function results_title( $total, $f ) {
		/* translators: %s: number of properties */
		$s = sprintf( _n( '%s property', '%s properties', $total, 'makaazi' ), number_format_i18n( $total ) );
		if ( 'rent' === $f['status'] ) {
			$s .= ' ' . __( 'to rent', 'makaazi' );
		} elseif ( 'stay' === $f['status'] ) {
			$s .= ' ' . __( 'for short stays', 'makaazi' );
		} elseif ( 'sale' === $f['status'] ) {
			$s .= ' ' . __( 'for sale', 'makaazi' );
		}
		if ( $f['location'] ) {
			$t = get_term_by( 'slug', $f['location'], 'property_location' );
			if ( $t ) {
				/* translators: %s: location name */
				$s .= ' ' . sprintf( __( 'in %s', 'makaazi' ), $t->name );
			}
		}
		return $s;
	}

	/**
	 * Filters form.
	 *
	 * @param array  $f      Filters.
	 * @param array  $preset Preset filters (hidden).
	 * @param string $base   Base URL.
	 * @param string $view   Current view.
	 * @return string
	 */
	public static function filters( $f, $preset, $base, $view ) {
		$enabled = (array) makaazi_settings( 'filters' );
		$has     = function ( $k ) use ( $enabled, $preset ) {
			return in_array( $k, $enabled, true ) && ! isset( $preset[ $k ] );
		};
		$h  = '<form id="mk-filter-form" class="mk-filters" method="get" action="' . esc_url( $base ) . '" data-mk-filters>';
		if ( 'grid' !== $view ) {
			$h .= '<input type="hidden" name="view" value="' . esc_attr( $view ) . '">';
		}

		if ( $has( 'status' ) ) {
			$h .= '<fieldset class="mk-fg"><legend>' . esc_html__( 'Looking to', 'makaazi' ) . '</legend><div class="mk-seg">';
			$opts = array( '' => __( 'Any', 'makaazi' ) );
			foreach ( makaazi_statuses() as $k => $st_def ) {
				$opts[ $k ] = $st_def['tab'];
			}
			foreach ( $opts as $k => $label ) {
				$h .= '<label><input type="radio" name="status" value="' . esc_attr( $k ) . '"' . checked( $f['status'], $k, false ) . '><span>' . esc_html( $label ) . '</span></label>';
			}
			$h .= '</div></fieldset>';
		}
		if ( $has( 'location' ) ) {
			$h .= '<div class="mk-fg"><label class="mk-fg__label" for="mk-f-location">' . esc_html__( 'Location', 'makaazi' ) . '</label><select id="mk-f-location" name="location">' . self::location_options( $f['location'] ) . '</select></div>';
		}
		if ( $has( 'type' ) ) {
			$h    .= '<fieldset class="mk-fg"><legend>' . esc_html__( 'Property type', 'makaazi' ) . '</legend>';
			$types = get_terms(
				array(
					'taxonomy'   => 'property_type',
					'hide_empty' => false,
				)
			);
			foreach ( (array) $types as $t ) {
				$on    = in_array( $t->slug, $f['type'], true );
				$cnt   = Makaazi_Query::count( array_merge( $f, array( 'type' => array( $t->slug ) ) ) );
				$dis   = ! $on && ! $cnt;
				$h    .= '<label class="mk-check' . ( $dis ? ' is-disabled' : '' ) . '"><input type="checkbox" id="mk-f-type-' . esc_attr( $t->slug ) . '" name="type[]" value="' . esc_attr( $t->slug ) . '"' . checked( $on, true, false ) . disabled( $dis, true, false ) . '><span>' . esc_html( $t->name ) . '</span><em>' . (int) $cnt . '</em></label>';
			}
			$h .= '</fieldset>';
		}
		if ( $has( 'use' ) ) {
			$h .= '<fieldset class="mk-fg"><legend>' . esc_html__( 'Use', 'makaazi' ) . '</legend><div class="mk-chips">';
			$h .= '<label class="mk-chip"><input type="radio" name="use" value=""' . checked( $f['use'], '', false ) . '><span>' . esc_html__( 'Any', 'makaazi' ) . '</span></label>';
			foreach ( (array) get_terms( array( 'taxonomy' => 'property_use', 'hide_empty' => false ) ) as $t ) {
				$h .= '<label class="mk-chip"><input type="radio" name="use" value="' . esc_attr( $t->slug ) . '"' . checked( $f['use'], $t->slug, false ) . '><span>' . esc_html( $t->name ) . '</span></label>';
			}
			$h .= '</div></fieldset>';
		}
		if ( $has( 'category' ) ) {
			$cats = get_terms( array( 'taxonomy' => 'property_category', 'hide_empty' => false ) );
			if ( $cats && ! is_wp_error( $cats ) ) {
				$h .= '<fieldset class="mk-fg"><legend>' . esc_html__( 'Category', 'makaazi' ) . '</legend><div class="mk-chips">';
				foreach ( $cats as $t ) {
					$h .= '<label class="mk-chip"><input type="checkbox" name="category[]" value="' . esc_attr( $t->slug ) . '"' . checked( in_array( $t->slug, $f['category'], true ), true, false ) . '><span>' . esc_html( $t->name ) . '</span></label>';
				}
				$h .= '</div></fieldset>';
			}
		}
		if ( $has( 'price' ) ) {
			$st = $f['status'] ? $f['status'] : ( isset( $preset['status'] ) ? $preset['status'] : '' );
			$h .= '<div class="mk-fg"><span class="mk-fg__label">' . esc_html( $st && isset( makaazi_statuses( true )[ $st ] ) ? makaazi_statuses( true )[ $st ]['price_label'] : __( 'Price', 'makaazi' ) ) . '</span>';
			if ( $st ) {
				$h .= '<div class="mk-two"><label><span class="screen-reader-text">' . esc_html__( 'Minimum price', 'makaazi' ) . '</span><select id="mk-f-min" name="min_price">' . self::price_options( $st, $f['min_price'], __( 'No min', 'makaazi' ) ) . '</select></label>'
					. '<label><span class="screen-reader-text">' . esc_html__( 'Maximum price', 'makaazi' ) . '</span><select id="mk-f-max" name="max_price">' . self::price_options( $st, $f['max_price'], __( 'No max', 'makaazi' ) ) . '</select></label></div>';
			} else {
				$h .= '<p class="mk-hint">' . esc_html__( 'Choose Buy, Rent or Short Stay to filter by price. Each uses its own price range.', 'makaazi' ) . '</p>';
			}
			$h .= '</div>';
		}
		foreach ( array(
			'beds'    => array( __( 'Bedrooms', 'makaazi' ), 5 ),
			'baths'   => array( __( 'Bathrooms', 'makaazi' ), 4 ),
			'parking' => array( __( 'Parking', 'makaazi' ), 4 ),
		) as $k => $d ) {
			if ( ! $has( $k ) ) {
				continue;
			}
			$h .= '<fieldset class="mk-fg"><legend>' . esc_html( $d[0] ) . '</legend><div class="mk-chips">';
			for ( $i = 0; $i <= $d[1]; $i++ ) {
				$h .= '<label class="mk-chip"><input type="radio" name="' . esc_attr( $k ) . '" value="' . ( $i ? $i : '' ) . '"' . checked( (int) $f[ $k ], $i, false ) . '><span>' . ( $i ? $i . '+' : esc_html__( 'Any', 'makaazi' ) ) . '</span></label>';
			}
			$h .= '</div></fieldset>';
		}
		if ( $has( 'features' ) ) {
			$feats = get_terms( array( 'taxonomy' => 'property_feature', 'hide_empty' => false ) );
			if ( $feats && ! is_wp_error( $feats ) ) {
				$h .= '<fieldset class="mk-fg mk-fg--more" data-mk-more><legend>' . esc_html__( 'Features', 'makaazi' ) . '</legend>';
				foreach ( $feats as $i => $t ) {
					$on = in_array( $t->slug, $f['features'], true );
					$h .= '<label class="mk-check' . ( $i >= 6 && ! $on ? ' mk-more-item' : '' ) . '"><input type="checkbox" id="mk-f-feat-' . esc_attr( $t->slug ) . '" name="features[]" value="' . esc_attr( $t->slug ) . '"' . checked( $on, true, false ) . '><span>' . esc_html( $t->name ) . '</span></label>';
				}
				if ( count( $feats ) > 6 ) {
					/* translators: %d: number of features */
					$h .= '<button type="button" class="mk-link" data-mk-more-toggle data-less="' . esc_attr__( 'Show fewer', 'makaazi' ) . '">' . esc_html( sprintf( __( 'Show all %d', 'makaazi' ), count( $feats ) ) ) . '</button>';
				}
				$h .= '</fieldset>';
			}
		}
		if ( $has( 'keyword' ) ) {
			$h .= '<div class="mk-fg"><label class="mk-fg__label" for="mk-f-q">' . esc_html__( 'Keyword or reference', 'makaazi' ) . '</label><input type="search" id="mk-f-q" name="q" value="' . esc_attr( $f['q'] ) . '" placeholder="' . esc_attr__( 'e.g. borehole, MK-00103', 'makaazi' ) . '"></div>';
		}
		$h .= '<noscript><button type="submit" class="mk-btn">' . esc_html__( 'Apply filters', 'makaazi' ) . '</button></noscript>';
		$h .= '<a class="mk-link mk-filters__clear" href="' . esc_url( $base ) . '" data-mk-link>' . esc_html__( 'Clear all filters', 'makaazi' ) . '</a>';
		$h .= '</form>';
		return $h;
	}

	/**
	 * Active filter chips.
	 *
	 * @param array  $f      Filters.
	 * @param array  $preset Preset.
	 * @param string $base   Base URL.
	 * @param string $view   View.
	 * @return array ['html' => string, 'n' => int]
	 */
	public static function chips( $f, $preset, $base, $view ) {
		$items = array();
		$name  = function ( $tax, $slug ) {
			$t = get_term_by( 'slug', $slug, $tax );
			return $t ? $t->name : $slug;
		};
		if ( $f['status'] && ! isset( $preset['status'] ) ) {
			$items[] = array( 'status', '', makaazi_status_label( $f['status'] ) );
		}
		if ( $f['location'] && ! isset( $preset['location'] ) ) {
			$items[] = array( 'location', '', $name( 'property_location', $f['location'] ) );
		}
		foreach ( array(
			'type'     => 'property_type',
			'category' => 'property_category',
			'features' => 'property_feature',
		) as $k => $tax ) {
			if ( isset( $preset[ $k ] ) ) {
				continue;
			}
			foreach ( $f[ $k ] as $slug ) {
				$items[] = array( $k, $slug, $name( $tax, $slug ) );
			}
		}
		if ( $f['use'] && ! isset( $preset['use'] ) ) {
			$items[] = array( 'use', '', $name( 'property_use', $f['use'] ) );
		}
		if ( $f['min_price'] ) {
			/* translators: %s: price */
			$items[] = array( 'min_price', '', sprintf( __( 'From %s', 'makaazi' ), makaazi_short_money( $f['min_price'] ) ) );
		}
		if ( $f['max_price'] ) {
			/* translators: %s: price */
			$items[] = array( 'max_price', '', sprintf( __( 'Up to %s', 'makaazi' ), makaazi_short_money( $f['max_price'] ) ) );
		}
		foreach ( array(
			/* translators: %d: number */
			'beds'    => __( '%d+ beds', 'makaazi' ),
			/* translators: %d: number */
			'baths'   => __( '%d+ baths', 'makaazi' ),
			/* translators: %d: number */
			'parking' => __( '%d+ parking', 'makaazi' ),
		) as $k => $fmt ) {
			if ( $f[ $k ] ) {
				$items[] = array( $k, '', sprintf( $fmt, $f[ $k ] ) );
			}
		}
		if ( $f['q'] ) {
			$items[] = array( 'q', '', '“' . $f['q'] . '”' );
		}
		if ( ! $items ) {
			return array(
				'html' => '',
				'n'    => 0,
			);
		}
		$h = '<div class="mk-active">';
		foreach ( $items as $it ) {
			$g = $f;
			if ( $it[1] ) {
				$g[ $it[0] ] = array_values( array_diff( $g[ $it[0] ], array( $it[1] ) ) );
			} else {
				$g[ $it[0] ] = Makaazi_Query::defaults()[ $it[0] ];
				if ( 'status' === $it[0] ) {
					$g['min_price'] = 0;
					$g['max_price'] = 0;
				}
			}
			foreach ( $preset as $pk => $pv ) {
				unset( $g[ $pk ] );
			}
			$g['pg'] = 1;
			$args    = Makaazi_Query::to_args( array_merge( Makaazi_Query::defaults(), $g ) );
			if ( 'grid' !== $view ) {
				$args['view'] = $view;
			}
			/* translators: %s: filter label */
			$h .= '<a class="mk-active__chip" href="' . esc_url( add_query_arg( $args, $base ) ) . '" data-mk-link aria-label="' . esc_attr( sprintf( __( 'Remove filter: %s', 'makaazi' ), $it[2] ) ) . '">' . esc_html( $it[2] ) . makaazi_icon( 'close', 12 ) . '</a>';
		}
		$h .= '<a class="mk-link" href="' . esc_url( $base ) . '" data-mk-link>' . esc_html__( 'Clear all', 'makaazi' ) . '</a></div>';
		return array(
			'html' => $h,
			'n'    => count( $items ),
		);
	}

	/**
	 * Numbered pagination.
	 *
	 * @param WP_Query $q    Query.
	 * @param array    $args Current args.
	 * @param string   $base Base URL.
	 * @return string
	 */
	public static function pagination( $q, $args, $base ) {
		$pages = (int) $q->max_num_pages;
		if ( $pages < 2 ) {
			return '';
		}
		$cur = max( 1, (int) $q->get( 'paged' ) );
		$h   = '<nav class="mk-pages" aria-label="' . esc_attr__( 'Pages', 'makaazi' ) . '">';
		for ( $i = 1; $i <= $pages; $i++ ) {
			$a = $args;
			unset( $a['pg'] );
			if ( $i > 1 ) {
				$a['pg'] = $i;
			}
			$h .= $i === $cur
				? '<span class="is-on" aria-current="page">' . $i . '</span>'
				: '<a href="' . esc_url( add_query_arg( $a, $base ) ) . '" data-mk-link data-mk-top>' . $i . '</a>';
		}
		return $h . '</nav>';
	}

	/**
	 * Map view: Leaflet map with approximate points plus a compact list.
	 *
	 * @param WP_Post[] $posts Posts.
	 * @return string
	 */
	public static function map_view( $posts ) {
		$points = array();
		$list   = '';
		foreach ( $posts as $p ) {
			$loc   = makaazi_public_location( $p->ID );
			$ids   = makaazi_gallery_ids( $p->ID );
			$thumb = $ids ? wp_get_attachment_image_url( $ids[0], 'thumbnail' ) : makaazi_placeholder();
			if ( $loc ) {
				$points[] = array(
					'lat'   => $loc[0],
					'lng'   => $loc[1],
					'title' => get_the_title( $p ),
					'url'   => get_permalink( $p ),
					'price' => wp_strip_all_tags( makaazi_price( $p->ID, false ) ),
					'img'   => $thumb,
				);
			}
			$list .= '<a class="mk-mini" href="' . esc_url( get_permalink( $p ) ) . '"><img src="' . esc_url( $thumb ) . '" alt="" loading="lazy"><span><b>' . esc_html( get_the_title( $p ) ) . '</b><small>' . esc_html( makaazi_location_text( $p->ID ) ) . '</small><strong>' . wp_kses_post( makaazi_price( $p->ID, false ) ) . '</strong></span></a>';
		}
		return '<div class="mk-mapview"><div class="mk-map mk-map--results" data-mk-map-results="' . esc_attr( wp_json_encode( $points ) ) . '" role="region" aria-label="' . esc_attr__( 'Map of results', 'makaazi' ) . '"></div><div class="mk-mapview__list">' . $list . '</div></div>'
			. '<p class="mk-map__note">' . esc_html__( 'Markers show approximate areas, not exact addresses.', 'makaazi' ) . '</p>';
	}

	/* ---------------------------------------------------------------------
	 * Single property components
	 * ------------------------------------------------------------------- */

	/**
	 * Resolve the property ID for single components.
	 *
	 * @param array $a Attributes with optional id.
	 * @return int
	 */
	public static function pid( $a ) {
		$id = ! empty( $a['id'] ) ? absint( $a['id'] ) : get_the_ID();
		return ( $id && 'property' === get_post_type( $id ) ) ? $id : 0;
	}

	/**
	 * Header: breadcrumb, badges, title, location, price, share.
	 *
	 * @param int $id Post ID.
	 * @return string
	 */
	public static function header( $id ) {
		$loc    = makaazi_location_term( $id );
		$status = makaazi_status( $id );
		$crumb  = '<nav class="mk-crumb" aria-label="' . esc_attr__( 'Breadcrumb', 'makaazi' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'makaazi' ) . '</a><span aria-hidden="true">›</span><a href="' . esc_url( makaazi_archive_url() ) . '">' . esc_html__( 'Properties', 'makaazi' ) . '</a>';
		if ( $loc ) {
			$crumb .= '<span aria-hidden="true">›</span><a href="' . esc_url( makaazi_archive_url( array( 'location' => $loc->slug ) ) ) . '">' . esc_html( $loc->name ) . '</a>';
		}
		$crumb .= '<span aria-hidden="true">›</span><span aria-current="page">' . esc_html( get_the_title( $id ) ) . '</span></nav>';
		$label  = makaazi_meta( $id, 'location_label' );
		$closed = makaazi_meta( $id, 'availability', 'available' );
		$h      = $crumb . '<div class="mk-phead"><div class="mk-phead__main"><div class="mk-badges mk-badges--static"><span class="mk-badge mk-badge--' . esc_attr( $status ) . '">' . esc_html( makaazi_status_label( $status ) ) . '</span>'
			. ( makaazi_meta( $id, 'featured' ) ? '<span class="mk-badge mk-badge--featured">' . esc_html__( 'Featured', 'makaazi' ) . '</span>' : '' )
			. ( in_array( $closed, array( 'sold', 'let', 'under_offer' ), true ) ? '<span class="mk-badge mk-badge--closed">' . esc_html( 'sold' === $closed ? __( 'Sold', 'makaazi' ) : ( 'let' === $closed ? __( 'Let', 'makaazi' ) : __( 'Under offer', 'makaazi' ) ) ) . '</span>' : '' )
			. '</div><h1 class="mk-phead__title">' . esc_html( get_the_title( $id ) ) . '</h1><div class="mk-phead__sub">'
			. ( $label || $loc ? '<span>' . makaazi_icon( 'pin', 14 ) . ' ' . esc_html( $label ? $label : makaazi_location_text( $id ) ) . '</span>' : '' )
			/* translators: %s: reference number */
			. '<span>' . esc_html( sprintf( __( 'Ref %s', 'makaazi' ), makaazi_meta( $id, 'ref' ) ) ) . '</span></div></div>'
			. '<div class="mk-phead__side"><div class="mk-phead__price">' . makaazi_price( $id ) . '</div>'
			. '<div class="mk-phead__acts">'
			. ( makaazi_settings( 'card_favourite' ) ? '<button type="button" class="mk-btn mk-btn--ghost mk-btn--sm mk-act" data-mk-fav="' . (int) $id . '" aria-pressed="false">' . makaazi_icon( 'heart', 15 ) . ' <span data-on="' . esc_attr__( 'Saved', 'makaazi' ) . '" data-off="' . esc_attr__( 'Save', 'makaazi' ) . '">' . esc_html__( 'Save', 'makaazi' ) . '</span></button>' : '' )
			. ( makaazi_settings( 'card_compare' ) ? '<button type="button" class="mk-btn mk-btn--ghost mk-btn--sm mk-act" data-mk-cmp="' . (int) $id . '" aria-pressed="false">' . makaazi_icon( 'compare', 15 ) . ' ' . esc_html__( 'Compare', 'makaazi' ) . '</button>' : '' )
			. '<button type="button" class="mk-btn mk-btn--ghost mk-btn--sm" data-mk-share data-url="' . esc_url( get_permalink( $id ) ) . '" data-title="' . esc_attr( get_the_title( $id ) ) . '">' . makaazi_icon( 'share', 15 ) . ' ' . esc_html__( 'Share', 'makaazi' ) . '</button></div></div></div>';
		Makaazi_Assets::enqueue();
		return '<div class="mk-single mk-single--header">' . $h . '</div>';
	}

	/**
	 * Gallery data for the lightbox.
	 *
	 * @param int $id Post ID.
	 * @return array
	 */
	public static function gallery_items( $id ) {
		$out = array();
		foreach ( makaazi_gallery_ids( $id ) as $aid ) {
			$full = wp_get_attachment_image_src( $aid, 'full' );
			if ( ! $full ) {
				continue;
			}
			$out[] = array(
				'src'     => $full[0],
				'w'       => $full[1],
				'h'       => $full[2],
				'thumb'   => wp_get_attachment_image_url( $aid, 'thumbnail' ),
				'caption' => wp_get_attachment_caption( $aid ),
				'alt'     => get_post_meta( $aid, '_wp_attachment_image_alt', true ),
			);
		}
		return $out;
	}

	/**
	 * Gallery mosaic + mobile strip; opens the lightbox.
	 *
	 * @param int $id Post ID.
	 * @return string
	 */
	public static function gallery( $id ) {
		$ids   = makaazi_gallery_ids( $id );
		$items = self::gallery_items( $id );
		if ( ! $ids ) {
			return '';
		}
		$n     = count( $ids );
		$title = get_the_title( $id );
		$h     = '<div class="mk-gallery" data-mk-gallery="' . esc_attr( wp_json_encode( $items ) ) . '" data-title="' . esc_attr( $title ) . '">';
		$h    .= '<div class="mk-mosaic mk-mosaic--' . min( 5, $n ) . '">';
		foreach ( array_slice( $ids, 0, 5 ) as $i => $aid ) {
			/* translators: 1: photo number, 2: total */
			$h .= '<button type="button" class="mk-mosaic__item" data-mk-open="' . $i . '" aria-label="' . esc_attr( sprintf( __( 'Open photo %1$d of %2$d', 'makaazi' ), $i + 1, $n ) ) . '">'
				. wp_get_attachment_image( $aid, 0 === $i ? 'makaazi-wide' : 'makaazi-card', false, array( 'loading' => $i ? 'lazy' : 'eager', 'sizes' => 0 === $i ? '(max-width: 900px) 100vw, 800px' : '400px' ) ) . '</button>';
		}
		/* translators: %d: number of photos */
		$h .= '<button type="button" class="mk-mosaic__all" data-mk-open="0">' . makaazi_icon( 'photo', 15 ) . ' ' . esc_html( sprintf( _n( 'View %d photo', 'View all %d photos', $n, 'makaazi' ), $n ) ) . '</button></div>';
		$h .= '<div class="mk-gstrip"><div class="mk-strip" data-mk-strip data-mk-counter>';
		foreach ( $ids as $i => $aid ) {
			$h .= '<button type="button" class="mk-strip__slide" data-mk-open="' . $i . '" aria-label="' . esc_attr( sprintf( __( 'Open photo %1$d of %2$d', 'makaazi' ), $i + 1, $n ) ) . '">' . wp_get_attachment_image( $aid, 'makaazi-card', false, array( 'loading' => $i ? 'lazy' : 'eager' ) ) . '</button>';
		}
		$h .= '</div><span class="mk-gstrip__count" aria-hidden="true">1 / ' . $n . '</span></div></div>';
		Makaazi_Assets::enqueue();
		return '<div class="mk-single">' . $h . '</div>';
	}

	/**
	 * Key facts bar.
	 *
	 * @param int $id Post ID.
	 * @return string
	 */
	public static function facts( $id ) {
		$type  = makaazi_term( $id, 'property_type' );
		$uses  = makaazi_term_names( $id, 'property_use' );
		$facts = array();
		foreach ( array(
			'bedrooms'  => array( __( 'Bedrooms', 'makaazi' ), 'bed' ),
			'bathrooms' => array( __( 'Bathrooms', 'makaazi' ), 'bath' ),
			'parking'   => array( __( 'Parking', 'makaazi' ), 'car' ),
		) as $k => $d ) {
			$v = (float) makaazi_meta( $id, $k, 0 );
			if ( $v ) {
				$facts[] = array( $d[0], makaazi_icon( $d[1] ) . esc_html( 0 + $v ) );
			}
		}
		$area = (float) makaazi_meta( $id, 'area_sqm', 0 );
		if ( $type && 'land' === $type->slug && makaazi_land_text( $id ) ) {
			$facts[] = array( __( 'Land size', 'makaazi' ), makaazi_icon( 'area' ) . esc_html( makaazi_land_text( $id ) ) );
		} elseif ( $area ) {
			$facts[] = array( __( 'Area', 'makaazi' ), makaazi_icon( 'area' ) . makaazi_area_html( $area ) . ( makaazi_settings( 'unit_toggle' ) ? ' <button type="button" class="mk-unit-mini" data-mk-unit-toggle aria-label="' . esc_attr__( 'Switch area unit', 'makaazi' ) . '">⇄</button>' : '' ) );
		}
		if ( $type ) {
			$facts[] = array( __( 'Type', 'makaazi' ), esc_html( $type->name ) );
		}
		if ( $uses ) {
			$facts[] = array( __( 'Use', 'makaazi' ), esc_html( implode( ', ', $uses ) ) );
		}
		$h = '<dl class="mk-facts" style="--mk-n:' . count( $facts ) . '">';
		foreach ( $facts as $f ) {
			$h .= '<div><dt>' . esc_html( $f[0] ) . '</dt><dd>' . $f[1] . '</dd></div>';
		}
		return '<div class="mk-single">' . $h . '</dl></div>';
	}

	/**
	 * Details table.
	 *
	 * @param int $id Post ID.
	 * @return string
	 */
	public static function details( $id, $title = '' ) {
		$type   = makaazi_term( $id, 'property_type' );
		$rows   = array(
			array( __( 'Reference', 'makaazi' ), makaazi_meta( $id, 'ref' ) ),
			array( __( 'Status', 'makaazi' ), makaazi_status_label( makaazi_status( $id ) ) ),
			array( __( 'Category', 'makaazi' ), implode( ', ', makaazi_term_names( $id, 'property_category' ) ) ),
			array( __( 'Type', 'makaazi' ), $type ? $type->name : '' ),
			array( __( 'Use', 'makaazi' ), implode( ', ', makaazi_term_names( $id, 'property_use' ) ) ),
			array( __( 'Price', 'makaazi' ), wp_strip_all_tags( makaazi_price( $id, false ) ) ),
		);
		$area   = (float) makaazi_meta( $id, 'area_sqm', 0 );
		$h_rows = '';
		foreach ( $rows as $r ) {
			if ( '' !== (string) $r[1] ) {
				$h_rows .= '<div><dt>' . esc_html( $r[0] ) . '</dt><dd>' . esc_html( $r[1] ) . '</dd></div>';
			}
		}
		if ( $area && ! ( $type && 'land' === $type->slug ) ) {
			$h_rows .= '<div><dt>' . esc_html__( 'Area', 'makaazi' ) . '</dt><dd>' . makaazi_area_html( $area ) . '</dd></div>';
		}
		if ( makaazi_land_text( $id ) ) {
			$h_rows .= '<div><dt>' . esc_html__( 'Land size', 'makaazi' ) . '</dt><dd>' . esc_html( makaazi_land_text( $id ) ) . '</dd></div>';
		}
		$year = (int) makaazi_meta( $id, 'year_built', 0 );
		if ( $year ) {
			$h_rows .= '<div><dt>' . esc_html( $year > (int) gmdate( 'Y' ) ? __( 'Completion', 'makaazi' ) : __( 'Year built', 'makaazi' ) ) . '</dt><dd>' . (int) $year . '</dd></div>';
		}
		foreach ( (array) makaazi_meta( $id, 'attributes', array() ) as $attr ) {
			if ( is_array( $attr ) && ! empty( $attr[0] ) ) {
				$h_rows .= '<div><dt>' . esc_html( $attr[0] ) . '</dt><dd>' . esc_html( isset( $attr[1] ) ? $attr[1] : '' ) . '</dd></div>';
			}
		}
		$video = makaazi_meta( $id, 'video_url' );
		if ( $video ) {
			$h_rows .= '<div><dt>' . esc_html__( 'Video tour', 'makaazi' ) . '</dt><dd><a href="' . esc_url( $video ) . '" target="_blank" rel="noopener">' . esc_html__( 'Watch the video', 'makaazi' ) . '</a></dd></div>';
		}
		$title = $title ? $title : __( 'Details', 'makaazi' );
		return '<section class="mk-single mk-sec"><h2 class="mk-sec__title">' . esc_html( $title ) . '</h2><dl class="mk-details">' . $h_rows . '</dl></section>';
	}

	/**
	 * Features list.
	 *
	 * @param int    $id    Post ID.
	 * @param string $title Heading.
	 * @return string
	 */
	public static function features( $id, $title = '' ) {
		$terms = get_the_terms( $id, 'property_feature' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}
		$title = $title ? $title : __( 'Features', 'makaazi' );
		$h     = '<section class="mk-single mk-sec" data-mk-more><h2 class="mk-sec__title">' . esc_html( $title ) . '</h2><ul class="mk-features">';
		foreach ( $terms as $i => $t ) {
			$h .= '<li class="' . ( $i >= 9 ? 'mk-more-item' : '' ) . '">' . makaazi_icon( 'check', 17 ) . esc_html( $t->name ) . '</li>';
		}
		$h .= '</ul>';
		if ( count( $terms ) > 9 ) {
			/* translators: %d: number of features */
			$h .= '<button type="button" class="mk-link" data-mk-more-toggle data-less="' . esc_attr__( 'Show fewer', 'makaazi' ) . '">' . esc_html( sprintf( __( 'Show all %d features', 'makaazi' ), count( $terms ) ) ) . '</button>';
		}
		return $h . '</section>';
	}

	/**
	 * Approximate location map.
	 *
	 * @param int    $id    Post ID.
	 * @param string $title Heading.
	 * @return string
	 */
	public static function map( $id, $title = '' ) {
		$title = $title ? $title : __( 'Location', 'makaazi' );
		$loc   = makaazi_public_location( $id );
		$label = makaazi_meta( $id, 'location_label' );
		$text  = makaazi_location_text( $id );
		$h     = '<section class="mk-single mk-sec"><h2 class="mk-sec__title">' . esc_html( $title ) . '</h2><p class="mk-map__place"><strong>' . esc_html( $text ) . '</strong>' . ( $label ? ' · ' . esc_html( $label ) : '' ) . '</p>';
		if ( $loc ) {
			Makaazi_Assets::enqueue( true );
			$h .= '<div class="mk-map" data-mk-map data-lat="' . esc_attr( $loc[0] ) . '" data-lng="' . esc_attr( $loc[1] ) . '" data-radius="' . esc_attr( $loc[2] ) . '" data-exact="' . ( $loc[3] ? '1' : '0' ) . '" role="region" aria-label="' . esc_attr( sprintf( /* translators: %s: area */ __( 'Map of the area around %s', 'makaazi' ), $text ) ) . '"></div>';
			$h .= '<p class="mk-map__note">' . esc_html( $loc[3] ? __( 'Exact location shown.', 'makaazi' ) : sprintf( /* translators: %d: metres */ __( 'Approximate area (about %d m). The exact address is shared when you book a viewing.', 'makaazi' ), $loc[2] ) ) . '</p>';
		}
		return $h . '</section>';
	}

	/**
	 * Contact card + enquiry form + mobile action bar.
	 *
	 * @param int $id Post ID.
	 * @return string
	 */
	public static function contact( $id ) {
		$s     = makaazi_settings();
		$title = get_the_title( $id );
		$ref   = makaazi_meta( $id, 'ref' );
		/* translators: 1: property title, 2: reference, 3: URL */
		$msg   = sprintf( __( 'Hi, I\'m interested in %1$s (%2$s). %3$s', 'makaazi' ), $title, $ref, get_permalink( $id ) );
		$aid   = Makaazi_Agents::of_property( $id );
		$h     = '<div class="mk-single mk-contact" id="mk-enquire">';
		if ( $aid ) {
			$h  .= Makaazi_Agents::card( $aid, $msg );
			$wan = (string) get_post_meta( $aid, '_mk_whatsapp', true );
			$wa  = $wan ? makaazi_whatsapp_url( $wan, $msg ) : '';
		} else {
			$wa   = $s['whatsapp'] ? makaazi_whatsapp_url( $s['whatsapp'], $msg ) : '';
			$tel  = preg_replace( '/[^\d+]/', '', $s['contact_phone'] );
			$h   .= '<div class="mk-box mk-agent"><div class="mk-agent__who"><span class="mk-agent__avatar" aria-hidden="true">' . esc_html( mb_substr( $s['contact_name'], 0, 1 ) ) . '</span><div><strong>' . esc_html( $s['contact_name'] ) . '</strong><span>' . esc_html__( 'Listing contact', 'makaazi' ) . '</span></div></div>';
			$h   .= '<div class="mk-row2">' . ( $wa ? '<a class="mk-btn mk-btn--wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">' . makaazi_icon( 'wa' ) . ' WhatsApp</a>' : '' )
				. ( $tel ? '<a class="mk-btn mk-btn--outline" href="tel:' . esc_attr( $tel ) . '">' . makaazi_icon( 'phone' ) . ' ' . esc_html__( 'Call', 'makaazi' ) . '</a>' : '' ) . '</div>';
			$h   .= $s['contact_phone'] ? '<p class="mk-agent__num">' . makaazi_icon( 'phone', 14 ) . ' ' . esc_html( $s['contact_phone'] ) . '</p>' : '';
			$h   .= '</div>';
		}
		/* translators: 1: property title, 2: reference */
		$h .= self::enquiry_form( $id, $aid, sprintf( __( 'Hi, I\'m interested in %1$s (%2$s). Is it available for a viewing this week?', 'makaazi' ), $title, $ref ) );
		$h .= '<div class="mk-mbar">' . ( $wa ? '<a class="mk-btn mk-btn--wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">' . makaazi_icon( 'wa' ) . ' WhatsApp</a>' : '' ) . '<a class="mk-btn mk-btn--outline" href="#mk-enquire">' . esc_html__( 'Enquire', 'makaazi' ) . '</a></div>';
		Makaazi_Assets::enqueue();
		return $h . '</div>';
	}

	/**
	 * Enquiry form for a property or an agent.
	 *
	 * @param int    $pid     Property ID (0 for an agent enquiry).
	 * @param int    $aid     Agent ID.
	 * @param string $default Default message.
	 * @param string $title   Form heading.
	 * @return string
	 */
	public static function enquiry_form( $pid, $aid, $default, $title = '' ) {
		static $n = 0;
		++$n;
		$u     = 'mk-e' . $n;
		$title = $title ? $title : __( 'Ask about this property', 'makaazi' );
		return '<form class="mk-box mk-enquiry" data-mk-enquiry novalidate><h2 class="mk-enquiry__title">' . esc_html( $title ) . '</h2>'
			. '<input type="hidden" name="property_id" value="' . (int) $pid . '">'
			. '<input type="hidden" name="agent_id" value="' . (int) $aid . '">'
			. '<input type="hidden" name="ts" value="' . esc_attr( time() ) . '">'
			. '<div class="mk-hp" aria-hidden="true"><label>' . esc_html__( 'Leave this empty', 'makaazi' ) . '<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
			. '<label class="mk-field" for="' . $u . '-name"><span>' . esc_html__( 'Your name', 'makaazi' ) . '</span><input id="' . $u . '-name" type="text" name="name" required autocomplete="name"></label>'
			. '<label class="mk-field" for="' . $u . '-phone"><span>' . esc_html__( 'Phone', 'makaazi' ) . '</span><input id="' . $u . '-phone" type="tel" name="phone" required autocomplete="tel" placeholder="07XX XXX XXX"></label>'
			. '<label class="mk-field" for="' . $u . '-email"><span>' . esc_html__( 'Email (optional)', 'makaazi' ) . '</span><input id="' . $u . '-email" type="email" name="email" autocomplete="email"></label>'
			. '<label class="mk-field" for="' . $u . '-msg"><span>' . esc_html__( 'Message', 'makaazi' ) . '</span><textarea id="' . $u . '-msg" name="message" rows="4">' . esc_textarea( $default ) . '</textarea></label>'
			. '<p class="mk-enquiry__status" role="status" aria-live="polite"></p>'
			. '<button type="submit" class="mk-btn mk-btn--block">' . esc_html__( 'Send enquiry', 'makaazi' ) . '</button></form>';
	}

	/**
	 * Related properties carousel.
	 *
	 * @param int   $id Post ID.
	 * @param array $a  Options: count, title, eyebrow, layout, columns.
	 * @return string
	 */
	public static function related( $id, $a = array() ) {
		$a   = wp_parse_args(
			$a,
			array(
				'count'   => makaazi_settings( 'related_count' ),
				'title'   => __( 'Similar properties', 'makaazi' ),
				'eyebrow' => __( 'You may also like', 'makaazi' ),
				'layout'  => 'carousel',
				'columns' => 4,
			)
		);
		$rel = Makaazi_Related::get( $id, max( 1, (int) $a['count'] ) );
		if ( ! $rel ) {
			return '';
		}
		$ids = wp_list_pluck( $rel, 'id' );
		$why = array();
		foreach ( $rel as $r ) {
			$why[ $r['id'] ] = $r['why'];
		}
		$loc  = makaazi_location_term( $id );
		$args = array( 'status' => makaazi_status( $id ) );
		if ( $loc ) {
			$args['location'] = $loc->parent ? get_term( $loc->parent )->slug : $loc->slug;
		}
		return '<section class="mk-single mk-related">' . self::section_head( $a['eyebrow'], $a['title'], makaazi_archive_url( $args ), __( 'View all similar', 'makaazi' ) )
			. self::cards(
				$ids,
				array(
					'layout'  => $a['layout'],
					'columns' => (int) $a['columns'],
					'why_map' => $why,
				)
			) . '</section>';
	}

	/**
	 * Section heading with optional "view all" link.
	 *
	 * @param string $eyebrow Small label.
	 * @param string $title   Heading.
	 * @param string $link    URL.
	 * @param string $text    Link text.
	 * @param string $sub     Subtitle.
	 * @return string
	 */
	public static function section_head( $eyebrow, $title, $link = '', $text = '', $sub = '' ) {
		if ( ! $eyebrow && ! $title && ! $link ) {
			return '';
		}
		return '<div class="mk-sechead"><div>' . ( $eyebrow ? '<p class="mk-eyebrow">' . esc_html( $eyebrow ) . '</p>' : '' ) . ( $title ? '<h2 class="mk-sechead__title">' . esc_html( $title ) . '</h2>' : '' ) . ( $sub ? '<p class="mk-sechead__sub">' . esc_html( $sub ) . '</p>' : '' ) . '</div>'
			. ( $link ? '<a class="mk-viewall" href="' . esc_url( $link ) . '">' . esc_html( $text ? $text : __( 'View all', 'makaazi' ) ) . makaazi_icon( 'arrow', 15 ) . '</a>' : '' ) . '</div>';
	}
}
