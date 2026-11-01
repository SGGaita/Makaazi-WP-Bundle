<?php
/**
 * PHPUnit bootstrap: load the WordPress test library, then the Makaazi plugins and theme.
 *
 * Set WP_TESTS_DIR to the WordPress test library (tests/phpunit from wordpress-develop).
 * tests/bin/install-wp-tests.sh prepares one.
 *
 * @package Makaazi
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Run tests/bin/install-wp-tests.sh first.\n"; // phpcs:ignore
	exit( 1 );
}

// Yoast PHPUnit Polyfills (required by the WordPress test library).
$_polyfills = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( ! $_polyfills && file_exists( dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' ) ) {
	$_polyfills = dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills';
}
if ( $_polyfills && ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_polyfills );
}

require_once $_tests_dir . '/includes/functions.php';

define( 'MAKAAZI_TESTS_ROOT', dirname( __DIR__, 2 ) );

tests_add_filter(
	'muplugins_loaded',
	function () {
		require MAKAAZI_TESTS_ROOT . '/plugins/makaazi-core/makaazi-core.php';
		require MAKAAZI_TESTS_ROOT . '/plugins/makaazi-hero-slider/makaazi-hero-slider.php';
	}
);

// Register the Makaazi theme folder so tests can switch to it.
tests_add_filter(
	'setup_theme',
	function () {
		register_theme_directory( MAKAAZI_TESTS_ROOT . '/themes' );
	}
);

require $_tests_dir . '/includes/bootstrap.php';
require __DIR__ . '/class-makaazi-testcase.php';

// Activation work that a real install does once.
Makaazi_Post_Types::register();
Makaazi_Post_Types::seed_terms();
Makaazi_Roles::install();
Makaazi_Agents::register();
Makaazi_Index::install();
