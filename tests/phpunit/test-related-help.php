<?php
/**
 * Similar properties and the Help content.
 *
 * @package Makaazi
 */

/**
 * @group related
 */
class Test_Makaazi_Related_Help extends Makaazi_TestCase {

	public function test_same_area_and_type_rank_first() {
		$nbi  = $this->location( 'Nairobi' );
		$this->location( 'Kilimani', $nbi );
		$this->location( 'Karen', $nbi );
		$base  = $this->property(
			array(
				'status' => 'rent',
				'location' => 'kilimani',
				'price' => 90000,
				'period' => 'month',
			)
		);
		$near  = $this->property(
			array(
				'status' => 'rent',
				'location' => 'kilimani',
				'price' => 95000,
				'period' => 'month',
			)
		);
		$far   = $this->property(
			array(
				'status' => 'rent',
				'location' => 'karen',
				'type' => 'villa',
				'price' => 400000,
				'period' => 'month',
			)
		);
		$sale  = $this->property(
			array(
				'status' => 'sale',
				'location' => 'kilimani',
			)
		);
		$this->set_settings( array( 'related_same_status' => 1 ) );
		makaazi_bump_cache();
		$ids = wp_list_pluck( Makaazi_Related::get( $base, 4 ), 'id' );
		$this->assertSame( $near, (int) $ids[0] );
		$this->assertNotContains( $base, array_map( 'intval', $ids ) );
		$this->assertNotContains( $sale, array_map( 'intval', $ids ) );
		$this->assertContains( $far, array_map( 'intval', $ids ) );
	}

	public function test_help_articles_are_complete() {
		foreach ( array( 'admin', 'account' ) as $ctx ) {
			$articles = Makaazi_Help::articles( $ctx );
			$this->assertNotEmpty( $articles );
			foreach ( $articles as $id => $a ) {
				$this->assertNotEmpty( $a['title'], $id );
				$this->assertNotEmpty( $a['body'], $id );
				$body = Makaazi_Help::body( $a, $ctx );
				$this->assertDoesNotMatchRegularExpression( '/\{(url|help):[^}]+\}/', $body, "Unreplaced token in {$id}" );
				$this->assertStringNotContainsString( 'href="#"', $body, "Unknown {url:…} token in {$id}" );
			}
		}
	}

	public function test_help_links_between_articles() {
		$a = Makaazi_Help::articles( 'admin' );
		$this->assertStringContainsString( 'article=demo', Makaazi_Help::body( $a['welcome'] ) );
	}

	public function test_help_articles_filter() {
		add_filter(
			'makaazi_help_articles',
			function ( $list, $ctx ) {
				if ( 'account' === $ctx ) {
					$list[] = array(
						'id'    => 'custom',
						'title' => 'Our rules',
						'body'  => '<p>Be kind.</p>',
					);
				}
				return $list;
			},
			10,
			2
		);
		$this->assertArrayHasKey( 'custom', Makaazi_Help::articles( 'account' ) );
		$this->assertArrayNotHasKey( 'custom', Makaazi_Help::articles( 'admin' ) );
	}

	public function test_dashboard_tabs_filter_adds_help() {
		$tabs = apply_filters( 'makaazi_dashboard_tabs', array( 'listings' => 'My listings' ), wp_get_current_user() );
		$this->assertArrayHasKey( 'help', $tabs );
		$this->assertStringContainsString( 'mk-dhelp', apply_filters( 'makaazi_dashboard_tab_help', '', wp_get_current_user() ) );
	}
}
