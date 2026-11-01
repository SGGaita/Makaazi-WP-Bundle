<?php
/**
 * The public map location must never reveal the exact pin.
 *
 * @package Makaazi
 */

/**
 * @group privacy
 */
class Test_Makaazi_Privacy extends Makaazi_TestCase {

	/**
	 * Distance in metres.
	 */
	private function metres( $a, $b, $c, $d ) {
		$r    = 6371000;
		$dlat = deg2rad( $c - $a );
		$dlng = deg2rad( $d - $b );
		$h    = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $a ) ) * cos( deg2rad( $c ) ) * sin( $dlng / 2 ) ** 2;
		return 2 * $r * asin( sqrt( $h ) );
	}

	public function test_offset_is_inside_the_radius_and_not_the_pin() {
		$id  = $this->property(
			array(
				'lat' => -1.2716,
				'lng' => 36.8068,
				'map_radius' => 500,
			)
		);
		$pub = makaazi_public_location( $id );
		$d   = $this->metres( -1.2716, 36.8068, $pub[0], $pub[1] );
		$this->assertGreaterThan( 50, $d, 'Public centre must be offset from the pin.' );
		$this->assertLessThanOrEqual( 500, $d );
		$this->assertFalse( $pub[3] );
	}

	public function test_offset_is_stable() {
		$id = $this->property(
			array(
				'lat' => -1.29,
				'lng' => 36.78,
			)
		);
		$this->assertSame( makaazi_public_location( $id ), makaazi_public_location( $id ) );
	}

	public function test_exact_pin_only_when_ticked() {
		$id  = $this->property(
			array(
				'lat' => -1.29,
				'lng' => 36.78,
				'exact_pin' => 1,
			)
		);
		$pub = makaazi_public_location( $id );
		$this->assertTrue( $pub[3] );
		$this->assertEqualsWithDelta( -1.29, $pub[0], 0.000001 );
	}

	public function test_exact_coordinates_are_not_in_rest_or_page() {
		$id = $this->property(
			array(
				'title' => 'Hidden pin',
				'lat' => -1.2716123,
				'lng' => 36.8068456,
			)
		);
		$r  = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/property/' . $id ) );
		$this->assertStringNotContainsString( '36.8068456', wp_json_encode( $r->get_data() ) );
		$this->go_to( get_permalink( $id ) );
		$html = do_shortcode( '[makaazi_property_map]' ) . wp_json_encode( Makaazi_SEO::nodes() );
		$this->assertStringNotContainsString( '36.8068456', $html );
		$this->assertStringNotContainsString( '-1.2716123', $html );
	}
}
