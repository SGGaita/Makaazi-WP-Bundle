<?php
/**
 * Search filters, sorting and the index table.
 *
 * @package Makaazi
 */

/**
 * @group query
 */
class Test_Makaazi_Query extends Makaazi_TestCase {

	/**
	 * Fixture IDs.
	 *
	 * @var array
	 */
	private $p = array();

	public function set_up() {
		parent::set_up();
		$nbi = $this->location( 'Nairobi' );
		$this->location( 'Westlands', $nbi );
		$this->location( 'Karen', $nbi );
		$this->p['flat']  = $this->property(
			array(
				'title' => 'Flat',
				'status' => 'rent',
				'price' => 80000,
				'period' => 'month',
				'beds' => 2,
				'location' => 'westlands',
				'area' => 90,
				'date' => '2026-01-01 10:00:00',
			)
		);
		$this->p['villa'] = $this->property(
			array(
				'title' => 'Villa',
				'status' => 'sale',
				'type' => 'villa',
				'price' => 95000000,
				'beds' => 5,
				'baths' => 4,
				'parking' => 3,
				'location' => 'karen',
				'area' => 600,
				'featured' => 1,
				'date' => '2026-01-02 10:00:00',
			)
		);
		$this->p['town']  = $this->property(
			array(
				'title' => 'Town',
				'status' => 'sale',
				'type' => 'townhouse',
				'price' => 30000000,
				'beds' => 3,
				'location' => 'westlands',
				'area' => 220,
				'date' => '2026-01-03 10:00:00',
			)
		);
		$this->p['sold']  = $this->property(
			array(
				'title' => 'Sold',
				'status' => 'sale',
				'price' => 20000000,
				'availability' => 'sold',
				'date' => '2026-01-04 10:00:00',
			)
		);
		$this->p['stay']  = $this->property(
			array(
				'title' => 'Stay',
				'status' => 'stay',
				'price' => 9000,
				'period' => 'night',
				'beds' => 1,
				'location' => 'westlands',
				'date' => '2026-01-05 10:00:00',
			)
		);
		$this->p['por']   = $this->property(
			array(
				'title' => 'POR',
				'status' => 'sale',
				'price' => 1,
				'por' => 1,
				'beds' => 4,
				'date' => '2026-01-06 10:00:00',
			)
		);
	}

	/**
	 * Run the same assertion with and without the index table.
	 */
	public function data_index() {
		return array(
			'index'      => array( true ),
			'meta query' => array( false ),
		);
	}

	/**
	 * @dataProvider data_index
	 */
	public function test_status_filter( $index ) {
		$this->assertEqualsCanonicalizing( array( $this->p['flat'] ), $this->search( array( 'status' => 'rent' ), $index ) );
		$this->assertEqualsCanonicalizing( array( $this->p['stay'] ), $this->search( array( 'status' => 'stay' ), $index ) );
	}

	/**
	 * @dataProvider data_index
	 */
	public function test_sold_and_let_are_hidden( $index ) {
		$this->assertNotContains( $this->p['sold'], $this->search( array(), $index ) );
	}

	/**
	 * @dataProvider data_index
	 */
	public function test_price_range_excludes_price_on_request( $index ) {
		$ids = $this->search(
			array(
				'status' => 'sale',
				'min_price' => 1,
				'max_price' => 50000000,
			),
			$index
		);
		$this->assertEqualsCanonicalizing( array( $this->p['town'] ), $ids );
	}

	/**
	 * @dataProvider data_index
	 */
	public function test_rooms_minimums( $index ) {
		$this->assertEqualsCanonicalizing( array( $this->p['villa'], $this->p['town'], $this->p['por'] ), $this->search( array( 'beds' => 3 ), $index ) );
		$this->assertEqualsCanonicalizing(
			array( $this->p['villa'] ),
			$this->search(
				array(
					'baths' => 3,
					'parking' => 2,
				),
				$index
			)
		);
	}

	/**
	 * @dataProvider data_index
	 */
	public function test_location_includes_children( $index ) {
		$ids = $this->search( array( 'location' => 'nairobi' ), $index );
		$this->assertContains( $this->p['villa'], $ids );
		$this->assertContains( $this->p['flat'], $ids );
		$this->assertEqualsCanonicalizing( array( $this->p['flat'], $this->p['town'], $this->p['stay'] ), $this->search( array( 'location' => 'westlands' ), $index ) );
	}

	/**
	 * @dataProvider data_index
	 */
	public function test_sorts( $index ) {
		$this->assertSame(
			array( $this->p['villa'], $this->p['town'] ),
			array_slice(
				$this->search(
					array(
						'status' => 'sale',
						'sort' => 'price_desc',
						'min_price' => 1,
					),
					$index
				),
				0,
				2
			)
		);
		$this->assertSame( $this->p['villa'], $this->search( array( 'sort' => 'largest' ), $index )[0] );
		$this->assertSame( $this->p['villa'], $this->search( array( 'sort' => 'featured' ), $index )[0] );
		$this->assertSame( $this->p['por'], $this->search( array(), $index )[0], 'Newest first by default.' );
	}

	public function test_price_on_request_sorts_last_with_index() {
		$ids = $this->search(
			array(
				'status' => 'sale',
				'sort' => 'price_asc',
			)
		);
		$this->assertSame( $this->p['por'], end( $ids ) );
	}

	public function test_disabled_short_stays_are_left_out() {
		$this->set_settings( array( 'stays_enabled' => 0 ) );
		$this->assertNotContains( $this->p['stay'], $this->search( array() ) );
		$this->assertSame( '', Makaazi_Query::parse( array( 'status' => 'stay' ) )['status'] );
	}

	public function test_index_rows_follow_changes() {
		global $wpdb;
		$t   = Makaazi_Index::table();
		$id  = $this->p['flat'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE post_id = %d", $id ), ARRAY_A ); // phpcs:ignore
		$this->assertSame( 'rent', $row['status'] );
		$this->assertEquals( 80000, (float) $row['price'] );

		update_post_meta( $id, '_mk_price', 120000 );
		update_post_meta( $id, '_mk_availability', 'let' );
		wp_set_object_terms( $id, 'stay', 'property_status' );
		Makaazi_Index::flush();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE post_id = %d", $id ), ARRAY_A ); // phpcs:ignore
		$this->assertEquals( 120000, (float) $row['price'] );
		$this->assertSame( '1', (string) $row['closed'] );
		$this->assertSame( 'stay', $row['status'] );

		wp_delete_post( $id, true );
		$this->assertNull( $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$t} WHERE post_id = %d", $id ) ) ); // phpcs:ignore
	}

	public function test_full_rebuild() {
		global $wpdb;
		$t = Makaazi_Index::table();
		$wpdb->query( "DELETE FROM {$t}" ); // phpcs:ignore
		update_option( 'makaazi_index_cursor', 0 );
		$this->assertTrue( Makaazi_Index::build_batch( 1000 ) );
		$s = Makaazi_Index::stats();
		$this->assertSame( $s['total'], $s['rows'] );
		$this->assertTrue( $s['ready'] );
	}

	public function test_count_matches_search() {
		$this->assertSame(
			2,
			Makaazi_Query::count(
				Makaazi_Query::parse(
					array(
						'status' => 'sale',
						'beds' => 3,
						'min_price' => 1,
					)
				)
			)
		);
	}
}
