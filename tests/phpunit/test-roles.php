<?php
/**
 * Roles, capabilities and the feature switches.
 *
 * @package Makaazi
 */

/**
 * @group roles
 */
class Test_Makaazi_Roles extends Makaazi_TestCase {

	/**
	 * An agent user linked to an agent profile.
	 *
	 * @return array [user ID, agent post ID]
	 */
	private function agent() {
		$uid = self::factory()->user->create( array( 'role' => 'mk_agent' ) );
		$aid = self::factory()->post->create(
			array(
				'post_type' => 'mk_agent',
				'post_title' => 'Grace Achieng',
			)
		);
		update_post_meta( $aid, '_mk_user_id', $uid );
		return array( $uid, $aid );
	}

	public function test_admins_and_editors_have_property_caps() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$ed    = self::factory()->user->create( array( 'role' => 'editor' ) );
		foreach ( Makaazi_Roles::caps() as $cap ) {
			$this->assertTrue( user_can( $admin, $cap ), $cap );
			$this->assertTrue( user_can( $ed, $cap ), $cap );
		}
	}

	public function test_agent_caps_follow_switches() {
		list( $uid ) = $this->agent();
		$this->set_settings(
			array(
				'agents_enabled' => 1,
				'agent_review' => 0,
			)
		);
		$this->assertTrue( user_can( $uid, 'publish_properties' ) );
		$this->assertFalse( user_can( $uid, 'edit_others_properties' ) );
		$this->set_settings( array( 'agent_review' => 1 ) );
		$this->assertFalse( user_can( $uid, 'publish_properties' ) );
		$this->assertTrue( user_can( $uid, 'edit_properties' ) );
		$this->set_settings( array( 'agents_enabled' => 0 ) );
		$this->assertFalse( user_can( $uid, 'edit_properties' ) );
		$this->assertFalse( user_can( $uid, 'upload_files' ) );
	}

	public function test_owner_can_list_only_when_enabled_and_approved() {
		$uid = self::factory()->user->create( array( 'role' => 'mk_owner' ) );
		$u   = get_userdata( $uid );
		$this->set_settings( array( 'owners_enabled' => 1 ) );
		$this->assertTrue( Makaazi_Accounts::can_list( $u ) );
		$this->assertFalse( user_can( $uid, 'edit_properties' ), 'Owners never get wp-admin property rights.' );
		update_user_meta( $uid, '_mk_pending', 'approval' );
		$this->assertFalse( Makaazi_Accounts::can_list( $u ) );
		delete_user_meta( $uid, '_mk_pending' );
		$this->set_settings( array( 'owners_enabled' => 0 ) );
		$this->assertFalse( Makaazi_Accounts::can_list( $u ) );
	}

	public function test_pending_accounts_cannot_sign_in() {
		$uid = self::factory()->user->create( array( 'role' => 'mk_owner' ) );
		update_user_meta( $uid, '_mk_pending', 'email' );
		$this->assertWPError( Makaazi_Accounts::block_pending( get_userdata( $uid ) ) );
		update_user_meta( $uid, '_mk_pending', 'approval' );
		$this->assertWPError( Makaazi_Accounts::block_pending( get_userdata( $uid ) ) );
		delete_user_meta( $uid, '_mk_pending' );
		$this->assertInstanceOf( 'WP_User', Makaazi_Accounts::block_pending( get_userdata( $uid ) ) );
	}

	public function test_can_manage_own_and_assigned_listings_only() {
		list( $uid, $aid ) = $this->agent();
		$owner             = self::factory()->user->create( array( 'role' => 'mk_owner' ) );
		$mine              = $this->property();
		wp_update_post(
			array(
				'ID' => $mine,
				'post_author' => $owner,
			)
		);
		$assigned = $this->property();
		update_post_meta( $assigned, '_mk_agent_id', $aid );
		$other = $this->property();

		$this->assertTrue( Makaazi_Submission::can_manage( $mine, get_userdata( $owner ) ) );
		$this->assertFalse( Makaazi_Submission::can_manage( $other, get_userdata( $owner ) ) );
		$this->set_settings( array( 'agents_enabled' => 1 ) );
		$this->assertTrue( Makaazi_Submission::can_manage( $assigned, get_userdata( $uid ) ) );
		$this->assertFalse( Makaazi_Submission::can_manage( $other, get_userdata( $uid ) ) );
		$this->set_settings( array( 'agents_enabled' => 0 ) );
		$this->assertFalse( Makaazi_Submission::can_manage( $assigned, get_userdata( $uid ) ) );
	}

	public function test_agent_lookup() {
		list( $uid, $aid ) = $this->agent();
		$this->assertSame( $aid, Makaazi_Agents::for_user( $uid ) );
		$pid = $this->property();
		update_post_meta( $pid, '_mk_agent_id', $aid );
		$this->set_settings( array( 'agents_enabled' => 1 ) );
		$this->assertSame( $aid, Makaazi_Agents::of_property( $pid ) );
	}
}
