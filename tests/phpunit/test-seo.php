<?php
/**
 * Schema.org data, descriptions, robots and SEO plugin integration.
 *
 * @package Makaazi
 */

/**
 * @group seo
 */
class Test_Makaazi_SEO extends Makaazi_TestCase {

	public function test_listing_schema() {
		$this->location( 'Westlands', $this->location( 'Nairobi' ) );
		$id = $this->property(
			array(
				'title' => 'Riverside 3BR',
				'status' => 'rent',
				'price' => 180000,
				'period' => 'month',
				'beds' => 3,
				'baths' => 2.5,
				'area' => 145,
				'location' => 'westlands',
			)
		);
		$n  = Makaazi_SEO::listing( $id );
		$this->assertSame( 'RealEstateListing', $n['@type'] );
		$this->assertSame( 'Apartment', $n['about']['@type'] );
		$this->assertSame( 3, $n['about']['numberOfBedrooms'] );
		$this->assertSame( 'MTK', $n['about']['floorSize']['unitCode'] );
		$this->assertSame( 'Westlands', $n['about']['address']['addressLocality'] );
		$this->assertSame( 'Nairobi', $n['about']['address']['addressRegion'] );
		$this->assertSame( 180000.0, (float) $n['offers']['price'] );
		$this->assertSame( 'MON', $n['offers']['priceSpecification']['unitCode'] );
		$this->assertStringContainsString( 'LeaseOut', $n['offers']['businessFunction'] );
		$this->assertArrayNotHasKey( 'geo', $n['about'], 'No coordinates unless the exact pin is public.' );
	}

	public function test_short_stay_and_sold_offers() {
		$stay = Makaazi_SEO::listing(
			$this->property(
				array(
					'status' => 'stay',
					'price' => 9000,
					'period' => 'night',
				)
			)
		);
		$this->assertSame( 'DAY', $stay['offers']['priceSpecification']['unitCode'] );
		$sold = Makaazi_SEO::listing( $this->property( array( 'availability' => 'sold' ) ) );
		$this->assertSame( 'https://schema.org/SoldOut', $sold['offers']['availability'] );
		$por = Makaazi_SEO::listing( $this->property( array( 'por' => 1 ) ) );
		$this->assertArrayNotHasKey( 'offers', $por );
	}

	public function test_description_is_short_and_specific() {
		$id = $this->property(
			array(
				'status' => 'sale',
				'beds' => 4,
				'type' => 'villa',
			)
		);
		$d  = Makaazi_SEO::description( $id );
		$this->assertLessThanOrEqual( 160, mb_strlen( $d ) );
		$this->assertStringStartsWith( '4-bedroom villa for sale', $d );
	}

	public function test_filtered_searches_are_noindex() {
		$this->go_to( get_post_type_archive_link( 'property' ) );
		$_GET = array( 'status' => 'rent' );
		$this->assertFalse( Makaazi_SEO::filtered() );
		$_GET = array(
			'status' => 'rent',
			'beds' => '2',
		);
		$this->assertTrue( Makaazi_SEO::filtered() );
		$this->assertSame( 'noindex, follow', Makaazi_SEO::robots_string( 'index, follow' ) );
		$_GET = array();
	}

	public function test_yoast_graph_gets_listing_as_main_entity() {
		$id = $this->property();
		$this->go_to( get_permalink( $id ) );
		$graph = array(
			array(
				'@type' => array( 'WebPage', 'ItemPage' ),
				'@id'   => get_permalink( $id ),
			),
		);
		$out   = Makaazi_SEO::yoast_graph( $graph );
		$types = wp_list_pluck( $out, '@type' );
		$this->assertContains( 'RealEstateListing', $types );
		$this->assertNotContains( 'BreadcrumbList', $types, 'Yoast adds its own breadcrumbs.' );
		$this->assertSame( get_permalink( $id ) . '#listing', $out[0]['mainEntity']['@id'] );
	}

	public function test_empty_seo_plugin_description_is_filled() {
		$id = $this->property();
		$this->go_to( get_permalink( $id ) );
		$this->assertNotSame( '', Makaazi_SEO::fill_description( '' ) );
		$this->assertSame( 'Written by hand', Makaazi_SEO::fill_description( 'Written by hand' ) );
	}

	public function test_own_tags_when_no_seo_plugin() {
		$id = $this->property( array( 'title' => 'Share me' ) );
		$this->go_to( get_permalink( $id ) );
		ob_start();
		Makaazi_SEO::head();
		Makaazi_SEO::print_schema();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'property="og:title"', $html );
		$this->assertStringContainsString( 'name="description"', $html );
		$this->assertStringContainsString( 'application/ld+json', $html );
	}

	public function test_sitemaps_exclude_private_types() {
		$types = Makaazi_SEO::core_sitemap_types(
			array(
				'property' => 1,
				'mk_enquiry' => 1,
				'mk_slider' => 1,
				'mk_agent' => 1,
			)
		);
		$this->assertArrayHasKey( 'property', $types );
		$this->assertArrayNotHasKey( 'mk_enquiry', $types );
		$this->assertArrayNotHasKey( 'mk_slider', $types );
		$this->assertTrue( Makaazi_SEO::sitemap_exclude( false, 'mk_enquiry' ) );
	}
}
