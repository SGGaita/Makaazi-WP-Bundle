<?php
/**
 * Property post type and taxonomies.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers content types.
 */
class Makaazi_Post_Types {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_action( 'init', array( __CLASS__, 'maybe_seed' ), 20 );
		add_action( 'after_setup_theme', array( __CLASS__, 'image_sizes' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'classic_editor' ), 10, 2 );
	}

	/**
	 * Add default terms introduced in later versions (e.g. Short Stay) on existing sites.
	 */
	public static function maybe_seed() {
		if ( (int) get_option( 'makaazi_terms_ver' ) >= 2 ) {
			return;
		}
		self::seed_terms();
		update_option( 'makaazi_terms_ver', 2 );
	}

	/**
	 * Register the property post type, enquiries and taxonomies.
	 */
	public static function register() {
		register_post_type(
			'property',
			array(
				'labels'          => array(
					'name'               => __( 'Properties', 'makaazi' ),
					'singular_name'      => __( 'Property', 'makaazi' ),
					'add_new'            => __( 'Add New', 'makaazi' ),
					'add_new_item'       => __( 'Add New Property', 'makaazi' ),
					'edit_item'          => __( 'Edit Property', 'makaazi' ),
					'new_item'           => __( 'New Property', 'makaazi' ),
					'view_item'          => __( 'View Property', 'makaazi' ),
					'search_items'       => __( 'Search Properties', 'makaazi' ),
					'not_found'          => __( 'No properties found.', 'makaazi' ),
					'not_found_in_trash' => __( 'No properties found in Trash.', 'makaazi' ),
					'all_items'          => __( 'All Properties', 'makaazi' ),
					'menu_name'          => __( 'Properties', 'makaazi' ),
				),
				'public'          => true,
				'has_archive'     => 'properties',
				'rewrite'         => array(
					'slug'       => 'property',
					'with_front' => false,
				),
				'menu_icon'       => 'dashicons-building',
				'menu_position'   => 5,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'custom-fields' ),
				'show_in_rest'    => true,
				'capability_type' => array( 'property', 'properties' ),
				'map_meta_cap'    => true,
			)
		);

		$taxes = array(
			'property_status'   => array( __( 'Statuses', 'makaazi' ), __( 'Status', 'makaazi' ), 'status', false ),
			'property_use'      => array( __( 'Uses', 'makaazi' ), __( 'Use', 'makaazi' ), 'use', false ),
			'property_type'     => array( __( 'Types', 'makaazi' ), __( 'Type', 'makaazi' ), 'property-type', false ),
			'property_category' => array( __( 'Categories', 'makaazi' ), __( 'Category', 'makaazi' ), 'property-category', true ),
			'property_location' => array( __( 'Locations', 'makaazi' ), __( 'Location', 'makaazi' ), 'location', true ),
			'property_feature'  => array( __( 'Features', 'makaazi' ), __( 'Feature', 'makaazi' ), 'feature', true ),
		);
		foreach ( $taxes as $tax => $d ) {
			register_taxonomy(
				$tax,
				'property',
				array(
					'labels'            => array(
						'name'          => $d[0],
						'singular_name' => $d[1],
						'menu_name'     => $d[0],
						/* translators: %s: taxonomy singular name */
						'add_new_item'  => sprintf( __( 'Add New %s', 'makaazi' ), $d[1] ),
						/* translators: %s: taxonomy singular name */
						'edit_item'     => sprintf( __( 'Edit %s', 'makaazi' ), $d[1] ),
						/* translators: %s: taxonomy plural name */
						'search_items'  => sprintf( __( 'Search %s', 'makaazi' ), $d[0] ),
						/* translators: %s: taxonomy plural name */
						'all_items'     => sprintf( __( 'All %s', 'makaazi' ), $d[0] ),
					),
					'hierarchical'      => $d[3],
					'public'            => true,
					'show_in_rest'      => true,
					'show_admin_column' => in_array( $tax, array( 'property_status', 'property_type', 'property_location' ), true ),
					'rewrite'           => array(
						'slug'         => $d[2],
						'with_front'   => false,
						'hierarchical' => 'property_location' === $tax,
					),
					'meta_box_cb'       => in_array( $tax, array( 'property_status', 'property_use', 'property_type', 'property_location' ), true ) ? array( __CLASS__, 'radio_box' ) : null,
					// Radio boxes post term IDs, so sanitize them as IDs for flat taxonomies too.
					'meta_box_sanitize_cb' => 'taxonomy_meta_box_sanitize_cb_checkboxes',
					'capabilities'      => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'manage_categories',
						'delete_terms' => 'manage_categories',
						'assign_terms' => 'edit_properties',
					),
				)
			);
		}
	}

	/**
	 * Property images.
	 */
	public static function image_sizes() {
		add_image_size( 'makaazi-card', 720, 540, true );
		add_image_size( 'makaazi-wide', 1600, 1000, true );
	}

	/**
	 * Use the classic editor screen for properties so the Property Details box sits under the title.
	 *
	 * @param bool   $use       Whether to use the block editor.
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public static function classic_editor( $use, $post_type ) {
		return 'property' === $post_type ? false : $use;
	}

	/**
	 * Single-choice taxonomy box (radio buttons, hierarchy indented).
	 * Use (residential/commercial) allows several choices so land can be both.
	 *
	 * @param WP_Post $post Post.
	 * @param array   $box  Box args.
	 */
	public static function radio_box( $post, $box ) {
		$tax      = $box['args']['taxonomy'];
		$multi    = 'property_use' === $tax;
		$terms    = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		$current  = wp_get_object_terms( $post->ID, $tax, array( 'fields' => 'ids' ) );
		$name     = 'tax_input[' . $tax . '][]';
		$by_parent = array();
		foreach ( (array) $terms as $t ) {
			$by_parent[ $t->parent ][] = $t;
		}
		echo '<div class="mk-radio-tax" style="max-height:240px;overflow:auto">';
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
		$walk = function ( $parent, $depth ) use ( &$walk, $by_parent, $current, $name, $multi ) {
			if ( empty( $by_parent[ $parent ] ) ) {
				return;
			}
			foreach ( $by_parent[ $parent ] as $t ) {
				printf(
					'<label style="display:block;margin:3px 0 3px %1$dpx"><input type="%2$s" name="%3$s" value="%4$d" %5$s> %6$s</label>',
					(int) ( $depth * 16 ),
					$multi ? 'checkbox' : 'radio',
					esc_attr( $name ),
					(int) $t->term_id,
					checked( in_array( $t->term_id, $current, true ), true, false ),
					esc_html( $t->name )
				);
				$walk( $t->term_id, $depth + 1 );
			}
		};
		$walk( 0, 0 );
		if ( ! $terms ) {
			echo '<p>' . esc_html__( 'No terms yet.', 'makaazi' ) . '</p>';
		}
		$tx = get_taxonomy( $tax );
		if ( $tx && current_user_can( $tx->cap->manage_terms ) ) {
			echo '<p><a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=' . $tax . '&post_type=property' ) ) . '">' . esc_html__( 'Manage', 'makaazi' ) . '</a></p>';
		}
		echo '</div>';
	}

	/**
	 * Default terms created on activation.
	 */
	public static function seed_terms() {
		$seed = array(
			'property_status' => array(
				'sale' => __( 'For Sale', 'makaazi' ),
				'rent' => __( 'For Rent', 'makaazi' ),
				'stay' => __( 'Short Stay', 'makaazi' ),
			),
			'property_use'    => array(
				'residential' => __( 'Residential', 'makaazi' ),
				'commercial' => __( 'Commercial', 'makaazi' ),
			),
			'property_type'   => array(
				'apartment'  => __( 'Apartment', 'makaazi' ),
				'maisonette' => __( 'Maisonette', 'makaazi' ),
				'townhouse'  => __( 'Townhouse', 'makaazi' ),
				'villa'      => __( 'Villa', 'makaazi' ),
				'bungalow'   => __( 'Bungalow', 'makaazi' ),
				'office'     => __( 'Office', 'makaazi' ),
				'shop'       => __( 'Shop', 'makaazi' ),
				'warehouse'  => __( 'Warehouse', 'makaazi' ),
				'land'       => __( 'Land', 'makaazi' ),
			),
		);
		foreach ( $seed as $tax => $terms ) {
			foreach ( $terms as $slug => $name ) {
				if ( ! term_exists( $slug, $tax ) ) {
					wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
				}
			}
		}
	}
}
