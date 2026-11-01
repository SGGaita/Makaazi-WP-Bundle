<?php
/**
 * Admin list screen, menus and term fields.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * General admin.
 */
class Makaazi_Admin {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_filter( 'manage_property_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_property_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'manage_edit-property_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_query' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filters' ) );
		add_action( 'wp_ajax_makaazi_feature', array( __CLASS__, 'ajax_feature' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'property_location_add_form_fields', array( __CLASS__, 'term_image_add' ) );
		add_action( 'property_location_edit_form_fields', array( __CLASS__, 'term_image_edit' ) );
		add_action( 'created_property_location', array( __CLASS__, 'term_image_save' ) );
		add_action( 'edited_property_location', array( __CLASS__, 'term_image_save' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MAKAAZI_FILE ), array( __CLASS__, 'action_links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'welcome' ) );
	}

	/**
	 * Admin assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function assets( $hook ) {
		$screen = get_current_screen();
		$v      = MAKAAZI_VERSION . '.' . (int) filemtime( MAKAAZI_DIR . 'assets/js/admin.js' );
		wp_register_style( 'makaazi-admin', MAKAAZI_URL . 'assets/css/admin.css', array(), $v );
		wp_register_script( 'makaazi-admin', MAKAAZI_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), $v, true );
		$is_ours = $screen && ( 'property' === $screen->post_type || false !== strpos( (string) $hook, 'makaazi' ) || 'property_location' === $screen->taxonomy || 'mk_slider' === $screen->post_type );
		if ( ! $is_ours ) {
			return;
		}
		wp_enqueue_style( 'makaazi-admin' );
		wp_enqueue_script( 'makaazi-admin' );
		wp_localize_script(
			'makaazi-admin',
			'MAKAAZI_ADMIN',
			array(
				'ajax'   => admin_url( 'admin-ajax.php' ),
				'nonce'  => wp_create_nonce( 'makaazi_admin' ),
				'tiles'  => makaazi_settings( 'map_tiles' ),
				'center' => array_map( 'floatval', explode( ',', makaazi_settings( 'map_center' ) ) ),
				'i18n'   => array(
					'choose'   => __( 'Choose photos', 'makaazi' ),
					'use'      => __( 'Add to gallery', 'makaazi' ),
					'remove'   => __( 'Remove', 'makaazi' ),
					'cover'    => __( 'Cover', 'makaazi' ),
					'notfound' => __( 'No match found. Drag the pin instead.', 'makaazi' ),
					'copied'   => __( 'Copied', 'makaazi' ),
				),
			)
		);
		if ( $screen && ( 'property' === $screen->post_type || 'property_location' === $screen->taxonomy || 'mk_slider' === $screen->post_type ) ) {
			wp_enqueue_media();
		}
		if ( $screen && 'property' === $screen->post_type && 'post' === $screen->base ) {
			wp_enqueue_style( 'leaflet', MAKAAZI_URL . 'assets/vendor/leaflet/leaflet.css', array(), '1.9.4' );
			wp_enqueue_script( 'leaflet', MAKAAZI_URL . 'assets/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
		}
	}

	/**
	 * Columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function columns( $cols ) {
		$new = array(
			'cb'         => $cols['cb'],
			'mk_thumb'   => '<span class="screen-reader-text">' . esc_html__( 'Photo', 'makaazi' ) . '</span>',
			'title'      => $cols['title'],
			'mk_price'   => __( 'Price', 'makaazi' ),
		);
		foreach ( $cols as $k => $v ) {
			if ( 0 === strpos( $k, 'taxonomy-' ) ) {
				$new[ $k ] = $v;
			}
		}
		$new['mk_featured'] = '<span class="dashicons dashicons-star-filled" title="' . esc_attr__( 'Featured', 'makaazi' ) . '"></span><span class="screen-reader-text">' . esc_html__( 'Featured', 'makaazi' ) . '</span>';
		$new['mk_leads']    = __( 'Enquiries', 'makaazi' );
		$new['date']        = $cols['date'];
		return $new;
	}

	/**
	 * Column values.
	 *
	 * @param string $col Column.
	 * @param int    $id  Post ID.
	 */
	public static function column( $col, $id ) {
		switch ( $col ) {
			case 'mk_thumb':
				$g = makaazi_gallery_ids( $id );
				echo $g ? wp_get_attachment_image( $g[0], array( 72, 54 ), false, array( 'class' => 'mk-thumb' ) ) : '<span class="mk-thumb mk-thumb--empty"></span>'; // phpcs:ignore
				break;
			case 'mk_price':
				echo wp_kses_post( makaazi_price( $id, false ) );
				$a = makaazi_meta( $id, 'availability', 'available' );
				if ( 'available' !== $a ) {
					echo '<br><span class="mk-avail mk-avail--' . esc_attr( $a ) . '">' . esc_html( ucwords( str_replace( '_', ' ', $a ) ) ) . '</span>';
				}
				echo '<br><span class="mk-ref">' . esc_html( makaazi_meta( $id, 'ref' ) ) . '</span>';
				break;
			case 'mk_featured':
				$on = (bool) makaazi_meta( $id, 'featured' );
				printf(
					'<button type="button" class="mk-star%1$s" data-id="%2$d" aria-pressed="%3$s" aria-label="%4$s"><span class="dashicons dashicons-star-%5$s"></span></button>',
					$on ? ' is-on' : '',
					(int) $id,
					$on ? 'true' : 'false',
					esc_attr__( 'Toggle featured', 'makaazi' ),
					$on ? 'filled' : 'empty'
				);
				break;
			case 'mk_leads':
				$n = (int) makaazi_meta( $id, 'leads', 0 );
				echo $n ? '<a href="' . esc_url( admin_url( 'edit.php?post_type=mk_enquiry&mk_property=' . $id ) ) . '">' . (int) $n . '</a>' : '—';
				break;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function sortable( $cols ) {
		$cols['mk_price'] = 'mk_price';
		return $cols;
	}

	/**
	 * Admin sort by price; enquiry list filter by property.
	 *
	 * @param WP_Query $q Query.
	 */
	public static function sort_query( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( 'mk_price' === $q->get( 'orderby' ) ) {
			$q->set( 'meta_key', '_mk_price' );
			$q->set( 'orderby', 'meta_value_num' );
		}
		if ( 'mk_enquiry' === $q->get( 'post_type' ) && ! empty( $_GET['mk_property'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$q->set( 'meta_key', '_mk_property_id' );
			$q->set( 'meta_value', absint( $_GET['mk_property'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	/**
	 * Taxonomy dropdown filters on the list screen.
	 *
	 * @param string $post_type Post type.
	 */
	public static function filters( $post_type ) {
		if ( 'property' !== $post_type ) {
			return;
		}
		foreach ( array( 'property_status', 'property_type', 'property_location' ) as $tax ) {
			$t = get_taxonomy( $tax );
			wp_dropdown_categories(
				array(
					/* translators: %s: taxonomy name */
					'show_option_all' => sprintf( __( 'All %s', 'makaazi' ), strtolower( $t->labels->name ) ),
					'taxonomy'        => $tax,
					'name'            => $tax,
					'value_field'     => 'slug',
					'selected'        => isset( $_GET[ $tax ] ) ? sanitize_title( wp_unslash( $_GET[ $tax ] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
					'hierarchical'    => true,
					'hide_empty'      => false,
				)
			);
		}
	}

	/**
	 * Toggle featured via AJAX.
	 */
	public static function ajax_feature() {
		check_ajax_referer( 'makaazi_admin', 'nonce' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( null, 403 );
		}
		$on = makaazi_meta( $id, 'featured' ) ? 0 : 1;
		update_post_meta( $id, '_mk_featured', $on );
		makaazi_bump_cache();
		wp_send_json_success( array( 'featured' => $on ) );
	}

	/**
	 * Location image field (add screen).
	 */
	public static function term_image_add() {
		echo '<div class="form-field"><label>' . esc_html__( 'Image', 'makaazi' ) . '</label>' . self::term_image_control( 0 ) . '<p>' . esc_html__( 'Shown on "Browse by location" tiles. If empty, a photo of a property in this area is used.', 'makaazi' ) . '</p></div>';
	}

	/**
	 * Location image field (edit screen).
	 *
	 * @param WP_Term $term Term.
	 */
	public static function term_image_edit( $term ) {
		echo '<tr class="form-field"><th scope="row"><label>' . esc_html__( 'Image', 'makaazi' ) . '</label></th><td>' . self::term_image_control( (int) get_term_meta( $term->term_id, 'mk_image', true ) ) . '<p class="description">' . esc_html__( 'Shown on "Browse by location" tiles.', 'makaazi' ) . '</p></td></tr>';
	}

	/**
	 * Image picker markup.
	 *
	 * @param int $id Attachment ID.
	 * @return string
	 */
	private static function term_image_control( $id ) {
		$img = $id ? wp_get_attachment_image( $id, 'thumbnail' ) : '';
		return wp_nonce_field( 'mk_term_image', 'mk_term_image_nonce', true, false ) . '<div class="mk-term-image" data-mk-image-pick><input type="hidden" name="mk_image" value="' . (int) $id . '"><span class="mk-term-image__preview">' . $img . '</span> <button type="button" class="button" data-pick>' . esc_html__( 'Choose image', 'makaazi' ) . '</button> <button type="button" class="button-link" data-clear>' . esc_html__( 'Remove', 'makaazi' ) . '</button></div>';
	}

	/**
	 * Save location image.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function term_image_save( $term_id ) {
		if ( ! isset( $_POST['mk_term_image_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mk_term_image_nonce'] ) ), 'mk_term_image' ) ) {
			return;
		}
		if ( isset( $_POST['mk_image'] ) ) {
			update_term_meta( $term_id, 'mk_image', absint( $_POST['mk_image'] ) );
		}
	}

	/**
	 * Plugin links.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=property&page=makaazi-settings' ) ) . '">' . esc_html__( 'Settings', 'makaazi' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=property&page=makaazi-demo' ) ) . '">' . esc_html__( 'Demo content', 'makaazi' ) . '</a>'
		);
		return $links;
	}

	/**
	 * First-run notice pointing to the demo importer.
	 */
	public static function welcome() {
		if ( get_option( 'makaazi_demo_imported' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins', 'edit-property' ), true ) ) {
			return;
		}
		echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Makaazi is ready.', 'makaazi' ) . '</strong> ' . esc_html__( 'Import the demo listings, slider and home page to see everything working, or start adding your own properties.', 'makaazi' ) . ' <a class="button button-primary" href="' . esc_url( admin_url( 'edit.php?post_type=property&page=makaazi-demo' ) ) . '">' . esc_html__( 'Import demo content', 'makaazi' ) . '</a></p></div>';
	}
}
