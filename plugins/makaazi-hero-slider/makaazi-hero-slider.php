<?php
/**
 * Plugin Name:       Makaazi Hero Slider
 * Plugin URI:        https://ascensiondynamics.co/
 * Description:       Hero sliders with fade, slide and Ken Burns effects and a configurable Buy/Rent property search bar. Place with [makaazi_slider].
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Ascension Dynamics
 * Author URI:        https://ascensiondynamics.co/
 * License:           GPL-2.0-or-later
 * Text Domain:       makaazi-slider
 * Domain Path:       /languages
 *
 * @package MakaaziSlider
 */

defined( 'ABSPATH' ) || exit;

define( 'MKS_VERSION', '1.0.0' );
define( 'MKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MKS_URL', plugin_dir_url( __FILE__ ) );

require_once MKS_DIR . 'includes/class-mks-slider.php';
require_once MKS_DIR . 'includes/class-mks-render.php';
if ( is_admin() ) {
	require_once MKS_DIR . 'includes/class-mks-admin.php';
}

MKS_Slider::init();
MKS_Render::init();
if ( is_admin() ) {
	MKS_Admin::init();
}

/**
 * Translations: wp-content/languages/plugins/{domain}-{locale}.mo first, then this plugin's languages/ folder.
 */
function mks_load_textdomain() {
	load_plugin_textdomain( 'makaazi-slider', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'mks_load_textdomain', 0 );
