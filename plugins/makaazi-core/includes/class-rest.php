<?php
/**
 * REST endpoints used by the front-end script.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * /wp-json/makaazi/v1/*
 */
class Makaazi_Rest {

	const NS = 'makaazi/v1';

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Register routes.
	 */
	public static function routes() {
		register_rest_route(
			self::NS,
			'/listing',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'listing' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/cards',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'cards' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/enquiry',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'enquiry' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Re-render the listing for new filters.
	 *
	 * @param WP_REST_Request $r Request.
	 * @return WP_REST_Response
	 */
	public static function listing( $r ) {
		$params = $r->get_query_params();
		$cfg    = json_decode( (string) $r->get_param( 'cfg' ), true );
		$cfg    = Makaazi_Render::listing_config( is_array( $cfg ) ? $cfg : array() );
		$base   = esc_url_raw( (string) $r->get_param( 'base' ) );
		if ( ! $base || 0 !== strpos( $base, home_url() ) ) {
			$base = makaazi_archive_url();
		}
		unset( $params['cfg'], $params['base'] );
		$view = isset( $params['view'] ) ? sanitize_key( $params['view'] ) : $cfg['default_view'];
		if ( ! in_array( $view, explode( ',', $cfg['views'] ), true ) ) {
			$view = 'grid';
		}
		$html = Makaazi_Render::listing_inner( $cfg, $params, $view, $base );
		return rest_ensure_response( array( 'html' => $html ) );
	}

	/**
	 * Next page of cards for "Load more".
	 *
	 * @param WP_REST_Request $r Request.
	 * @return WP_REST_Response
	 */
	public static function cards( $r ) {
		$atts = json_decode( (string) $r->get_param( 'atts' ), true );
		$a    = shortcode_atts( Makaazi_Shortcodes::properties_defaults(), is_array( $atts ) ? array_map( 'sanitize_text_field', $atts ) : array() );
		$page = max( 2, (int) $r->get_param( 'page' ) );
		list( $args ) = Makaazi_Shortcodes::properties_query( $a, $page );
		$q    = new WP_Query( $args );
		$html = '';
		foreach ( $q->posts as $p ) {
			$html .= Makaazi_Render::card(
				$p->ID,
				array(
					'layout'      => $a['layout'],
					'button_text' => $a['button_text'],
					'image_ratio' => $a['image_ratio'],
					'show'        => $a['show'],
					'hide'        => $a['hide'],
				)
			);
		}
		return rest_ensure_response(
			array(
				'html' => $html,
				'more' => $page < $q->max_num_pages,
			)
		);
	}

	/**
	 * Store an enquiry and email it.
	 *
	 * @param WP_REST_Request $r Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function enquiry( $r ) {
		$p = $r->get_json_params();
		if ( ! is_array( $p ) || ! $p ) {
			$p = $r->get_body_params();
		}
		$err = function ( $msg, $code = 400 ) {
			return new WP_Error( 'makaazi_enquiry', $msg, array( 'status' => $code ) );
		};
		// Bots fill the hidden field or submit instantly.
		if ( ! empty( $p['website'] ) || ( isset( $p['ts'] ) && time() - (int) $p['ts'] < 3 ) ) {
			return rest_ensure_response(
				array(
					'ok' => true,
					'message' => __( 'Thanks. Your enquiry has been sent.', 'makaazi' ),
				)
			);
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'mk_rl_' . md5( $ip );
		$hit = (int) get_transient( $key );
		if ( $hit >= 5 ) {
			return $err( __( 'You have sent several enquiries in the last hour. Please call or WhatsApp us instead.', 'makaazi' ), 429 );
		}
		$pid = isset( $p['property_id'] ) ? absint( $p['property_id'] ) : 0;
		$aid = isset( $p['agent_id'] ) ? absint( $p['agent_id'] ) : 0;
		if ( $pid ) {
			if ( 'property' !== get_post_type( $pid ) || 'publish' !== get_post_status( $pid ) ) {
				return $err( __( 'This property is no longer available.', 'makaazi' ) );
			}
			$aid = Makaazi_Agents::of_property( $pid );
		} elseif ( ! $aid || ! makaazi_agents_on() || 'mk_agent' !== get_post_type( $aid ) || 'publish' !== get_post_status( $aid ) ) {
			return $err( __( 'We could not find who to send this to. Please call us instead.', 'makaazi' ) );
		}
		$name  = isset( $p['name'] ) ? sanitize_text_field( $p['name'] ) : '';
		$phone = isset( $p['phone'] ) ? sanitize_text_field( $p['phone'] ) : '';
		$email = isset( $p['email'] ) ? sanitize_email( $p['email'] ) : '';
		$msg   = isset( $p['message'] ) ? sanitize_textarea_field( $p['message'] ) : '';
		if ( '' === $name ) {
			return $err( __( 'Add your name so we know who to reply to.', 'makaazi' ) );
		}
		if ( strlen( preg_replace( '/\D+/', '', $phone ) ) < 9 ) {
			return $err( __( 'Enter a phone number with at least 9 digits, for example 0712 345 678.', 'makaazi' ) );
		}
		if ( ! empty( $p['email'] ) && ! is_email( $email ) ) {
			return $err( __( 'Check the email address, or leave it empty.', 'makaazi' ) );
		}
		set_transient( $key, $hit + 1, HOUR_IN_SECONDS );

		$id = Makaazi_Enquiries::create( $pid, $name, $phone, $email, $msg, $aid );
		if ( is_wp_error( $id ) ) {
			return $err( __( 'We could not save your enquiry. Please call or WhatsApp us.', 'makaazi' ), 500 );
		}
		return rest_ensure_response(
			array(
				'ok'      => true,
				'message' => __( 'Thanks. Your enquiry has been sent and we will call or WhatsApp you shortly.', 'makaazi' ),
			)
		);
	}
}
