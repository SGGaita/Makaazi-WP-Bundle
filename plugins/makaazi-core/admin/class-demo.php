<?php
/**
 * Demo content importer: Properties › Demo Content.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Imports and removes the sample Nairobi listings, photos, hero slider, pages and menu.
 */
class Makaazi_Demo {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'admin_post_makaazi_demo_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_post_makaazi_demo_remove', array( __CLASS__, 'handle_remove' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_submenu_page( 'edit.php?post_type=property', __( 'Demo Content', 'makaazi' ), __( 'Demo Content', 'makaazi' ), 'manage_options', 'makaazi-demo', array( __CLASS__, 'page' ) );
	}

	/**
	 * Page.
	 */
	public static function page() {
		$imported = get_option( 'makaazi_demo_imported' );
		$msg      = isset( $_GET['mk_msg'] ) ? sanitize_key( $_GET['mk_msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="wrap mk-demo"><h1>' . esc_html__( 'Demo Content', 'makaazi' ) . '</h1>';
		if ( 'imported' === $msg ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Demo content imported.', 'makaazi' ) . ' <a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'View the home page', 'makaazi' ) . '</a></p></div>';
		} elseif ( 'removed' === $msg ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Demo content removed.', 'makaazi' ) . '</p></div>';
		}
		echo '<div class="card"><h2>' . esc_html__( 'Sample listings for Nairobi, Kiambu, Kajiado and Mombasa', 'makaazi' ) . '</h2><p>' . esc_html__( 'Importing creates:', 'makaazi' ) . '</p><ul>'
			. '<li>' . esc_html__( '15 properties with photos, prices in KES, features and approximate map locations', 'makaazi' ) . '</li>'
			. '<li>' . esc_html__( 'Locations, types, categories and features', 'makaazi' ) . '</li>'
			. '<li>' . esc_html__( 'A three-slide hero slider with the Buy/Rent search (needs Makaazi Hero Slider)', 'makaazi' ) . '</li>'
			. '<li>' . esc_html__( 'Home and Contact pages built from shortcodes, set as the front page, and a main menu', 'makaazi' ) . '</li>'
			. '</ul><p>' . esc_html__( 'Photos are illustrations. Running the import again skips anything already imported.', 'makaazi' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'makaazi_demo' );
		echo '<input type="hidden" name="action" value="makaazi_demo_import"><p><button class="button button-primary button-hero">' . esc_html( $imported ? __( 'Import again (skips existing)', 'makaazi' ) : __( 'Import demo content', 'makaazi' ) ) . '</button></p></form></div>';
		if ( $imported ) {
			echo '<div class="card"><h2>' . esc_html__( 'Remove demo content', 'makaazi' ) . '</h2><p>' . esc_html__( 'Deletes only the items the importer created, including their photos. Your own properties and pages are kept.', 'makaazi' ) . '</p>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'makaazi_demo' );
			echo '<input type="hidden" name="action" value="makaazi_demo_remove"><p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'I understand this permanently deletes the demo items.', 'makaazi' ) . '</label></p><p><button class="button button-link-delete">' . esc_html__( 'Remove demo content', 'makaazi' ) . '</button></p></form></div>';
		}
		echo '</div>';
	}

	/**
	 * Import request.
	 */
	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to import demo content.', 'makaazi' ) );
		}
		check_admin_referer( 'makaazi_demo' );
		self::import();
		wp_safe_redirect( admin_url( 'edit.php?post_type=property&page=makaazi-demo&mk_msg=imported' ) );
		exit;
	}

	/**
	 * Remove request.
	 */
	public static function handle_remove() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to remove demo content.', 'makaazi' ) );
		}
		check_admin_referer( 'makaazi_demo' );
		self::remove();
		wp_safe_redirect( admin_url( 'edit.php?post_type=property&page=makaazi-demo&mk_msg=removed' ) );
		exit;
	}

	/**
	 * Find an existing demo item.
	 *
	 * @param string $key       Demo key.
	 * @param string $post_type Post type.
	 * @return int
	 */
	private static function existing( $key, $post_type ) {
		$q = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_mk_demo', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return $q ? (int) $q[0] : 0;
	}

	/**
	 * Import a bundled image once.
	 *
	 * @param string $file File name in demo/images.
	 * @param string $alt  Alt text.
	 * @return int Attachment ID.
	 */
	private static function image( $file, $alt = '' ) {
		static $cache = array();
		if ( isset( $cache[ $file ] ) ) {
			return $cache[ $file ];
		}
		$found = self::existing( 'img:' . $file, 'attachment' );
		if ( $found ) {
			return $cache[ $file ] = $found;
		}
		$src = MAKAAZI_DIR . 'demo/images/' . $file;
		if ( ! file_exists( $src ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$up = wp_upload_bits( 'makaazi-' . $file, null, file_get_contents( $src ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! empty( $up['error'] ) ) {
			return 0;
		}
		$type = wp_check_filetype( $up['file'] );
		$id   = wp_insert_attachment(
			array(
				'post_mime_type' => $type['type'],
				'post_title'     => ucwords( str_replace( array( '-', '.jpg', '.png' ), array( ' ', '', '' ), $file ) ),
				'post_status'    => 'inherit',
			),
			$up['file']
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
		update_post_meta( $id, '_mk_demo', 'img:' . $file );
		if ( $alt ) {
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		}
		return $cache[ $file ] = $id;
	}

	/**
	 * Get or create a term, remembering what the demo created.
	 *
	 * @param string $name     Name.
	 * @param string $slug     Slug.
	 * @param string $taxonomy Taxonomy.
	 * @param int    $parent   Parent term ID.
	 * @return int
	 */
	private static function term( $name, $slug, $taxonomy, $parent = 0 ) {
		$t = get_term_by( 'slug', $slug, $taxonomy );
		if ( $t ) {
			return (int) $t->term_id;
		}
		$r = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug, 'parent' => $parent ) );
		if ( is_wp_error( $r ) ) {
			return 0;
		}
		$made   = (array) get_option( 'makaazi_demo_terms', array() );
		$made[] = array( $taxonomy, (int) $r['term_id'] );
		update_option( 'makaazi_demo_terms', $made, false );
		return (int) $r['term_id'];
	}

	/**
	 * Run the import.
	 */
	public static function import() {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		wp_raise_memory_limit( 'admin' );
		$data = json_decode( (string) file_get_contents( MAKAAZI_DIR . 'demo/demo.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $data ) {
			return;
		}
		Makaazi_Post_Types::seed_terms();
		$area_ids = array();
		foreach ( $data['locations'] as $county => $c ) {
			$cid = self::term( $c['name'], $county, 'property_location' );
			foreach ( $c['areas'] as $slug => $name ) {
				$area_ids[ $slug ] = self::term( $name, $slug, 'property_location', $cid );
			}
		}
		foreach ( $data['categories'] as $slug => $name ) {
			self::term( $name, $slug, 'property_category' );
		}
		foreach ( $data['features'] as $slug => $name ) {
			self::term( $name, $slug, 'property_feature' );
		}
		foreach ( $data['locImg'] as $slug => $file ) {
			if ( isset( $area_ids[ $slug ] ) && ! get_term_meta( $area_ids[ $slug ], 'mk_image', true ) ) {
				update_term_meta( $area_ids[ $slug ], 'mk_image', self::image( $file ) );
			}
		}

		$now = time();
		foreach ( $data['props'] as $p ) {
			if ( self::existing( 'prop:' . $p['key'], 'property' ) ) {
				continue;
			}
			$date = gmdate( 'Y-m-d H:i:s', $now - (int) $p['age'] * DAY_IN_SECONDS - 3600 );
			$id   = wp_insert_post(
				array(
					'post_type'     => 'property',
					'post_status'   => 'publish',
					'post_title'    => $p['title'],
					'post_content'  => $p['desc'],
					'post_excerpt'  => wp_trim_words( $p['desc'], 24 ),
					'post_date_gmt' => $date,
					'post_date'     => get_date_from_gmt( $date ),
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_mk_demo', 'prop:' . $p['key'] );
			wp_set_object_terms( $id, $p['status'], 'property_status' );
			wp_set_object_terms( $id, $p['type'], 'property_type' );
			wp_set_object_terms( $id, $p['use'], 'property_use' );
			wp_set_object_terms( $id, $p['cats'], 'property_category' );
			wp_set_object_terms( $id, $p['features'], 'property_feature' );
			if ( isset( $area_ids[ $p['loc'] ] ) ) {
				wp_set_object_terms( $id, array( $area_ids[ $p['loc'] ] ), 'property_location' );
			}
			$gallery = array();
			foreach ( $p['photos'] as $i => $file ) {
				$aid = self::image( $file );
				if ( $aid && ! in_array( $aid, $gallery, true ) ) {
					$gallery[] = $aid;
				}
			}
			$meta = array(
				'_mk_price'            => $p['price'],
				'_mk_price_period'     => $p['period'],
				'_mk_price_on_request' => (int) $p['por'],
				'_mk_availability'     => 'available',
				'_mk_area_sqm'         => $p['area'],
				'_mk_bedrooms'         => $p['bd'],
				'_mk_bathrooms'        => $p['ba'],
				'_mk_parking'          => $p['pk'],
				'_mk_featured'         => (int) $p['featured'],
				'_mk_gallery'          => $gallery,
				'_mk_location_label'   => $p['label'],
				'_mk_lat'              => $p['lat'],
				'_mk_lng'              => $p['lng'],
				'_mk_map_radius'       => 500,
				'_mk_exact_pin'        => in_array( $p['type'], array( 'office', 'shop' ), true ) ? 1 : 0,
				'_mk_attributes'       => $p['attrs'] ? $p['attrs'] : array(),
				'_mk_leads'            => 0,
			);
			if ( $p['year'] ) {
				$meta['_mk_year_built'] = $p['year'];
			}
			if ( $p['land'] ) {
				$meta['_mk_land_size'] = $p['land'][0];
				$meta['_mk_land_unit'] = $p['land'][1];
			}
			foreach ( $meta as $k => $v ) {
				update_post_meta( $id, $k, $v );
			}
			if ( $gallery ) {
				set_post_thumbnail( $id, $gallery[0] );
			}
			if ( '' === (string) get_post_meta( $id, '_mk_ref', true ) ) {
				update_post_meta( $id, '_mk_ref', Makaazi_Meta::next_ref() );
			}
		}

		// Hero slider.
		if ( post_type_exists( 'mk_slider' ) && ! self::existing( 'slider:home', 'mk_slider' ) ) {
			$sid    = wp_insert_post(
				array(
					'post_type'   => 'mk_slider',
					'post_status' => 'publish',
					'post_title'  => __( 'Home hero', 'makaazi' ),
					'post_name'   => 'home-hero',
				)
			);
			$slides = array();
			foreach ( $data['hero'] as $h ) {
				$slides[] = array(
					'image'        => self::image( $h['image'] ),
					'mobile_image' => 0,
					'video'        => '',
					'eyebrow'      => $h['eyebrow'],
					'title'        => $h['title'],
					'text'         => $h['text'],
					'btn1_text'    => $h['btn1_text'],
					'btn1_url'     => home_url( $h['btn1_url'] ),
					'btn2_text'    => $h['btn2_text'],
					'btn2_url'     => home_url( $h['btn2_url'] ),
					'align'        => 'left',
					'overlay'      => 50,
					'from'         => '',
					'until'        => '',
				);
			}
			update_post_meta( $sid, '_mks_slides', $slides );
			if ( class_exists( 'MKS_Slider' ) ) {
				update_post_meta( $sid, '_mks_settings', MKS_Slider::defaults() );
			}
			update_post_meta( $sid, '_mk_demo', 'slider:home' );
		}

		// Pages.
		$home = self::existing( 'page:home', 'page' );
		if ( ! $home ) {
			$home = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'Home', 'makaazi' ),
					'post_name'    => 'home',
					'post_content' => self::home_content(),
				)
			);
			update_post_meta( $home, '_mk_demo', 'page:home' );
		}
		$contact = self::existing( 'page:contact', 'page' );
		if ( ! $contact ) {
			$contact = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'Contact', 'makaazi' ),
					'post_name'    => 'contact',
					'post_content' => "<!-- wp:paragraph -->\n<p>" . esc_html__( 'Call or WhatsApp us on +254 700 123 456, or email hello@makaazihomes.co.ke. Our office is at Westlands Business Park, Nairobi, open Monday to Saturday, 8am to 6pm.', 'makaazi' ) . "</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html__( 'Search properties', 'makaazi' ) . "</h2>\n<!-- /wp:heading -->\n\n<!-- wp:shortcode -->\n[makaazi_search style=\"minimal\"]\n<!-- /wp:shortcode -->",
				)
			);
			update_post_meta( $contact, '_mk_demo', 'page:contact' );
		}
		if ( ! get_option( 'makaazi_demo_imported' ) ) {
			update_option( 'makaazi_demo_prev_front', array( get_option( 'show_on_front' ), (int) get_option( 'page_on_front' ) ), false );
		}
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );

		// Main menu (used by the theme's Navigation block).
		if ( post_type_exists( 'wp_navigation' ) && ! self::existing( 'nav:main', 'wp_navigation' ) ) {
			$link  = function ( $label, $url ) {
				return '<!-- wp:navigation-link ' . wp_json_encode(
					array(
						'label'          => $label,
						'url'            => $url,
						'kind'           => 'custom',
						'isTopLevelLink' => true,
					),
					JSON_UNESCAPED_SLASHES
				) . ' /-->';
			};
			$props = makaazi_archive_url();
			$nav   = wp_insert_post(
				array(
					'post_type'    => 'wp_navigation',
					'post_status'  => 'publish',
					'post_title'   => __( 'Main menu', 'makaazi' ),
					'post_content' => implode(
						"\n",
						array(
							$link( __( 'Buy', 'makaazi' ), add_query_arg( 'status', 'sale', $props ) ),
							$link( __( 'Rent', 'makaazi' ), add_query_arg( 'status', 'rent', $props ) ),
							$link( __( 'Commercial', 'makaazi' ), add_query_arg( 'use', 'commercial', $props ) ),
							$link( __( 'All properties', 'makaazi' ), $props ),
							$link( __( 'Contact', 'makaazi' ), get_permalink( $contact ) ),
						)
					),
				)
			);
			update_post_meta( $nav, '_mk_demo', 'nav:main' );
		}

		if ( 'Just another WordPress site' === get_option( 'blogdescription' ) || '' === get_option( 'blogdescription' ) ) {
			update_option( 'blogdescription', __( 'Homes, offices and land across Kenya', 'makaazi' ) );
		}
		if ( ! get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}
		update_option( 'makaazi_demo_imported', time() );
		makaazi_bump_cache();
		flush_rewrite_rules();
	}

	/**
	 * Home page content: the theme's pattern if available, otherwise a built-in version.
	 *
	 * @return string
	 */
	public static function home_content() {
		$reg = class_exists( 'WP_Block_Patterns_Registry' ) ? WP_Block_Patterns_Registry::get_instance() : null;
		if ( $reg && $reg->is_registered( 'makaazi-theme/home' ) ) {
			$p = $reg->get_registered( 'makaazi-theme/home' );
			if ( ! empty( $p['content'] ) ) {
				return $p['content'];
			}
		}
		$sc = function ( $code ) {
			return "<!-- wp:shortcode -->\n" . $code . "\n<!-- /wp:shortcode -->";
		};
		return implode(
			"\n\n",
			array(
				$sc( '[makaazi_slider slug="home-hero"]' ),
				$sc( '[makaazi_properties source="featured" count="4" columns="4" eyebrow="Hand-picked" title="Featured properties"]' ),
				$sc( '[makaazi_properties source="sale" count="3" columns="3" eyebrow="Buy" title="Homes for sale" use="residential"]' ),
				$sc( '[makaazi_locations count="6" columns="3" eyebrow="Neighbourhoods" title="Browse by location"]' ),
				$sc( '[makaazi_properties source="rent" count="8" columns="4" layout="carousel" eyebrow="Rent" title="Homes to rent"]' ),
			)
		);
	}

	/**
	 * Remove everything the importer created.
	 */
	public static function remove() {
		$items = get_posts(
			array(
				'post_type'      => array( 'property', 'mk_slider', 'page', 'wp_navigation', 'attachment' ),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_mk_demo', // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		foreach ( $items as $id ) {
			if ( 'attachment' === get_post_type( $id ) ) {
				wp_delete_attachment( $id, true );
			} else {
				wp_delete_post( $id, true );
			}
		}
		foreach ( (array) get_option( 'makaazi_demo_terms', array() ) as $t ) {
			$term = get_term( (int) $t[1], $t[0] );
			if ( $term && ! is_wp_error( $term ) && 0 === (int) $term->count ) {
				wp_delete_term( $term->term_id, $t[0] );
			}
		}
		$prev = get_option( 'makaazi_demo_prev_front' );
		if ( is_array( $prev ) ) {
			update_option( 'show_on_front', $prev[0] ? $prev[0] : 'posts' );
			update_option( 'page_on_front', (int) $prev[1] );
		}
		delete_option( 'makaazi_demo_terms' );
		delete_option( 'makaazi_demo_imported' );
		delete_option( 'makaazi_demo_prev_front' );
		makaazi_bump_cache();
	}
}
