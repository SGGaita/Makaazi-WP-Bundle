<?php
/**
 * REST endpoints: enquiries, saved lists, listing pages.
 *
 * @package Makaazi
 */

/**
 * @group rest
 */
class Test_Makaazi_Rest extends Makaazi_TestCase {

	/**
	 * Send a REST request.
	 */
	private function req( $method, $route, $params = array() ) {
		$r = new WP_REST_Request( $method, '/makaazi/v1' . $route );
		foreach ( $params as $k => $v ) {
			$r->set_param( $k, $v );
		}
		return rest_do_request( $r );
	}

	public function set_up() {
		parent::set_up();
		reset_phpmailer_instance();
		$_SERVER['REMOTE_ADDR'] = '10.0.0.' . wp_rand( 1, 250 );
	}

	public function test_enquiry_is_saved_and_emailed() {
		$this->set_settings( array( 'enquiry_email' => 'sales@example.org' ) );
		$id = $this->property( array( 'title' => 'Garden flat' ) );
		$r  = $this->req(
			'POST',
			'/enquiry',
			array(
				'property_id' => $id,
				'name' => 'Wanjiru',
				'phone' => '0712 345 678',
				'email' => 'w@example.org',
				'message' => 'Is it available?',
			)
		);
		$this->assertSame( 200, $r->get_status() );
		$this->assertTrue( $r->get_data()['ok'] );
		$e = get_posts(
			array(
				'post_type' => 'mk_enquiry',
				'post_status' => 'private',
				'meta_key' => '_mk_property_id',
				'meta_value' => $id,
			)
		);
		$this->assertCount( 1, $e );
		$this->assertSame( 'Wanjiru', get_post_meta( $e[0]->ID, '_mk_name', true ) );
		$mail = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertNotFalse( $mail );
		$this->assertStringContainsString( 'sales@example.org', wp_json_encode( $mail->to ) );
		$this->assertSame( 1, (int) get_post_meta( $id, '_mk_leads', true ) );
	}

	public function test_enquiry_fires_action_and_recipient_filter() {
		$id   = $this->property();
		$seen = array();
		add_action(
			'makaazi_enquiry_created',
			function ( $eid, $pid, $data ) use ( &$seen ) {
				$seen = array( $pid, $data['name'] );
			},
			10,
			3
		);
		add_filter(
			'makaazi_enquiry_recipients',
			function ( $to ) {
				$to[] = 'crm@example.org';
				return $to;
			}
		);
		$this->req(
			'POST',
			'/enquiry',
			array(
				'property_id' => $id,
				'name' => 'Otieno',
				'phone' => '0722000111',
				'message' => 'Hi',
			)
		);
		$this->assertSame( array( $id, 'Otieno' ), $seen );
		$this->assertStringContainsString( 'crm@example.org', wp_json_encode( tests_retrieve_phpmailer_instance()->get_sent()->to ) );
	}

	public function test_enquiry_validation() {
		$id = $this->property();
		$this->assertSame(
			400,
			$this->req(
				'POST',
				'/enquiry',
				array(
					'property_id' => $id,
					'name' => '',
					'phone' => '0712345678',
				)
			)->get_status()
		);
		$this->assertSame(
			400,
			$this->req(
				'POST',
				'/enquiry',
				array(
					'property_id' => $id,
					'name' => 'A',
					'phone' => '123',
				)
			)->get_status()
		);
		$this->assertSame(
			400,
			$this->req(
				'POST',
				'/enquiry',
				array(
					'property_id' => $id,
					'name' => 'A',
					'phone' => '0712345678',
					'email' => 'nope',
				)
			)->get_status()
		);
		$draft = $this->property( array( 'post_status' => 'draft' ) );
		$this->assertSame(
			400,
			$this->req(
				'POST',
				'/enquiry',
				array(
					'property_id' => $draft,
					'name' => 'A',
					'phone' => '0712345678',
				)
			)->get_status()
		);
	}

	public function test_honeypot_is_silently_dropped() {
		$id = $this->property();
		$r  = $this->req(
			'POST',
			'/enquiry',
			array(
				'property_id' => $id,
				'name' => 'Bot',
				'phone' => '0712345678',
				'website' => 'http://spam.example',
			)
		);
		$this->assertTrue( $r->get_data()['ok'] );
		$this->assertCount(
			0,
			get_posts(
				array(
					'post_type' => 'mk_enquiry',
					'post_status' => 'private',
				)
			)
		);
	}

	public function test_rate_limit() {
		$id = $this->property();
		for ( $i = 0; $i < 5; $i++ ) {
			$this->assertSame(
				200,
				$this->req(
					'POST',
					'/enquiry',
					array(
						'property_id' => $id,
						'name' => 'N',
						'phone' => '0712345678',
					)
				)->get_status()
			);
		}
		$this->assertSame(
			429,
			$this->req(
				'POST',
				'/enquiry',
				array(
					'property_id' => $id,
					'name' => 'N',
					'phone' => '0712345678',
				)
			)->get_status()
		);
	}

	public function test_saved_cards_only_published_properties() {
		$a     = $this->property( array( 'title' => 'Alpha' ) );
		$b     = $this->property(
			array(
				'title' => 'Draft',
				'post_status' => 'draft',
			)
		);
		$page  = self::factory()->post->create();
		$r     = $this->req( 'GET', '/saved/cards', array( 'ids' => "$a,$b,$page,$a" ) );
		$this->assertSame( array( $a ), array_map( 'intval', $r->get_data()['ids'] ) );
		$this->assertStringContainsString( 'Alpha', $r->get_data()['html'] );
	}

	public function test_compare_is_capped_at_four() {
		$ids = array();
		for ( $i = 0; $i < 6; $i++ ) {
			$ids[] = $this->property( array( 'title' => "C$i" ) );
		}
		$r = $this->req( 'GET', '/saved/compare', array( 'ids' => implode( ',', $ids ) ) );
		$this->assertLessThanOrEqual( 4, substr_count( $r->get_data()['html'], 'mk-ctable__head' ) );
	}

	public function test_me_saved_needs_sign_in_and_syncs() {
		$this->assertSame( 401, $this->req( 'GET', '/me/saved' )->get_status() );
		$a = $this->property();
		$b = $this->property();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$r = $this->req(
			'POST',
			'/me/saved',
			array(
				'favs' => array( $a, $b, 999999 ),
				'compare' => array( $b ),
			)
		);
		$this->assertSame( array( $a, $b ), array_map( 'intval', $r->get_data()['favs'] ) );
		$this->assertSame( array( $b ), array_map( 'intval', $this->req( 'GET', '/me/saved' )->get_data()['compare'] ) );
	}

	public function test_listing_endpoint_returns_html() {
		$this->property(
			array(
				'title' => 'Rental A',
				'status' => 'rent',
				'price' => 50000,
				'period' => 'month',
			)
		);
		$r = $this->req(
			'GET',
			'/listing',
			array(
				'status' => 'rent',
				'cfg' => '{}',
			)
		);
		$this->assertSame( 200, $r->get_status() );
		$this->assertStringContainsString( 'Rental A', $r->get_data()['html'] );
		$this->assertStringContainsString( 'to rent', $r->get_data()['html'] );
	}

	public function test_upload_requires_a_lister() {
		$this->assertContains( $this->req( 'POST', '/upload' )->get_status(), array( 401, 403 ) );
	}
}
