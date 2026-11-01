<?php
/**
 * Shortcodes.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers every [makaazi_*] shortcode.
 */
class Makaazi_Shortcodes {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'render_block_core/shortcode', array( __CLASS__, 'render_shortcode_block' ), 10, 1 );
	}

	/**
	 * Block themes run shortcodes in a template before patterns and template parts are expanded,
	 * so a Shortcode block inside a pattern would print as text. Render it here instead.
	 *
	 * @param string $html Block output.
	 * @return string
	 */
	public static function render_shortcode_block( $html ) {
		if ( false === strpos( $html, '[' ) ) {
			return $html;
		}
		return do_shortcode( shortcode_unautop( $html ) );
	}

	/**
	 * Register shortcodes.
	 */
	public static function register() {
		$map = array(
			'makaazi_properties'           => 'properties',
			'makaazi_property_listing'     => 'listing',
			'makaazi_search'               => 'search',
			'makaazi_locations'            => 'locations',
			'makaazi_unit_toggle'          => 'unit_toggle',
			'makaazi_property'             => 'property',
			'makaazi_property_header'      => 'property_header',
			'makaazi_property_gallery'     => 'property_gallery',
			'makaazi_property_facts'       => 'property_facts',
			'makaazi_property_description' => 'property_description',
			'makaazi_property_details'     => 'property_details',
			'makaazi_property_features'    => 'property_features',
			'makaazi_property_map'         => 'property_map',
			'makaazi_property_contact'     => 'property_contact',
			'makaazi_related_properties'   => 'related',
		);
		foreach ( $map as $tag => $method ) {
			add_shortcode( $tag, array( __CLASS__, $method ) );
		}
	}

	/**
	 * Default attributes for [makaazi_properties]; shared with the load-more endpoint.
	 *
	 * @return array
	 */
	public static function properties_defaults() {
		return array(
			'source'         => 'all',
			'status'         => '',
			'location'       => '',
			'type'           => '',
			'category'       => '',
			'use'            => '',
			'ids'            => '',
			'count'          => 6,
			'columns'        => 3,
			'columns_tablet' => 2,
			'columns_mobile' => 1,
			'layout'         => 'grid',
			'orderby'        => 'newest',
			'pagination'     => 'none',
			'eyebrow'        => '',
			'title'          => '',
			'subtitle'       => '',
			'view_all'       => 'yes',
			'view_all_text'  => '',
			'button_text'    => '',
			'image_ratio'    => '',
			'show'           => '',
			'hide'           => '',
			'include_closed' => 'no',
			'class'          => '',
			'empty_text'     => '',
		);
	}

	/**
	 * Build the WP_Query args and filters for [makaazi_properties].
	 *
	 * @param array $a    Attributes.
	 * @param int   $page Page number.
	 * @return array [args, filters for the view-all link]
	 */
	public static function properties_query( $a, $page = 1 ) {
		$f = Makaazi_Query::defaults();
		switch ( $a['source'] ) {
			case 'sale':
			case 'buy':
				$f['status'] = 'sale';
				break;
			case 'rent':
				$f['status'] = 'rent';
				break;
		}
		if ( $a['status'] ) {
			$f['status'] = in_array( $a['status'], array( 'sale', 'rent' ), true ) ? $a['status'] : $f['status'];
		}
		foreach ( array( 'location', 'use' ) as $k ) {
			if ( $a[ $k ] ) {
				$f[ $k ] = sanitize_title( $a[ $k ] );
			}
		}
		foreach ( array( 'type', 'category' ) as $k ) {
			if ( $a[ $k ] ) {
				$f[ $k ] = array_filter( array_map( 'sanitize_title', explode( ',', $a[ $k ] ) ) );
			}
		}
		$sort_map  = array(
			'newest'     => 'newest',
			'price_asc'  => 'price_asc',
			'price_desc' => 'price_desc',
			'featured'   => 'featured',
			'largest'    => 'largest',
			'random'     => 'random',
		);
		$f['sort'] = isset( $sort_map[ $a['orderby'] ] ) ? $sort_map[ $a['orderby'] ] : 'newest';
		$f['pg']   = max( 1, (int) $page );
		$args      = Makaazi_Query::args( $f, max( 1, min( 48, (int) $a['count'] ) ) );
		if ( 'yes' === $a['include_closed'] ) {
			unset( $args['meta_query']['avail'] );
		}
		if ( 'featured' === $a['source'] ) {
			$args['meta_query'][] = array(
				'key'   => '_mk_featured',
				'value' => '1',
			);
		}
		if ( 'ids' === $a['source'] || $a['ids'] ) {
			$ids                    = array_filter( array_map( 'absint', explode( ',', $a['ids'] ) ) );
			$args['post__in']       = $ids ? $ids : array( 0 );
			$args['posts_per_page'] = count( $args['post__in'] );
			if ( 'newest' === $a['orderby'] ) {
				$args['orderby'] = 'post__in';
			}
		}
		if ( 'random' === $f['sort'] ) {
			$args['orderby'] = 'rand';
		}
		return array( $args, $f );
	}

	/**
	 * [makaazi_properties] grid, carousel or list.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function properties( $atts ) {
		$a = shortcode_atts( self::properties_defaults(), $atts, 'makaazi_properties' );
		static $n = 0;
		++$n;
		$page          = ( 'numbered' === $a['pagination'] && isset( $_GET[ 'mkp' . $n ] ) ) ? absint( $_GET[ 'mkp' . $n ] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		list( $args, $f ) = self::properties_query( $a, $page );
		$q             = new WP_Query( $args );
		Makaazi_Assets::enqueue();

		$view_all = '';
		if ( 'yes' === $a['view_all'] && 'ids' !== $a['source'] ) {
			$va = Makaazi_Query::to_args( $f );
			unset( $va['pg'] );
			if ( 'featured' === $a['source'] ) {
				$va['sort'] = 'featured';
			}
			$view_all = makaazi_archive_url( $va );
		}
		$h = '<div class="mk-block mk-properties ' . esc_attr( $a['class'] ) . '">' . Makaazi_Render::section_head( $a['eyebrow'], $a['title'], $view_all, $a['view_all_text'], $a['subtitle'] );
		if ( ! $q->have_posts() ) {
			wp_reset_postdata();
			return $h . '<p class="mk-empty-line">' . esc_html( $a['empty_text'] ? $a['empty_text'] : __( 'No properties to show yet.', 'makaazi' ) ) . '</p></div>';
		}
		$opts = array(
			'layout'         => in_array( $a['layout'], array( 'grid', 'carousel', 'list' ), true ) ? $a['layout'] : 'grid',
			'columns'        => (int) $a['columns'],
			'columns_tablet' => (int) $a['columns_tablet'],
			'columns_mobile' => (int) $a['columns_mobile'],
			'button_text'    => $a['button_text'],
			'image_ratio'    => $a['image_ratio'],
			'show'           => $a['show'],
			'hide'           => $a['hide'],
		);
		$h   .= Makaazi_Render::cards( wp_list_pluck( $q->posts, 'ID' ), $opts );
		if ( 'loadmore' === $a['pagination'] && $q->max_num_pages > 1 ) {
			$h .= '<div class="mk-more"><button type="button" class="mk-btn mk-btn--outline" data-mk-loadmore data-page="1" data-max="' . (int) $q->max_num_pages . '" data-atts="' . esc_attr( wp_json_encode( $a ) ) . '">' . esc_html__( 'Load more', 'makaazi' ) . '</button></div>';
		} elseif ( 'numbered' === $a['pagination'] && $q->max_num_pages > 1 ) {
			$h .= '<nav class="mk-pages" aria-label="' . esc_attr__( 'Pages', 'makaazi' ) . '">';
			for ( $i = 1; $i <= $q->max_num_pages; $i++ ) {
				$h .= $i === $page ? '<span class="is-on" aria-current="page">' . $i . '</span>' : '<a href="' . esc_url( add_query_arg( 'mkp' . $n, $i ) ) . '">' . $i . '</a>';
			}
			$h .= '</nav>';
		}
		wp_reset_postdata();
		return $h . '</div>';
	}

	/**
	 * [makaazi_property_listing] — the Properties page with filters.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function listing( $atts ) {
		$atts = (array) $atts;
		// On taxonomy archives, preset the term.
		if ( is_tax( array( 'property_location', 'property_type', 'property_category', 'property_use', 'property_status' ) ) ) {
			$term = get_queried_object();
			$map  = array(
				'property_location' => 'location',
				'property_type'     => 'type',
				'property_category' => 'category',
				'property_use'      => 'use',
				'property_status'   => 'status',
			);
			if ( $term && isset( $map[ $term->taxonomy ] ) && empty( $atts[ $map[ $term->taxonomy ] ] ) ) {
				$atts[ $map[ $term->taxonomy ] ] = $term->slug;
				if ( empty( $atts['title'] ) && 'property_status' !== $term->taxonomy ) {
					/* translators: %s: term name */
					$atts['title'] = sprintf( __( 'Properties: %s', 'makaazi' ), $term->name );
				}
			}
		}
		$cfg = Makaazi_Render::listing_config( $atts );
		return Makaazi_Render::listing( $cfg, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * [makaazi_search].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function search( $atts ) {
		$a = shortcode_atts(
			array(
				'fields'         => 'location,type,price',
				'style'          => 'solid',
				'button_text'    => __( 'Search', 'makaazi' ),
				'buy_label'      => __( 'Buy', 'makaazi' ),
				'rent_label'     => __( 'Rent', 'makaazi' ),
				'default_status' => 'sale',
				'show_tabs'      => 'yes',
				'location_label' => __( 'Location', 'makaazi' ),
				'type_label'     => __( 'Property type', 'makaazi' ),
				'class'          => '',
			),
			$atts,
			'makaazi_search'
		);
		$a['labels'] = array(
			'location' => $a['location_label'],
			'type'     => $a['type_label'],
		);
		return Makaazi_Render::search_form( $a );
	}

	/**
	 * [makaazi_locations] tiles or list.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function locations( $atts ) {
		$a = shortcode_atts(
			array(
				'locations' => '',
				'parent'    => '',
				'count'     => 6,
				'columns'   => 3,
				'layout'    => 'tiles',
				'eyebrow'   => '',
				'title'     => '',
				'status'    => '',
			),
			$atts,
			'makaazi_locations'
		);
		$args = array(
			'taxonomy'   => 'property_location',
			'hide_empty' => true,
		);
		if ( $a['locations'] ) {
			$args['slug']    = array_map( 'sanitize_title', explode( ',', $a['locations'] ) );
			$args['orderby'] = 'slug__in';
		} else {
			$args['orderby'] = 'count';
			$args['order']   = 'DESC';
			$args['number']  = max( 1, (int) $a['count'] );
			if ( $a['parent'] ) {
				$p              = get_term_by( 'slug', sanitize_title( $a['parent'] ), 'property_location' );
				$args['parent'] = $p ? $p->term_id : 0;
			} else {
				$args['childless'] = true;
			}
		}
		$terms = get_terms( $args );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}
		Makaazi_Assets::enqueue();
		$link = function ( $t ) use ( $a ) {
			$q = array( 'location' => $t->slug );
			if ( in_array( $a['status'], array( 'sale', 'rent' ), true ) ) {
				$q['status'] = $a['status'];
			}
			return makaazi_archive_url( $q );
		};
		if ( 'list' === $a['layout'] ) {
			$h = '<ul class="mk-loclist">';
			foreach ( $terms as $t ) {
				$h .= '<li><a href="' . esc_url( $link( $t ) ) . '">' . esc_html( $t->name ) . '</a> <span>(' . (int) $t->count . ')</span></li>';
			}
			return $h . '</ul>';
		}
		$h = '<div class="mk-block">' . Makaazi_Render::section_head( $a['eyebrow'], $a['title'] ) . '<div class="mk-locs" style="--mk-cols:' . max( 1, min( 6, (int) $a['columns'] ) ) . '">';
		foreach ( $terms as $t ) {
			$img_id = (int) get_term_meta( $t->term_id, 'mk_image', true );
			if ( ! $img_id ) {
				$p = get_posts(
					array(
						'post_type'      => 'property',
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'tax_query'      => array( array( 'taxonomy' => 'property_location', 'terms' => $t->term_id ) ), // phpcs:ignore
					)
				);
				$g      = $p ? makaazi_gallery_ids( $p[0] ) : array();
				$img_id = $g ? $g[0] : 0;
			}
			$img = $img_id ? wp_get_attachment_image( $img_id, 'makaazi-card', false, array( 'loading' => 'lazy', 'alt' => '' ) ) : '';
			/* translators: %d: number of properties */
			$h .= '<a class="mk-loc" href="' . esc_url( $link( $t ) ) . '">' . $img . '<span><b>' . esc_html( $t->name ) . '</b><small>' . esc_html( sprintf( _n( '%d property', '%d properties', $t->count, 'makaazi' ), $t->count ) ) . '</small></span></a>';
		}
		return $h . '</div></div>';
	}

	/**
	 * [makaazi_unit_toggle].
	 *
	 * @return string
	 */
	public static function unit_toggle() {
		if ( ! makaazi_settings( 'unit_toggle' ) ) {
			return '';
		}
		Makaazi_Assets::enqueue();
		$u = makaazi_unit();
		return '<button type="button" class="mk-unit" data-mk-unit-toggle aria-label="' . esc_attr__( 'Switch area unit between square metres and square feet', 'makaazi' ) . '"><span class="' . ( 'sqm' === $u ? 'is-on' : '' ) . '" data-u="sqm">m²</span><span aria-hidden="true">|</span><span class="' . ( 'sqft' === $u ? 'is-on' : '' ) . '" data-u="sqft">ft²</span></button>';
	}

	/**
	 * [makaazi_property] full single layout (for classic themes and page builders).
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property( $atts ) {
		$id = Makaazi_Render::pid( (array) $atts );
		if ( ! $id ) {
			return '';
		}
		$desc = apply_filters( 'makaazi_property_description', wpautop( get_post_field( 'post_content', $id ) ), $id );
		return '<div class="mk-property">' . Makaazi_Render::header( $id ) . Makaazi_Render::gallery( $id ) . Makaazi_Render::facts( $id )
			. '<div class="mk-pbody"><div class="mk-pbody__main"><section class="mk-single mk-sec"><h2 class="mk-sec__title">' . esc_html__( 'About this property', 'makaazi' ) . '</h2><div class="mk-desc">' . wp_kses_post( $desc ) . '</div></section>'
			. Makaazi_Render::details( $id ) . Makaazi_Render::features( $id ) . Makaazi_Render::map( $id ) . '</div><aside class="mk-pbody__side">' . Makaazi_Render::contact( $id ) . '</aside></div>'
			. Makaazi_Render::related( $id ) . '</div>';
	}

	/**
	 * Single-component shortcodes.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_header( $atts ) {
		$id = Makaazi_Render::pid( (array) $atts );
		return $id ? Makaazi_Render::header( $id ) : '';
	}

	/**
	 * [makaazi_property_gallery].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_gallery( $atts ) {
		$id = Makaazi_Render::pid( (array) $atts );
		return $id ? Makaazi_Render::gallery( $id ) : '';
	}

	/**
	 * [makaazi_property_facts].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_facts( $atts ) {
		$id = Makaazi_Render::pid( (array) $atts );
		return $id ? Makaazi_Render::facts( $id ) : '';
	}

	/**
	 * [makaazi_property_description] — only for page builders; block templates use the Content block.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_description( $atts ) {
		$a  = shortcode_atts(
			array(
				'id'    => 0,
				'title' => __( 'About this property', 'makaazi' ),
			),
			$atts
		);
		$id = Makaazi_Render::pid( $a );
		if ( ! $id || in_the_loop() && get_the_ID() === $id && doing_filter( 'the_content' ) ) {
			return '';
		}
		return '<section class="mk-single mk-sec">' . ( $a['title'] ? '<h2 class="mk-sec__title">' . esc_html( $a['title'] ) . '</h2>' : '' ) . '<div class="mk-desc">' . wp_kses_post( wpautop( get_post_field( 'post_content', $id ) ) ) . '</div></section>';
	}

	/**
	 * [makaazi_property_details].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_details( $atts ) {
		$a  = shortcode_atts( array( 'id' => 0, 'title' => '' ), $atts );
		$id = Makaazi_Render::pid( $a );
		return $id ? Makaazi_Render::details( $id, $a['title'] ) : '';
	}

	/**
	 * [makaazi_property_features].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_features( $atts ) {
		$a  = shortcode_atts( array( 'id' => 0, 'title' => '' ), $atts );
		$id = Makaazi_Render::pid( $a );
		return $id ? Makaazi_Render::features( $id, $a['title'] ) : '';
	}

	/**
	 * [makaazi_property_map].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_map( $atts ) {
		$a  = shortcode_atts( array( 'id' => 0, 'title' => '' ), $atts );
		$id = Makaazi_Render::pid( $a );
		return $id ? Makaazi_Render::map( $id, $a['title'] ) : '';
	}

	/**
	 * [makaazi_property_contact].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_contact( $atts ) {
		$id = Makaazi_Render::pid( (array) $atts );
		return $id ? Makaazi_Render::contact( $id ) : '';
	}

	/**
	 * [makaazi_related_properties].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function related( $atts ) {
		$a  = shortcode_atts(
			array(
				'id'      => 0,
				'count'   => makaazi_settings( 'related_count' ),
				'title'   => __( 'Similar properties', 'makaazi' ),
				'eyebrow' => __( 'You may also like', 'makaazi' ),
				'layout'  => 'carousel',
				'columns' => 4,
			),
			$atts,
			'makaazi_related_properties'
		);
		$id = Makaazi_Render::pid( $a );
		return $id ? Makaazi_Render::related( $id, $a ) : '';
	}
}
