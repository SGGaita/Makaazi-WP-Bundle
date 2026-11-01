<?php
/**
 * Turns filter parameters into property queries.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Filter parsing and query building.
 */
class Makaazi_Query {

	/**
	 * Sort options.
	 *
	 * @return array
	 */
	public static function sorts() {
		/**
		 * Filter the sort options offered on listings. Add a key here and handle it with makaazi_query_args.
		 *
		 * @param array $sorts key => label.
		 */
		return apply_filters( 'makaazi_sorts', array(
			'newest'     => __( 'Newest', 'makaazi' ),
			'price_asc'  => __( 'Price: low to high', 'makaazi' ),
			'price_desc' => __( 'Price: high to low', 'makaazi' ),
			'largest'    => __( 'Largest', 'makaazi' ),
			'featured'   => __( 'Featured first', 'makaazi' ),
		) );
	}

	/**
	 * Empty filter set.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'status'    => '',
			'location'  => '',
			'type'      => array(),
			'use'       => '',
			'category'  => array(),
			'min_price' => 0,
			'max_price' => 0,
			'beds'      => 0,
			'baths'     => 0,
			'parking'   => 0,
			'features'  => array(),
			'q'         => '',
			'sort'      => makaazi_settings( 'default_sort' ),
			'pg'        => 1,
		);
	}

	/**
	 * Normalise filters from a request array (GET or REST params).
	 *
	 * @param array $src    Source params.
	 * @param array $preset Values that always apply (e.g. taxonomy archive term).
	 * @return array
	 */
	public static function parse( $src, $preset = array() ) {
		$f    = self::defaults();
		$list = function ( $v ) {
			if ( is_array( $v ) ) {
				$v = implode( ',', $v );
			}
			return array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) $v ) ) ) );
		};
		$src = wp_unslash( (array) $src );
		if ( isset( $src['status'] ) ) {
			$f['status'] = makaazi_is_status( $src['status'] ) ? $src['status'] : '';
		}
		if ( isset( $src['location'] ) ) {
			$f['location'] = sanitize_title( $src['location'] );
		}
		if ( isset( $src['type'] ) ) {
			$f['type'] = $list( $src['type'] );
		}
		if ( isset( $src['use'] ) ) {
			$f['use'] = sanitize_title( $src['use'] );
		}
		if ( isset( $src['category'] ) ) {
			$f['category'] = $list( $src['category'] );
		}
		if ( isset( $src['features'] ) ) {
			$f['features'] = $list( $src['features'] );
		}
		foreach ( array( 'min_price', 'max_price', 'beds', 'baths', 'parking', 'pg' ) as $k ) {
			if ( isset( $src[ $k ] ) ) {
				$f[ $k ] = absint( $src[ $k ] );
			}
		}
		$f['pg'] = max( 1, $f['pg'] );
		if ( isset( $src['q'] ) ) {
			$f['q'] = sanitize_text_field( $src['q'] );
		}
		if ( isset( $src['sort'] ) && isset( self::sorts()[ $src['sort'] ] ) ) {
			$f['sort'] = $src['sort'];
		}
		foreach ( $preset as $k => $v ) {
			if ( '' !== $v && array() !== $v ) {
				$f[ $k ] = $v;
			}
		}
		return $f;
	}

	/**
	 * Filters that differ from the defaults, as URL query args.
	 *
	 * @param array $f Filters.
	 * @return array
	 */
	public static function to_args( $f ) {
		$d   = self::defaults();
		$out = array();
		foreach ( $f as $k => $v ) {
			if ( ! array_key_exists( $k, $d ) || $v == $d[ $k ] || '' === $v || array() === $v || 0 === $v ) { // phpcs:ignore Universal.Operators.StrictComparisons
				continue;
			}
			if ( 'pg' === $k && 1 === (int) $v ) {
				continue;
			}
			$out[ $k ] = is_array( $v ) ? implode( ',', $v ) : $v;
		}
		return $out;
	}

	/**
	 * Build WP_Query args.
	 *
	 * @param array $f        Filters.
	 * @param int   $per_page Items per page.
	 * @param array $extra    Extra query args.
	 * @return array
	 */
	public static function args( $f, $per_page = 12, $extra = array() ) {
		$tax  = array( 'relation' => 'AND' );
		$meta = array(
			'relation' => 'AND',
			'avail'    => array(
				'relation' => 'OR',
				array(
					'key'     => '_mk_availability',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_mk_availability',
					'value'   => array( 'sold', 'let' ),
					'compare' => 'NOT IN',
				),
			),
		);
		if ( $f['status'] ) {
			$tax[] = array(
				'taxonomy' => 'property_status',
				'field'    => 'slug',
				'terms'    => $f['status'],
			);
		} else {
			// Leave out listing types that are switched off (e.g. Short Stays).
			$off = array_diff( array_keys( makaazi_statuses( true ) ), array_keys( makaazi_statuses() ) );
			if ( $off ) {
				$tax[] = array(
					'taxonomy' => 'property_status',
					'field'    => 'slug',
					'terms'    => array_values( $off ),
					'operator' => 'NOT IN',
				);
			}
		}
		if ( $f['location'] ) {
			$tax[] = array(
				'taxonomy'         => 'property_location',
				'field'            => 'slug',
				'terms'            => $f['location'],
				'include_children' => true,
			);
		}
		if ( $f['type'] ) {
			$tax[] = array(
				'taxonomy' => 'property_type',
				'field'    => 'slug',
				'terms'    => $f['type'],
			);
		}
		if ( $f['use'] ) {
			$tax[] = array(
				'taxonomy' => 'property_use',
				'field'    => 'slug',
				'terms'    => $f['use'],
			);
		}
		if ( $f['category'] ) {
			$tax[] = array(
				'taxonomy' => 'property_category',
				'field'    => 'slug',
				'terms'    => $f['category'],
				'operator' => 'AND',
			);
		}
		if ( $f['features'] ) {
			$tax[] = array(
				'taxonomy' => 'property_feature',
				'field'    => 'slug',
				'terms'    => $f['features'],
				'operator' => 'AND',
			);
		}
		if ( $f['min_price'] || $f['max_price'] ) {
			$meta[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_mk_price_on_request',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_mk_price_on_request',
					'value' => array( '', '0' ),
					'compare' => 'IN',
				),
			);
		}
		if ( $f['min_price'] ) {
			$meta[] = array(
				'key'     => '_mk_price',
				'value'   => $f['min_price'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $f['max_price'] ) {
			$meta[] = array(
				'key'     => '_mk_price',
				'value'   => $f['max_price'],
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}
		foreach ( array(
			'beds'    => '_mk_bedrooms',
			'baths'   => '_mk_bathrooms',
			'parking' => '_mk_parking',
		) as $k => $key ) {
			if ( $f[ $k ] ) {
				$meta[] = array(
					'key'     => $key,
					'value'   => $f[ $k ],
					'compare' => '>=',
					'type'    => 'DECIMAL(10,1)',
				);
			}
		}
		$args = array(
			'post_type'           => 'property',
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => $f['pg'],
			'ignore_sticky_posts' => true,
			'tax_query'           => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_query'          => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
		);
		if ( $f['q'] ) {
			if ( preg_match( '/^[a-z]{1,4}-?\d{2,}$/i', trim( $f['q'] ) ) ) {
				$args['meta_query'][] = array(
					'key'     => '_mk_ref',
					'value'   => trim( $f['q'] ),
					'compare' => 'LIKE',
				);
			} else {
				$args['s'] = $f['q'];
			}
		}
		$args = self::apply_sort( $args, $f['sort'] );
		/**
		 * Filter the WP_Query arguments used for property searches and listings.
		 *
		 * @param array $args    Query args.
		 * @param array $filters Parsed filters (status, location, type, price…).
		 */
		return apply_filters( 'makaazi_query_args', array_merge( $args, $extra ), $f );
	}

	/**
	 * Apply sort order to query args.
	 *
	 * @param array  $args Query args.
	 * @param string $sort Sort key.
	 * @return array
	 */
	public static function apply_sort( $args, $sort ) {
		switch ( $sort ) {
			case 'price_asc':
			case 'price_desc':
				$args['meta_query']['price_clause'] = array(
					'key'  => '_mk_price',
					'type' => 'NUMERIC',
				);
				$args['orderby'] = array(
					'price_clause' => 'price_asc' === $sort ? 'ASC' : 'DESC',
					'date'         => 'DESC',
				);
				break;
			case 'largest':
				$args['meta_query']['area_clause'] = array(
					'key'  => '_mk_area_sqm',
					'type' => 'NUMERIC',
				);
				$args['orderby'] = array(
					'area_clause' => 'DESC',
					'date'        => 'DESC',
				);
				break;
			case 'featured':
				$args['meta_query']['feat_clause'] = array(
					'key'  => '_mk_featured',
					'type' => 'NUMERIC',
				);
				$args['orderby'] = array(
					'feat_clause' => 'DESC',
					'date'        => 'DESC',
				);
				break;
			case 'random':
				$args['orderby'] = 'rand';
				break;
			default:
				$args['orderby'] = array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				);
		}
		return $args;
	}

	/**
	 * Count matches for a filter set (cached).
	 *
	 * @param array $f Filters.
	 * @return int
	 */
	public static function count( $f ) {
		$f['pg'] = 1;
		$key     = 'mk_cnt_' . md5( wp_json_encode( $f ) . makaazi_cache_ver() );
		$cached  = get_transient( $key );
		if ( false !== $cached ) {
			return (int) $cached;
		}
		$q = new WP_Query(
			self::args(
				$f,
				1,
				array(
					'fields'                 => 'ids',
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			)
		);
		set_transient( $key, (int) $q->found_posts, HOUR_IN_SECONDS );
		return (int) $q->found_posts;
	}
}
