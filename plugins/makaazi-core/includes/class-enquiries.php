<?php
/**
 * Enquiries inbox.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores enquiries as a private post type so nothing is lost when email is not configured (e.g. XAMPP).
 */
class Makaazi_Enquiries {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'manage_mk_enquiry_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_mk_enquiry_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'add_meta_boxes_mk_enquiry', array( __CLASS__, 'box' ) );
	}

	/**
	 * Register the post type.
	 */
	public static function register() {
		register_post_type(
			'mk_enquiry',
			array(
				'labels'          => array(
					'name'          => __( 'Enquiries', 'makaazi' ),
					'singular_name' => __( 'Enquiry', 'makaazi' ),
					'edit_item'     => __( 'Enquiry', 'makaazi' ),
					'not_found'     => __( 'No enquiries yet.', 'makaazi' ),
					'all_items'     => __( 'Enquiries', 'makaazi' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=property',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Save an enquiry and notify by email.
	 *
	 * @param int    $pid   Property ID.
	 * @param string $name  Name.
	 * @param string $phone Phone.
	 * @param string $email Email.
	 * @param string $msg   Message.
	 * @return int|WP_Error
	 */
	public static function create( $pid, $name, $phone, $email, $msg ) {
		$id = wp_insert_post(
			array(
				'post_type'    => 'mk_enquiry',
				'post_status'  => 'private',
				/* translators: 1: name, 2: property title */
				'post_title'   => sprintf( __( '%1$s about %2$s', 'makaazi' ), $name, get_the_title( $pid ) ),
				'post_content' => $msg,
				'meta_input'   => array(
					'_mk_property_id' => $pid,
					'_mk_name'        => $name,
					'_mk_phone'       => $phone,
					'_mk_email'       => $email,
				),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_post_meta( $pid, '_mk_leads', (int) get_post_meta( $pid, '_mk_leads', true ) + 1 );

		$to = makaazi_settings( 'enquiry_email' );
		$to = is_email( $to ) ? $to : get_option( 'admin_email' );
		/* translators: 1: property title, 2: reference */
		$subject = sprintf( __( 'New enquiry: %1$s (%2$s)', 'makaazi' ), get_the_title( $pid ), makaazi_meta( $pid, 'ref' ) );
		$body    = implode(
			"\n",
			array(
				__( 'Name:', 'makaazi' ) . ' ' . $name,
				__( 'Phone:', 'makaazi' ) . ' ' . $phone,
				__( 'Email:', 'makaazi' ) . ' ' . ( $email ? $email : '-' ),
				__( 'Property:', 'makaazi' ) . ' ' . get_permalink( $pid ),
				'',
				$msg,
				'',
				__( 'View all enquiries:', 'makaazi' ) . ' ' . admin_url( 'edit.php?post_type=mk_enquiry' ),
			)
		);
		$headers = $email ? array( 'Reply-To: ' . $name . ' <' . $email . '>' ) : array();
		wp_mail( $to, $subject, $body, $headers );
		return $id;
	}

	/**
	 * List columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function columns( $cols ) {
		return array(
			'cb'          => $cols['cb'],
			'title'       => __( 'Enquiry', 'makaazi' ),
			'mk_phone'    => __( 'Phone', 'makaazi' ),
			'mk_email'    => __( 'Email', 'makaazi' ),
			'mk_property' => __( 'Property', 'makaazi' ),
			'date'        => __( 'Received', 'makaazi' ),
		);
	}

	/**
	 * Column values.
	 *
	 * @param string $col Column.
	 * @param int    $id  Post ID.
	 */
	public static function column( $col, $id ) {
		if ( 'mk_phone' === $col ) {
			echo esc_html( get_post_meta( $id, '_mk_phone', true ) );
		} elseif ( 'mk_email' === $col ) {
			echo esc_html( get_post_meta( $id, '_mk_email', true ) );
		} elseif ( 'mk_property' === $col ) {
			$pid = (int) get_post_meta( $id, '_mk_property_id', true );
			if ( $pid ) {
				echo '<a href="' . esc_url( get_edit_post_link( $pid ) ) . '">' . esc_html( get_the_title( $pid ) ) . '</a> <span style="color:#646970">' . esc_html( makaazi_meta( $pid, 'ref' ) ) . '</span>';
			}
		}
	}

	/**
	 * Contact details box on the enquiry screen.
	 */
	public static function box() {
		add_meta_box(
			'mk_enquiry_from',
			__( 'Contact details', 'makaazi' ),
			function ( $post ) {
				$pid = (int) get_post_meta( $post->ID, '_mk_property_id', true );
				echo '<p><strong>' . esc_html( get_post_meta( $post->ID, '_mk_name', true ) ) . '</strong><br>' . esc_html( get_post_meta( $post->ID, '_mk_phone', true ) ) . '<br>' . esc_html( get_post_meta( $post->ID, '_mk_email', true ) ) . '</p>';
				if ( $pid ) {
					echo '<p><a href="' . esc_url( get_permalink( $pid ) ) . '">' . esc_html( get_the_title( $pid ) ) . '</a></p>';
				}
			},
			'mk_enquiry',
			'side',
			'high'
		);
	}
}
