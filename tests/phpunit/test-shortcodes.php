<?php
/**
 * Shortcode output.
 *
 * @package Makaazi
 */

/**
 * @group shortcodes
 */
class Test_Makaazi_Shortcodes extends Makaazi_TestCase {

	public function test_properties_grid() {
		$this->property(
			array(
				'title' => 'Featured One',
				'featured' => 1,
			)
		);
		$this->property( array( 'title' => 'Plain One' ) );
		$html = do_shortcode( '[makaazi_properties source="featured" title="Featured properties"]' );
		$this->assertStringContainsString( 'Featured One', $html );
		$this->assertStringNotContainsString( 'Plain One', $html );
		$this->assertStringContainsString( 'mk-sechead__title', $html );
	}

	public function test_short_stay_section_hides_when_switched_off() {
		$this->property(
			array(
				'title' => 'Beach cottage',
				'status' => 'stay',
				'price' => 9000,
				'period' => 'night',
			)
		);
		$this->set_settings( array( 'stays_enabled' => 1 ) );
		$this->assertStringContainsString( 'Beach cottage', do_shortcode( '[makaazi_properties source="stay"]' ) );
		$this->assertStringContainsString( '/night', do_shortcode( '[makaazi_properties source="stay"]' ) );
		$this->set_settings( array( 'stays_enabled' => 0 ) );
		$this->assertSame( '', do_shortcode( '[makaazi_properties source="stay"]' ) );
	}

	public function test_search_tabs() {
		$this->set_settings( array( 'stays_enabled' => 1 ) );
		$html = do_shortcode( '[makaazi_search]' );
		$this->assertSame( 3, substr_count( $html, 'name="status"' ) );
		$this->assertStringContainsString( 'value="stay"', $html );
		$html = do_shortcode( '[makaazi_search tabs="sale,rent" default_status="rent"]' );
		$this->assertStringNotContainsString( 'value="stay"', $html );
		$this->assertMatchesRegularExpression( '/value="rent"\s*checked/', $html );
	}

	public function test_listing_title_is_h1_on_the_properties_page() {
		$this->property();
		$this->go_to( get_post_type_archive_link( 'property' ) );
		$this->assertStringContainsString( '<h1 class="mk-results__title"', do_shortcode( '[makaazi_property_listing]' ) );
		$this->go_to( home_url( '/' ) );
		$this->assertStringContainsString( '<h2 class="mk-results__title"', do_shortcode( '[makaazi_property_listing]' ) );
	}

	public function test_card_has_keyboard_photo_strip_and_title() {
		$file = get_temp_dir() . 'makaazi-test.jpg';
		$img  = imagecreatetruecolor( 40, 30 );
		imagejpeg( $img, $file );
		$a = self::factory()->attachment->create_upload_object( $file );
		$b = self::factory()->attachment->create_upload_object( $file );
		$id  = $this->property( array( 'title' => 'Two photos' ) );
		update_post_meta( $id, '_mk_gallery', array( $a, $b ) );
		$html = Makaazi_Render::card( $id );
		$this->assertStringContainsString( 'data-mk-strip tabindex="0"', $html );
		$this->assertStringContainsString( 'Photos of Two photos', $html );
		$this->assertStringContainsString( 'mk-card__title', $html );
	}

	public function test_card_data_filter() {
		$id = $this->property();
		add_filter(
			'makaazi_card_data',
			function ( $c ) {
				$c['button_text'] = 'Book a viewing';
				return $c;
			}
		);
		$this->assertStringContainsString( 'Book a viewing', Makaazi_Render::card( $id ) );
	}

	public function test_shortcode_block_renders_without_wpautop() {
		$block = "<!-- wp:shortcode -->\n[makaazi_search]\n<!-- /wp:shortcode -->";
		$html  = do_blocks( $block );
		$this->assertStringContainsString( 'mk-search', $html );
		$this->assertStringNotContainsString( '<p>[makaazi_search]', $html );
	}

	public function test_slider_shortcode() {
		$sid = self::factory()->post->create(
			array(
				'post_type' => 'mk_slider',
				'post_title' => 'Home',
			)
		);
		$this->assertIsString( do_shortcode( '[makaazi_slider id="' . $sid . '"]' ) );
	}
}
