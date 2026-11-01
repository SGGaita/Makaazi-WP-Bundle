<?php
/**
 * Plugin Name:       Makaazi Core
 * Plugin URI:        https://makaazihomes.co.ke/
 * Description:       Property listings for the Makaazi Homes template: properties, taxonomies, settings, enquiries and the listing shortcodes.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Makaazi Homes
 * License:           GPL-2.0-or-later
 * Text Domain:       makaazi
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

define( 'MAKAAZI_VERSION', '0.1.0' );
define( 'MAKAAZI_FILE', __FILE__ );
define( 'MAKAAZI_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAKAAZI_URL', plugin_dir_url( __FILE__ ) );

require_once MAKAAZI_DIR . 'includes/functions.php';
require_once MAKAAZI_DIR . 'includes/class-post-types.php';
require_once MAKAAZI_DIR . 'includes/class-meta.php';
require_once MAKAAZI_DIR . 'includes/class-query.php';
require_once MAKAAZI_DIR . 'includes/class-related.php';
require_once MAKAAZI_DIR . 'includes/class-render.php';
require_once MAKAAZI_DIR . 'includes/class-shortcodes.php';
require_once MAKAAZI_DIR . 'includes/class-rest.php';
require_once MAKAAZI_DIR . 'includes/class-enquiries.php';
require_once MAKAAZI_DIR . 'includes/class-assets.php';
require_once MAKAAZI_DIR . 'includes/class-shortcode-docs.php';

if ( is_admin() ) {
	require_once MAKAAZI_DIR . 'admin/class-admin.php';
	require_once MAKAAZI_DIR . 'admin/class-meta-box.php';
	require_once MAKAAZI_DIR . 'admin/class-settings.php';
	require_once MAKAAZI_DIR . 'admin/class-shortcodes-page.php';
	require_once MAKAAZI_DIR . 'admin/class-demo.php';
}

Makaazi_Post_Types::init();
Makaazi_Meta::init();
Makaazi_Related::init();
Makaazi_Shortcodes::init();
Makaazi_Rest::init();
Makaazi_Enquiries::init();
Makaazi_Assets::init();

if ( is_admin() ) {
	Makaazi_Admin::init();
	Makaazi_Meta_Box::init();
	Makaazi_Settings::init();
	Makaazi_Shortcodes_Page::init();
	Makaazi_Demo::init();
}

/**
 * Activation: register types, seed default terms, flush permalinks.
 */
function makaazi_activate() {
	Makaazi_Post_Types::register();
	Makaazi_Post_Types::seed_terms();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'makaazi_activate' );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
