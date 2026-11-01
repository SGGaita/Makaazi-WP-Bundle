<?php
/**
 * Settings page: Properties › Settings.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tabbed settings stored in one option, makaazi_settings.
 */
class Makaazi_Settings {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_makaazi_settings', array( __CLASS__, 'save' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_submenu_page( 'edit.php?post_type=property', __( 'Makaazi Settings', 'makaazi' ), __( 'Settings', 'makaazi' ), 'manage_options', 'makaazi-settings', array( __CLASS__, 'page' ) );
	}

	/**
	 * Tabs and fields.
	 *
	 * @return array
	 */
	public static function schema() {
		$card_parts = array(
			'badges'   => __( 'Badges (status, featured, new)', 'makaazi' ),
			'category' => __( 'Category', 'makaazi' ),
			'use'      => __( 'Residential / Commercial', 'makaazi' ),
			'type'     => __( 'Type', 'makaazi' ),
			'location' => __( 'Location', 'makaazi' ),
			'beds'     => __( 'Bedrooms', 'makaazi' ),
			'baths'    => __( 'Bathrooms', 'makaazi' ),
			'parking'  => __( 'Parking', 'makaazi' ),
			'area'     => __( 'Area', 'makaazi' ),
		);
		$filters    = array(
			'status'   => __( 'Buy / Rent', 'makaazi' ),
			'location' => __( 'Location', 'makaazi' ),
			'type'     => __( 'Property type', 'makaazi' ),
			'use'      => __( 'Use', 'makaazi' ),
			'category' => __( 'Category', 'makaazi' ),
			'price'    => __( 'Price', 'makaazi' ),
			'beds'     => __( 'Bedrooms', 'makaazi' ),
			'baths'    => __( 'Bathrooms', 'makaazi' ),
			'parking'  => __( 'Parking', 'makaazi' ),
			'features' => __( 'Features', 'makaazi' ),
			'keyword'  => __( 'Keyword or reference', 'makaazi' ),
		);
		return array(
			'general' => array(
				__( 'General', 'makaazi' ),
				array(
					'currency'    => array( 'text', __( 'Currency code', 'makaazi' ), __( 'Shown before prices, e.g. KES.', 'makaazi' ) ),
					'short_price' => array( 'checkbox', __( 'Short prices', 'makaazi' ), __( 'Show KES 85M instead of KES 85,000,000.', 'makaazi' ) ),
					'unit'        => array( 'select', __( 'Default area unit', 'makaazi' ), '', array( 'sqm' => __( 'Square metres (m²)', 'makaazi' ), 'sqft' => __( 'Square feet (ft²)', 'makaazi' ) ) ),
					'unit_toggle' => array( 'checkbox', __( 'Unit switch', 'makaazi' ), __( 'Let visitors switch between m² and ft².', 'makaazi' ) ),
					'ref_prefix'  => array( 'text', __( 'Reference prefix', 'makaazi' ), __( 'New listings get references like MK-00101.', 'makaazi' ) ),
				),
			),
			'cards'   => array(
				__( 'Cards', 'makaazi' ),
				array(
					'button_text' => array( 'text', __( 'Button text', 'makaazi' ), __( 'Each [makaazi_properties] can override it with button_text="…".', 'makaazi' ) ),
					'card_images' => array( 'select', __( 'Photos per card', 'makaazi' ), __( 'How many photos visitors can scroll on a card.', 'makaazi' ), array( 1 => '1', 3 => '3', 5 => '5', 8 => '8' ) ),
					'image_ratio' => array( 'select', __( 'Photo shape', 'makaazi' ), '', array( '4:3' => '4:3', '3:2' => '3:2', '16:9' => '16:9', '1:1' => '1:1' ) ),
					'card_show'   => array( 'multi', __( 'Show on cards', 'makaazi' ), '', $card_parts ),
					'new_days'    => array( 'number', __( '"New" badge for', 'makaazi' ), __( 'days after publishing.', 'makaazi' ) ),
				),
			),
			'listing' => array(
				__( 'Properties page', 'makaazi' ),
				array(
					'per_page'     => array( 'number', __( 'Properties per page', 'makaazi' ), '' ),
					'default_sort' => array( 'select', __( 'Default sort', 'makaazi' ), '', Makaazi_Query::sorts() ),
					'filters'      => array( 'multi', __( 'Filters', 'makaazi' ), '', $filters ),
				),
			),
			'features' => array(
				__( 'Features', 'makaazi' ),
				array(
					'agents_enabled'     => array( 'checkbox', __( 'Agents', 'makaazi' ), __( 'Agent profiles, agent pages, the agent on each listing and agent sign-in. Turning this off hides them; nothing is deleted.', 'makaazi' ) ),
					'agent_review'       => array( 'checkbox', __( 'Review agent listings', 'makaazi' ), __( 'New listings from agents wait for an admin to approve them.', 'makaazi' ) ),
					'enquiry_cc_admin'   => array( 'checkbox', __( 'Copy me on agent enquiries', 'makaazi' ), __( 'Send a copy of every enquiry to the address in Property page › Send enquiries to.', 'makaazi' ) ),
					'owners_enabled'     => array( 'checkbox', __( 'Owner submissions', 'makaazi' ), __( 'Owners can sign up, list properties from the website and manage them in their dashboard.', 'makaazi' ) ),
					'owner_registration' => array( 'select', __( 'Owner accounts', 'makaazi' ), __( 'How property owners get an account.', 'makaazi' ), array( 'open' => __( 'Anyone can sign up', 'makaazi' ), 'approval' => __( 'Sign-ups need my approval', 'makaazi' ), 'invite' => __( 'Invite only (I create accounts)', 'makaazi' ) ) ),
					'owner_email_verify' => array( 'checkbox', __( 'Confirm email', 'makaazi' ), __( 'New owners must click a link in an email before signing in. Your site must be able to send email (XAMPP cannot by default); you can also activate accounts under Users.', 'makaazi' ) ),
					'owner_autopublish'  => array( 'checkbox', __( 'Publish owner listings straight away', 'makaazi' ), __( 'Not recommended. When off, every owner listing waits for review.', 'makaazi' ) ),
					'owner_edit_review'  => array( 'checkbox', __( 'Review edits to live listings', 'makaazi' ), __( 'When an owner edits a live listing it goes back to Pending review.', 'makaazi' ) ),
					'max_photos'         => array( 'number', __( 'Photos per listing', 'makaazi' ), __( 'Maximum photos an owner or agent can upload.', 'makaazi' ) ),
					'dashboard_page'     => array( 'page', __( 'My account page', 'makaazi' ), __( 'A page containing [makaazi_dashboard].', 'makaazi' ) ),
				),
			),
			'shortlist' => array(
				__( 'Favourites & compare', 'makaazi' ),
				array(
					'card_favourite'  => array( 'checkbox', __( 'Favourites', 'makaazi' ), __( 'Show the heart on cards and the Save button on property pages.', 'makaazi' ) ),
					'card_compare'    => array( 'checkbox', __( 'Compare', 'makaazi' ), __( 'Show the compare button and the compare bar (up to 4 properties).', 'makaazi' ) ),
					'favourites_page' => array( 'page', __( 'Favourites page', 'makaazi' ), __( 'A page containing [makaazi_favourites].', 'makaazi' ) ),
					'compare_page'    => array( 'page', __( 'Compare page', 'makaazi' ), __( 'A page containing [makaazi_compare].', 'makaazi' ) ),
				),
			),
			'single'  => array(
				__( 'Property page', 'makaazi' ),
				array(
					'contact_name'        => array( 'text', __( 'Contact name', 'makaazi' ), __( 'Shown in the contact box when a listing has no agent.', 'makaazi' ) ),
					'contact_phone'       => array( 'text', __( 'Phone', 'makaazi' ), '' ),
					'whatsapp'            => array( 'text', __( 'WhatsApp number', 'makaazi' ), __( 'International format without +, e.g. 254700123456.', 'makaazi' ) ),
					'enquiry_email'       => array( 'text', __( 'Send enquiries to', 'makaazi' ), __( 'Leave empty to use the site admin email. Enquiries are also saved under Properties › Enquiries.', 'makaazi' ) ),
					'privacy_radius'      => array( 'select', __( 'Default map privacy radius', 'makaazi' ), '', array( 250 => '250 m', 500 => '500 m', 1000 => '1 km', 2000 => '2 km' ) ),
					'related_count'       => array( 'number', __( 'Similar properties', 'makaazi' ), __( 'How many to show.', 'makaazi' ) ),
					'related_same_status' => array( 'checkbox', __( 'Same status only', 'makaazi' ), __( 'Only suggest rentals for rentals and sales for sales.', 'makaazi' ) ),
					'map_tiles'           => array( 'text', __( 'Map tiles URL', 'makaazi' ), __( 'OpenStreetMap by default. Use a tile provider for high traffic sites.', 'makaazi' ) ),
					'map_center'          => array( 'text', __( 'Default map centre', 'makaazi' ), __( 'Latitude,longitude used when placing a new pin.', 'makaazi' ) ),
				),
			),
			'prices'  => array(
				__( 'Price brackets', 'makaazi' ),
				array(
					'brackets_sale' => array( 'text', __( 'Buy brackets', 'makaazi' ), __( 'Comma-separated amounts used in search and filters.', 'makaazi' ) ),
					'brackets_rent' => array( 'text', __( 'Rent brackets (per month)', 'makaazi' ), __( 'Comma-separated amounts.', 'makaazi' ) ),
				),
			),
		);
	}

	/**
	 * Render page.
	 */
	public static function page() {
		$schema = self::schema();
		$tab    = isset( $_GET['tab'] ) && isset( $schema[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'features'; // phpcs:ignore WordPress.Security.NonceVerification
		$s      = makaazi_settings();
		echo '<div class="wrap mk-settings"><h1>' . esc_html__( 'Makaazi Settings', 'makaazi' ) . '</h1>';
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'makaazi' ) . '</p></div>';
		}
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $schema as $k => $t ) {
			echo '<a class="nav-tab' . ( $k === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'edit.php?post_type=property&page=makaazi-settings&tab=' . $k ) ) . '">' . esc_html( $t[0] ) . '</a>';
		}
		echo '</nav><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'makaazi_settings' );
		echo '<input type="hidden" name="action" value="makaazi_settings"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '"><table class="form-table" role="presentation">';
		foreach ( $schema[ $tab ][1] as $key => $f ) {
			$id = 'mk-set-' . $key;
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $f[1] ) . '</label></th><td>';
			$val = isset( $s[ $key ] ) ? $s[ $key ] : '';
			switch ( $f[0] ) {
				case 'checkbox':
					echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="s[' . esc_attr( $key ) . ']" value="1"' . checked( (bool) $val, true, false ) . '> ' . esc_html( $f[2] ) . '</label>';
					$f[2] = '';
					break;
				case 'select':
					echo '<select id="' . esc_attr( $id ) . '" name="s[' . esc_attr( $key ) . ']">';
					foreach ( $f[3] as $ov => $ol ) {
						echo '<option value="' . esc_attr( $ov ) . '"' . selected( (string) $val, (string) $ov, false ) . '>' . esc_html( $ol ) . '</option>';
					}
					echo '</select>';
					break;
				case 'multi':
					echo '<fieldset id="' . esc_attr( $id ) . '" class="mk-multi">';
					foreach ( $f[3] as $ov => $ol ) {
						echo '<label><input type="checkbox" name="s[' . esc_attr( $key ) . '][]" value="' . esc_attr( $ov ) . '"' . checked( in_array( $ov, (array) $val, true ), true, false ) . '> ' . esc_html( $ol ) . '</label>';
					}
					echo '</fieldset>';
					break;
				case 'page':
					wp_dropdown_pages(
						array(
							'name'              => 's[' . $key . ']',
							'id'                => $id,
							'selected'          => (int) $val,
							'show_option_none'  => __( '— Choose a page —', 'makaazi' ),
							'option_none_value' => 0,
						)
					);
					break;
				case 'number':
					echo '<input type="number" min="0" id=""' . esc_attr( $id ) . '" name="s[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" class="small-text">';
					break;
				default:
					echo '<input type="text" id="' . esc_attr( $id ) . '" name="s[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" class="regular-text">';
			}
			if ( $f[2] ) {
				echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
		submit_button();
		echo '</form>';
		if ( 'cards' === $tab ) {
			$sample = get_posts(
				array(
					'post_type'      => 'property',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( $sample ) {
				Makaazi_Assets::register();
				wp_print_styles( 'makaazi' );
				echo '<h2>' . esc_html__( 'Preview', 'makaazi' ) . '</h2><div class="mk-admin-preview">' . Makaazi_Render::card( $sample[0] ) . '</div>'; // phpcs:ignore
			}
		}
		echo '</div>';
	}

	/**
	 * Save handler.
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'makaazi' ) );
		}
		check_admin_referer( 'makaazi_settings' );
		$schema = self::schema();
		$tab    = isset( $_POST['tab'] ) && isset( $schema[ $_POST['tab'] ] ) ? sanitize_key( $_POST['tab'] ) : 'features';
		$in     = isset( $_POST['s'] ) && is_array( $_POST['s'] ) ? wp_unslash( $_POST['s'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$saved  = get_option( 'makaazi_settings', array() );
		$saved  = is_array( $saved ) ? $saved : array();
		foreach ( $schema[ $tab ][1] as $key => $f ) {
			switch ( $f[0] ) {
				case 'checkbox':
					$saved[ $key ] = empty( $in[ $key ] ) ? 0 : 1;
					break;
				case 'multi':
					$saved[ $key ] = isset( $in[ $key ] ) ? array_values( array_intersect( (array) $in[ $key ], array_map( 'strval', array_keys( $f[3] ) ) ) ) : array();
					break;
				case 'select':
					$v             = isset( $in[ $key ] ) ? (string) $in[ $key ] : '';
					$saved[ $key ] = array_key_exists( $v, $f[3] ) ? $v : makaazi_default_settings()[ $key ];
					break;
				case 'number':
				case 'page':
					$saved[ $key ] = isset( $in[ $key ] ) ? absint( $in[ $key ] ) : 0;
					break;
				default:
					$v = isset( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : '';
					if ( 0 === strpos( $key, 'brackets_' ) ) {
						$v = implode( ',', array_filter( array_map( 'absint', explode( ',', $v ) ) ) );
					}
					if ( 'map_tiles' === $key ) {
						$v = $v ? $v : makaazi_default_settings()['map_tiles'];
					}
					$saved[ $key ] = $v;
			}
		}
		$before = get_option( 'makaazi_settings', array() );
		if ( isset( $saved['agents_enabled'] ) && ( ! is_array( $before ) || ! isset( $before['agents_enabled'] ) || (int) $before['agents_enabled'] !== (int) $saved['agents_enabled'] ) ) {
			update_option( 'makaazi_flush_rules', 1, false ); // Agent pages appear or disappear.
		}
		update_option( 'makaazi_settings', $saved );
		makaazi_bump_cache();
		wp_safe_redirect( admin_url( 'edit.php?post_type=property&page=makaazi-settings&tab=' . $tab . '&updated=1' ) );
		exit;
	}
}
