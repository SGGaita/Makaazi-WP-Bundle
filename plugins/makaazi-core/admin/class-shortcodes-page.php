<?php
/**
 * Properties › Shortcodes reference page.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reference page with copy buttons.
 */
class Makaazi_Shortcodes_Page {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_submenu_page( 'edit.php?post_type=property', __( 'Shortcodes', 'makaazi' ), __( 'Shortcodes', 'makaazi' ), 'edit_posts', 'makaazi-shortcodes', array( __CLASS__, 'page' ) );
	}

	/**
	 * Render.
	 */
	public static function page() {
		$docs   = Makaazi_Shortcode_Docs::all();
		$groups = array();
		foreach ( $docs as $d ) {
			$groups[ $d['group'] ][] = $d;
		}
		echo '<div class="wrap mk-ref"><h1>' . esc_html__( 'Shortcodes', 'makaazi' ) . '</h1>';
		echo '<p class="mk-ref__intro">' . esc_html__( 'Paste a shortcode into a Shortcode block, a classic editor page or a page builder text widget. Attributes are optional; values shown are the defaults. Click Copy to copy an example.', 'makaazi' ) . '</p>';
		echo '<nav class="mk-ref__toc">';
		foreach ( $docs as $d ) {
			echo '<a href="#sc-' . esc_attr( $d['tag'] ) . '"><code>[' . esc_html( $d['tag'] ) . ']</code></a>';
		}
		echo '</nav>';
		foreach ( $groups as $g => $items ) {
			echo '<h2 class="mk-ref__group">' . esc_html( $g ) . '</h2>';
			foreach ( $items as $d ) {
				echo '<section class="mk-ref__item" id="sc-' . esc_attr( $d['tag'] ) . '"><h3><code>[' . esc_html( $d['tag'] ) . ']</code></h3><p>' . esc_html( $d['desc'] ) . '</p>';
				if ( $d['atts'] ) {
					echo '<table class="widefat striped mk-ref__atts"><thead><tr><th>' . esc_html__( 'Attribute', 'makaazi' ) . '</th><th>' . esc_html__( 'Default', 'makaazi' ) . '</th><th>' . esc_html__( 'Values', 'makaazi' ) . '</th></tr></thead><tbody>';
					foreach ( $d['atts'] as $k => $v ) {
						echo '<tr><td><code>' . esc_html( $k ) . '</code></td><td>' . ( '' !== $v[0] ? '<code>' . esc_html( $v[0] ) . '</code>' : '—' ) . '</td><td>' . esc_html( $v[1] ) . '</td></tr>';
					}
					echo '</tbody></table>';
				}
				foreach ( $d['examples'] as $ex ) {
					echo '<div class="mk-ref__ex"><code>' . esc_html( $ex ) . '</code><button type="button" class="button button-small" data-mk-copy="' . esc_attr( $ex ) . '">' . esc_html__( 'Copy', 'makaazi' ) . '</button></div>';
				}
				echo '</section>';
			}
		}
		echo '</div>';
	}
}
