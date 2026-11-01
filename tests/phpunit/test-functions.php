<?php
/**
 * Formatting helpers: money, area, statuses, brackets.
 *
 * @package Makaazi
 */

/**
 * @group functions
 */
class Test_Makaazi_Functions extends Makaazi_TestCase {

	public function test_money_uses_currency_and_thousands() {
		$this->set_settings( array( 'currency' => 'KES' ) );
		$this->assertSame( 'KES 85,000,000', makaazi_money( 85000000 ) );
	}

	public function test_short_money() {
		$this->set_settings( array( 'currency' => 'KES' ) );
		$this->assertSame( 'KES 85M', makaazi_short_money( 85000000 ) );
		$this->assertSame( 'KES 180K', makaazi_short_money( 180000 ) );
		$this->assertStringContainsString( '1.5M', makaazi_short_money( 1500000 ) );
	}

	public function test_price_shows_period_and_request() {
		$id = $this->property(
			array(
				'status' => 'rent',
				'price' => 95000,
				'period' => 'month',
			)
		);
		$this->assertStringContainsString( '95,000', wp_strip_all_tags( makaazi_price( $id ) ) );
		$this->assertStringContainsString( '/month', wp_strip_all_tags( makaazi_price( $id ) ) );
		update_post_meta( $id, '_mk_price_on_request', 1 );
		$this->assertStringContainsString( 'Price on request', wp_strip_all_tags( makaazi_price( $id ) ) );
	}

	public function test_area_html_carries_square_metres_for_the_unit_switch() {
		$html = makaazi_area_html( 100 );
		$this->assertStringContainsString( 'data-sqm="100"', $html );
		$this->set_settings( array( 'unit' => 'sqft' ) );
		$this->assertStringContainsString( '1,076', makaazi_area_html( 100 ) );
	}

	public function test_statuses_follow_the_short_stay_switch() {
		$this->set_settings( array( 'stays_enabled' => 1 ) );
		$this->assertSame( array( 'sale', 'rent', 'stay' ), array_keys( makaazi_statuses() ) );
		$this->assertTrue( makaazi_is_status( 'stay' ) );
		$this->set_settings( array( 'stays_enabled' => 0 ) );
		$this->assertSame( array( 'sale', 'rent' ), array_keys( makaazi_statuses() ) );
		$this->assertFalse( makaazi_is_status( 'stay' ) );
		$this->assertArrayHasKey( 'stay', makaazi_statuses( true ) );
		$this->assertFalse( makaazi_is_status( 'bogus' ) );
	}

	public function test_brackets_per_status_are_sorted() {
		$this->set_settings(
			array(
				'brackets_sale' => '50000000,10000000',
				'brackets_rent' => '50000,20000',
				'brackets_stay' => '8000,3000',
			)
		);
		$this->assertSame( array( 10000000, 50000000 ), makaazi_brackets( 'sale' ) );
		$this->assertSame( array( 20000, 50000 ), makaazi_brackets( 'rent' ) );
		$this->assertSame( array( 3000, 8000 ), makaazi_brackets( 'stay' ) );
	}

	public function test_reference_numbers_are_unique_and_prefixed() {
		$this->set_settings( array( 'ref_prefix' => 'MK-' ) );
		$a = Makaazi_Meta::next_ref();
		$b = Makaazi_Meta::next_ref();
		$this->assertMatchesRegularExpression( '/^MK-\d{5}$/', $a );
		$this->assertNotSame( $a, $b );
	}

	public function test_settings_reread_after_save() {
		$this->set_settings( array( 'currency' => 'USD' ) );
		$this->assertSame( 'USD', makaazi_settings( 'currency' ) );
		$this->set_settings( array( 'currency' => 'KES' ) );
		$this->assertSame( 'KES', makaazi_settings( 'currency' ) );
	}
}
