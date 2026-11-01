<?php
/**
 * E2E site setup (run with: wp eval-file): demo content, a test agent account and settings.
 *
 * @package Makaazi
 */

require_once MAKAAZI_DIR . 'admin/class-demo.php';
wp_set_current_user( 1 );
if ( ! get_option( 'makaazi_demo_imported' ) ) {
	$makaazi_r = Makaazi_Demo::import();
	if ( is_wp_error( $makaazi_r ) ) {
		WP_CLI::error( $makaazi_r->get_error_message() );
	}
}
$makaazi_s                       = get_option( 'makaazi_settings', array() );
$makaazi_s['owner_registration'] = 'open';
$makaazi_s['owner_email_verify'] = 0;
$makaazi_s['stays_enabled']      = 1;
update_option( 'makaazi_settings', $makaazi_s );
if ( ! username_exists( 'e2e-owner' ) ) {
	wp_insert_user(
		array(
			'user_login' => 'e2e-owner',
			'user_pass'  => 'e2e-owner-pass',
			'user_email' => 'owner@example.org',
			'role'       => 'mk_owner',
		)
	);
}
Makaazi_Index::install();
flush_rewrite_rules();
WP_CLI::success( 'Makaazi E2E site ready.' );
