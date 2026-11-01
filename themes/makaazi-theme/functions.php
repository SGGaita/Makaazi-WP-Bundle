<?php
/**
 * Makaazi theme setup.
 *
 * @package MakaaziTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme supports and editor styles.
 */
function makaazi_theme_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
	load_theme_textdomain( 'makaazi-theme', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'makaazi_theme_setup' );

/**
 * Front-end stylesheet.
 */
function makaazi_theme_assets() {
	$file = get_template_directory() . '/assets/css/theme.css';
	wp_enqueue_style( 'makaazi-theme', get_template_directory_uri() . '/assets/css/theme.css', array(), (string) filemtime( $file ) );
	// Sticky header: add a shadow and a slimmer bar once the page scrolls.
	wp_register_script( 'makaazi-theme', '', array(), '1', true );
	wp_enqueue_script( 'makaazi-theme' );
	wp_add_inline_script( 'makaazi-theme', "(function(){var b=document.body,on=false;function f(){var s=window.scrollY>8;if(s!==on){on=s;b.classList.toggle('mk-scrolled',s);}}window.addEventListener('scroll',f,{passive:true});f();})();" );
}
add_action( 'wp_enqueue_scripts', 'makaazi_theme_assets' );

/**
 * Pattern category.
 */
function makaazi_theme_patterns() {
	register_block_pattern_category( 'makaazi', array( 'label' => __( 'Makaazi', 'makaazi-theme' ) ) );
}
add_action( 'init', 'makaazi_theme_patterns' );

/**
 * Show the bundled logo until a Site Logo is chosen.
 *
 * @param string $html  Block HTML.
 * @param array  $block Block.
 * @return string
 */
function makaazi_theme_logo_fallback( $html, $block ) {
	if ( '' !== trim( $html ) ) {
		return $html;
	}
	$width = isset( $block['attrs']['width'] ) ? (int) $block['attrs']['width'] : 168;
	$light = ! empty( $block['attrs']['className'] ) && false !== strpos( $block['attrs']['className'], 'is-light' );
	$src   = get_template_directory_uri() . '/assets/img/' . ( $light ? 'logo-light.png' : 'logo.png' );
	return '<div class="wp-block-site-logo"><a href="' . esc_url( home_url( '/' ) ) . '" class="custom-logo-link" rel="home"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="' . $width . '" style="width:' . $width . 'px" class="custom-logo"></a></div>';
}
add_filter( 'render_block_core/site-logo', 'makaazi_theme_logo_fallback', 10, 2 );

/**
 * Remind admins to activate the plugins the templates rely on.
 */
function makaazi_theme_plugin_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$missing = array();
	if ( ! defined( 'MAKAAZI_VERSION' ) ) {
		$missing[] = 'Makaazi Core';
	}
	if ( ! defined( 'MKS_VERSION' ) ) {
		$missing[] = 'Makaazi Hero Slider';
	}
	if ( $missing ) {
		/* translators: %s: plugin names */
		echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( __( 'The Makaazi theme needs these plugins to show properties: %s. Activate them under Plugins.', 'makaazi-theme' ), implode( ', ', $missing ) ) ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'makaazi_theme_plugin_notice' );
