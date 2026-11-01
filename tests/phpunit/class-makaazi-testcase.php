<?php
/**
 * Base test case with helpers for making listings.
 *
 * @package Makaazi
 */

/**
 * Helpers shared by the Makaazi tests.
 */
abstract class Makaazi_TestCase extends WP_UnitTestCase {

	/**
	 * Settings to restore.
	 *
	 * @var array
	 */
	protected $saved_settings;

	/**
	 * Reset settings and the settings cache.
	 */
	public function set_up() {
		parent::set_up();
		$this->saved_settings = get_option( 'makaazi_settings', array() );
		update_option( 'makaazi_index_ready', 1 );
		$this->refresh_settings();
	}

	/**
	 * Restore settings.
	 */
	public function tear_down() {
		update_option( 'makaazi_settings', $this->saved_settings );
		$this->refresh_settings();
		parent::tear_down();
	}

	/**
	 * Change settings for one test.
	 *
	 * @param array $values Values.
	 */
	protected function set_settings( $values ) {
		update_option( 'makaazi_settings', array_merge( (array) get_option( 'makaazi_settings', array() ), $values ) );
		$this->refresh_settings();
	}

	/**
	 * Make makaazi_settings() re-read the option.
	 */
	protected function refresh_settings() {
		makaazi_settings_changed();
	}

	/**
	 * Create a property with meta and terms.
	 *
	 * @param array $a Options: title, status, type, location, price, period, beds, baths, parking, area, featured, availability, lat, lng, por.
	 * @return int
	 */
	protected function property( $a = array() ) {
		$a  = wp_parse_args(
			$a,
			array(
				'title'        => 'Test listing',
				'status'       => 'sale',
				'type'         => 'apartment',
				'location'     => '',
				'use'          => 'residential',
				'price'        => 10000000,
				'period'       => 'total',
				'beds'         => 2,
				'baths'        => 2,
				'parking'      => 1,
				'area'         => 100,
				'featured'     => 0,
				'availability' => 'available',
				'post_status'  => 'publish',
				'date'         => '',
			)
		);
		$id = self::factory()->post->create(
			array(
				'post_type'    => 'property',
				'post_title'   => $a['title'],
				'post_status'  => $a['post_status'],
				'post_content' => 'A test property description with enough words to make an excerpt for the meta description.',
				'post_date'    => $a['date'] ? $a['date'] : current_time( 'mysql' ),
			)
		);
		wp_set_object_terms( $id, $a['status'], 'property_status' );
		wp_set_object_terms( $id, $a['type'], 'property_type' );
		wp_set_object_terms( $id, $a['use'], 'property_use' );
		if ( $a['location'] ) {
			wp_set_object_terms( $id, $a['location'], 'property_location' );
		}
		$meta = array(
			'price'        => $a['price'],
			'price_period' => $a['period'],
			'bedrooms'     => $a['beds'],
			'bathrooms'    => $a['baths'],
			'parking'      => $a['parking'],
			'area_sqm'     => $a['area'],
			'featured'     => $a['featured'],
			'availability' => $a['availability'],
		);
		foreach ( array( 'lat', 'lng', 'por', 'exact_pin', 'map_radius' ) as $k ) {
			if ( isset( $a[ $k ] ) ) {
				$meta[ 'por' === $k ? 'price_on_request' : $k ] = $a[ $k ];
			}
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, '_mk_' . $k, $v );
		}
		Makaazi_Index::flush();
		return $id;
	}

	/**
	 * A location term (with optional parent).
	 *
	 * @param string $name   Name.
	 * @param int    $parent Parent term ID.
	 * @return int Term ID.
	 */
	protected function location( $name, $parent = 0 ) {
		$t = wp_insert_term( $name, 'property_location', array( 'parent' => $parent ) );
		return is_wp_error( $t ) ? (int) $t->get_error_data() : (int) $t['term_id'];
	}

	/**
	 * IDs returned for filters.
	 *
	 * @param array $filters  Filters (as in the URL).
	 * @param bool  $use_index Use the index table.
	 * @return int[]
	 */
	protected function search( $filters, $use_index = true ) {
		if ( ! $use_index ) {
			add_filter( 'makaazi_use_index', '__return_false' );
		}
		$ids = get_posts( Makaazi_Query::args( Makaazi_Query::parse( $filters ), 100, array( 'fields' => 'ids' ) ) );
		remove_filter( 'makaazi_use_index', '__return_false' );
		return array_map( 'intval', $ids );
	}
}
