<?php
/**
 * Related properties scoring.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Scores other listings by area, type, category, status and price.
 */
class Makaazi_Related {

	/**
	 * Hook in.
	 */
	public static function init() {}

	/**
	 * Related property IDs with reasons.
	 *
	 * @param int $post_id Post ID.
	 * @param int $count   How many.
	 * @return array[] Each: ['id' => int, 'why' => string[]].
	 */
	public static function get( $post_id, $count = 8 ) {
		$key    = 'mk_rel_' . $post_id . '_' . $count . '_' . makaazi_cache_ver();
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$loc       = makaazi_location_term( $post_id );
		$loc_id    = $loc ? $loc->term_id : 0;
		$parent_id = $loc ? (int) $loc->parent : 0;
		$type      = makaazi_term( $post_id, 'property_type' );
		$cats      = wp_get_object_terms( $post_id, 'property_category', array( 'fields' => 'ids' ) );
		$status    = makaazi_status( $post_id );
		$price     = (float) makaazi_meta( $post_id, 'price', 0 );
		$same_only = (bool) makaazi_settings( 'related_same_status' );

		$f = Makaazi_Query::defaults();
		if ( $same_only ) {
			$f['status'] = $status;
		}
		$q = new WP_Query(
			Makaazi_Query::args(
				$f,
				150,
				array(
					'post__not_in' => array( $post_id ),
					'fields'       => 'ids',
				)
			)
		);

		$scored = array();
		foreach ( $q->posts as $id ) {
			$s   = 0;
			$why = array();
			$l   = makaazi_location_term( $id );
			if ( $l && $loc_id && $l->term_id === $loc_id ) {
				$s    += 4;
				$why[] = __( 'Same area', 'makaazi' );
			} elseif ( $l && $parent_id && ( (int) $l->parent === $parent_id || $l->term_id === $parent_id ) ) {
				$s    += 2;
				$why[] = __( 'Nearby', 'makaazi' );
			}
			$t = makaazi_term( $id, 'property_type' );
			if ( $t && $type && $t->term_id === $type->term_id ) {
				$s    += 3;
				$why[] = __( 'Same type', 'makaazi' );
			}
			if ( $cats && array_intersect( $cats, wp_get_object_terms( $id, 'property_category', array( 'fields' => 'ids' ) ) ) ) {
				$s    += 2;
				$why[] = __( 'Same category', 'makaazi' );
			}
			if ( makaazi_status( $id ) === $status ) {
				$s += 2;
			}
			$p = (float) makaazi_meta( $id, 'price', 0 );
			if ( $price && $p && abs( $p - $price ) <= $price * 0.3 ) {
				$s    += 1;
				$why[] = __( 'Similar price', 'makaazi' );
			}
			$scored[] = array(
				'id'    => $id,
				'score' => $s,
				'why'   => $why,
				'date'  => get_post_time( 'U', true, $id ),
			);
		}
		usort(
			$scored,
			function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return $b['date'] - $a['date'];
				}
				return $b['score'] - $a['score'];
			}
		);
		$good = array_values(
			array_filter(
				$scored,
				function ( $r ) {
					return $r['score'] >= 5;
				}
			)
		);
		if ( count( $good ) < 3 ) {
			// Fallback: fill with the newest listings of the same status.
			$good = $scored;
		}
		$out = array();
		foreach ( array_slice( $good, 0, $count ) as $r ) {
			$out[] = array(
				'id'  => $r['id'],
				'why' => array_slice( $r['why'], 0, 2 ),
			);
		}
		/**
		 * Filter the related listings for a property.
		 *
		 * @param array $out Rows of array( 'id' => int, 'why' => string[] ).
		 * @param int   $id  Property ID.
		 */
		$out = apply_filters( 'makaazi_related', $out, $id );
		set_transient( $key, $out, 12 * HOUR_IN_SECONDS );
		return $out;
	}
}
