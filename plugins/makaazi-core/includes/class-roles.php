<?php
/**
 * Roles and capabilities: Agent and Property owner.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Property capabilities, the mk_agent and mk_owner roles, and wp-admin access rules.
 */
class Makaazi_Roles {

	const VER = 2;

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 1 );
		add_filter( 'user_has_cap', array( __CLASS__, 'dynamic_caps' ), 10, 4 );
		add_action( 'admin_init', array( __CLASS__, 'keep_owners_out' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'own_properties_only' ) );
	}

	/**
	 * Property capabilities.
	 *
	 * @return string[]
	 */
	public static function caps() {
		return array(
			'edit_properties',
			'edit_others_properties',
			'edit_published_properties',
			'edit_private_properties',
			'publish_properties',
			'read_private_properties',
			'delete_properties',
			'delete_others_properties',
			'delete_published_properties',
			'delete_private_properties',
		);
	}

	/**
	 * Install or upgrade roles once per version.
	 */
	public static function maybe_install() {
		if ( (int) get_option( 'makaazi_roles_ver' ) >= self::VER ) {
			return;
		}
		self::install();
	}

	/**
	 * Add caps to admins/editors and create the two roles.
	 */
	public static function install() {
		foreach ( array( 'administrator', 'editor' ) as $r ) {
			$role = get_role( $r );
			if ( $role ) {
				foreach ( self::caps() as $c ) {
					$role->add_cap( $c );
				}
			}
		}
		remove_role( 'mk_agent' );
		add_role(
			'mk_agent',
			__( 'Agent', 'makaazi' ),
			array(
				'read'                        => true,
				'upload_files'                => true,
				'edit_properties'             => true,
				'edit_published_properties'   => true,
				'publish_properties'          => true,
				'delete_properties'           => true,
				'delete_published_properties' => true,
			)
		);
		remove_role( 'mk_owner' );
		add_role( 'mk_owner', __( 'Property owner', 'makaazi' ), array( 'read' => true ) );
		update_option( 'makaazi_roles_ver', self::VER );
		update_option( 'makaazi_flush_rules', 1, false ); // New agent archive URLs.
	}

	/**
	 * Agents lose publishing when "agents' listings need review" is on, or everything when agents are switched off.
	 *
	 * @param array   $allcaps All caps.
	 * @param array   $caps    Required caps.
	 * @param array   $args    Args.
	 * @param WP_User $user    User.
	 * @return array
	 */
	public static function dynamic_caps( $allcaps, $caps, $args, $user ) {
		if ( ! $user || ! in_array( 'mk_agent', (array) $user->roles, true ) ) {
			return $allcaps;
		}
		if ( ! makaazi_settings( 'agents_enabled' ) ) {
			foreach ( self::caps() as $c ) {
				$allcaps[ $c ] = false;
			}
			$allcaps['upload_files'] = false;
		} elseif ( makaazi_settings( 'agent_review' ) ) {
			$allcaps['publish_properties'] = false;
		}
		return $allcaps;
	}

	/**
	 * Is this user an owner (front-end only)?
	 *
	 * @param WP_User|null $user User.
	 * @return bool
	 */
	public static function is_owner( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		return $user && $user->exists() && in_array( 'mk_owner', (array) $user->roles, true );
	}

	/**
	 * Is this user an agent?
	 *
	 * @param WP_User|null $user User.
	 * @return bool
	 */
	public static function is_agent( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		return $user && $user->exists() && in_array( 'mk_agent', (array) $user->roles, true );
	}

	/**
	 * Owners (and agents when agents are off) use the front-end dashboard only.
	 */
	public static function keep_owners_out() {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_user_logged_in() ) {
			return;
		}
		$user = wp_get_current_user();
		if ( user_can( $user, 'edit_posts' ) || user_can( $user, 'edit_properties' ) ) {
			return;
		}
		if ( self::is_owner( $user ) || self::is_agent( $user ) ) {
			wp_safe_redirect( makaazi_dashboard_url() ? makaazi_dashboard_url() : home_url( '/' ) );
			exit;
		}
	}

	/**
	 * Hide the admin bar for owners.
	 *
	 * @param bool $show Show.
	 * @return bool
	 */
	public static function admin_bar( $show ) {
		return self::is_owner() ? false : $show;
	}

	/**
	 * Agents in wp-admin only see their own properties.
	 *
	 * @param WP_Query $q Query.
	 */
	public static function own_properties_only( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'property' !== $q->get( 'post_type' ) ) {
			return;
		}
		if ( current_user_can( 'edit_others_properties' ) ) {
			return;
		}
		$q->set( 'author', get_current_user_id() );
	}
}
