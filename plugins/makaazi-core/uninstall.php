<?php
/**
 * Uninstall: remove only data that can be rebuilt. Listings, agents, enquiries, settings and
 * user data are kept, so reinstalling the plugin brings the site back as it was.
 *
 * @package Makaazi
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mk_index" ); // phpcs:ignore WordPress.DB
foreach ( array( 'makaazi_index_ver', 'makaazi_index_ready', 'makaazi_index_cursor', 'makaazi_flush_rules' ) as $makaazi_opt ) {
	delete_option( $makaazi_opt );
}
wp_clear_scheduled_hook( 'makaazi_index_build' );
