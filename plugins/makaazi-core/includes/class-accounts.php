<?php
/**
 * Front-end accounts: sign in, sign up, email confirmation, admin activation and the My account dashboard.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Accounts and dashboard.
 */
class Makaazi_Accounts {

	/**
	 * Messages for the current request: [type, text].
	 *
	 * @var array
	 */
	public static $notice = array();

	/**
	 * Field errors for the current request.
	 *
	 * @var array
	 */
	public static $errors = array();

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'shortcodes' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle' ), 5 );
		add_filter( 'authenticate', array( __CLASS__, 'block_pending' ), 99 );
		add_filter( 'manage_users_columns', array( __CLASS__, 'user_columns' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'user_column' ), 10, 3 );
		add_filter( 'user_row_actions', array( __CLASS__, 'user_actions' ), 10, 2 );
		add_action( 'admin_post_makaazi_activate_user', array( __CLASS__, 'activate_user' ) );
		add_filter( 'makaazi_shortcode_docs', array( __CLASS__, 'docs' ) );
	}

	/**
	 * Shortcodes.
	 */
	public static function shortcodes() {
		add_shortcode( 'makaazi_dashboard', array( __CLASS__, 'dashboard' ) );
		add_shortcode( 'makaazi_account_link', array( __CLASS__, 'link' ) );
	}

	/**
	 * Can this user list properties from the front end?
	 *
	 * @param WP_User|null $u User.
	 * @return bool
	 */
	public static function can_list( $u = null ) {
		$u = $u ? $u : wp_get_current_user();
		if ( ! $u || ! $u->exists() ) {
			return false;
		}
		if ( Makaazi_Roles::is_owner( $u ) ) {
			return makaazi_owners_on() && ! get_user_meta( $u->ID, '_mk_pending', true );
		}
		if ( Makaazi_Roles::is_agent( $u ) ) {
			return makaazi_agents_on();
		}
		return user_can( $u, 'edit_properties' );
	}

	/**
	 * Registration open to the public?
	 *
	 * @return bool
	 */
	public static function can_register() {
		return makaazi_owners_on() && 'invite' !== makaazi_settings( 'owner_registration' );
	}

	/* ---------------------------------------------------------------------
	 * Form handling
	 * ------------------------------------------------------------------- */

	/**
	 * Handle POSTed account forms and email confirmation links.
	 */
	public static function handle() {
		// Email confirmation link.
		if ( isset( $_GET['mk_verify'], $_GET['uid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$uid   = absint( $_GET['uid'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$token = sanitize_text_field( wp_unslash( $_GET['mk_verify'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$hash  = (string) get_user_meta( $uid, '_mk_verify', true );
			if ( $hash && wp_check_password( $token, $hash ) ) {
				delete_user_meta( $uid, '_mk_verify' );
				if ( 'email' === get_user_meta( $uid, '_mk_pending', true ) ) {
					delete_user_meta( $uid, '_mk_pending' );
				}
				self::$notice = array( 'ok', __( 'Thanks, your email is confirmed. You can sign in now.', 'makaazi' ) );
			} else {
				self::$notice = array( 'error', __( 'That confirmation link has expired or was already used. Try signing in.', 'makaazi' ) );
			}
			return;
		}
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['mk_account'] ) ) { // phpcs:ignore
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['mk_account'] ) );
		if ( ! isset( $_POST['_mk_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_mk_nonce'] ) ), 'mk_account_' . $action ) ) {
			self::$notice = array( 'error', __( 'Your session expired. Please try again.', 'makaazi' ) );
			return;
		}
		$p = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised per field.
		switch ( $action ) {
			case 'login':
				self::do_login( $p );
				break;
			case 'register':
				self::do_register( $p );
				break;
			case 'profile':
				self::do_profile( $p );
				break;
		}
	}

	/**
	 * Sign in.
	 *
	 * @param array $p Posted data.
	 */
	private static function do_login( $p ) {
		$user = wp_signon(
			array(
				'user_login'    => isset( $p['log'] ) ? sanitize_user( $p['log'] ) : '',
				'user_password' => isset( $p['pwd'] ) ? (string) $p['pwd'] : '',
				'remember'      => ! empty( $p['remember'] ),
			),
			is_ssl()
		);
		if ( is_wp_error( $user ) ) {
			$code         = $user->get_error_code();
			self::$notice = array( 'error', 'makaazi_pending' === $code ? $user->get_error_message() : __( 'The email or password is not right. Check them and try again.', 'makaazi' ) );
			return;
		}
		wp_safe_redirect( makaazi_dashboard_url() );
		exit;
	}

	/**
	 * Sign up as a property owner.
	 *
	 * @param array $p Posted data.
	 */
	private static function do_register( $p ) {
		if ( ! self::can_register() ) {
			self::$notice = array( 'error', __( 'New accounts are created by invitation. Please contact us.', 'makaazi' ) );
			return;
		}
		if ( ! empty( $p['website'] ) || ( isset( $p['ts'] ) && time() - (int) $p['ts'] < 3 ) ) {
			self::$notice = array( 'ok', __( 'Thanks. Check your email to continue.', 'makaazi' ) );
			return;
		}
		$name  = isset( $p['full_name'] ) ? sanitize_text_field( $p['full_name'] ) : '';
		$email = isset( $p['email'] ) ? sanitize_email( $p['email'] ) : '';
		$phone = isset( $p['phone'] ) ? sanitize_text_field( $p['phone'] ) : '';
		$pass  = isset( $p['pass'] ) ? (string) $p['pass'] : '';
		if ( '' === $name ) {
			self::$errors['full_name'] = __( 'Enter your name.', 'makaazi' );
		}
		if ( ! is_email( $email ) ) {
			self::$errors['email'] = __( 'Enter a valid email address.', 'makaazi' );
		} elseif ( email_exists( $email ) ) {
			self::$errors['email'] = __( 'An account with this email already exists. Sign in instead.', 'makaazi' );
		}
		if ( strlen( preg_replace( '/\D+/', '', $phone ) ) < 9 ) {
			self::$errors['phone'] = __( 'Enter a phone number with at least 9 digits.', 'makaazi' );
		}
		if ( strlen( $pass ) < 8 ) {
			self::$errors['pass'] = __( 'Use at least 8 characters.', 'makaazi' );
		}
		if ( self::$errors ) {
			return;
		}
		$login = sanitize_user( current( explode( '@', $email ) ), true );
		$login = $login ? $login : 'owner';
		$base  = $login;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . ( ++$i );
		}
		$uid = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $pass,
				'display_name' => $name,
				'first_name'   => strtok( $name, ' ' ),
				'role'         => 'mk_owner',
			)
		);
		if ( is_wp_error( $uid ) ) {
			self::$notice = array( 'error', $uid->get_error_message() );
			return;
		}
		update_user_meta( $uid, '_mk_phone', $phone );
		$mode   = makaazi_settings( 'owner_registration' );
		$verify = (bool) makaazi_settings( 'owner_email_verify' );
		$site   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		if ( 'approval' === $mode ) {
			update_user_meta( $uid, '_mk_pending', 'approval' );
			/* translators: %s: name */
			wp_mail( get_option( 'admin_email' ), sprintf( __( 'New owner waiting for approval: %s', 'makaazi' ), $name ), sprintf( "%s\n%s\n%s\n\n%s", $name, $email, $phone, admin_url( 'users.php?role=mk_owner' ) ) );
			self::$notice = array( 'ok', __( 'Thanks for signing up. We will review your account and email you when you can sign in.', 'makaazi' ) );
		} elseif ( $verify ) {
			$token = wp_generate_password( 32, false );
			update_user_meta( $uid, '_mk_verify', wp_hash_password( $token ) );
			update_user_meta( $uid, '_mk_pending', 'email' );
			$link = add_query_arg(
				array(
					'mk_verify' => $token,
					'uid'       => $uid,
				),
				makaazi_dashboard_url()
			);
			/* translators: %s: site name */
			wp_mail( $email, sprintf( __( 'Confirm your %s account', 'makaazi' ), $site ), sprintf( /* translators: 1: name, 2: link */ __( "Hi %1\$s,\n\nConfirm your email to start listing properties:\n%2\$s\n\nIf you did not sign up, ignore this email.", 'makaazi' ), strtok( $name, ' ' ), $link ) );
			self::$notice = array( 'ok', __( 'Almost done. We sent a confirmation link to your email. Click it, then sign in.', 'makaazi' ) );
		} else {
			wp_set_current_user( $uid );
			wp_set_auth_cookie( $uid, true, is_ssl() );
			wp_safe_redirect( makaazi_dashboard_url( array( 'tab' => 'submit' ) ) );
			exit;
		}
	}

	/**
	 * Save profile (and the linked agent profile for agents).
	 *
	 * @param array $p Posted data.
	 */
	private static function do_profile( $p ) {
		$u = wp_get_current_user();
		if ( ! $u->exists() ) {
			return;
		}
		$name  = isset( $p['full_name'] ) ? sanitize_text_field( $p['full_name'] ) : '';
		$email = isset( $p['email'] ) ? sanitize_email( $p['email'] ) : '';
		$pass  = isset( $p['pass'] ) ? (string) $p['pass'] : '';
		if ( '' === $name ) {
			self::$errors['full_name'] = __( 'Enter your name.', 'makaazi' );
		}
		if ( ! is_email( $email ) ) {
			self::$errors['email'] = __( 'Enter a valid email address.', 'makaazi' );
		} elseif ( email_exists( $email ) && email_exists( $email ) !== $u->ID ) {
			self::$errors['email'] = __( 'Another account uses this email.', 'makaazi' );
		}
		if ( '' !== $pass && strlen( $pass ) < 8 ) {
			self::$errors['pass'] = __( 'Use at least 8 characters, or leave it empty to keep your password.', 'makaazi' );
		}
		if ( self::$errors ) {
			return;
		}
		$data = array(
			'ID'           => $u->ID,
			'display_name' => $name,
			'user_email'   => $email,
		);
		if ( '' !== $pass ) {
			$data['user_pass'] = $pass;
		}
		wp_update_user( $data );
		update_user_meta( $u->ID, '_mk_phone', isset( $p['phone'] ) ? sanitize_text_field( $p['phone'] ) : '' );
		if ( '' !== $pass ) {
			wp_set_auth_cookie( $u->ID, true, is_ssl() );
		}
		$aid = Makaazi_Agents::for_user( $u->ID );
		if ( $aid && makaazi_agents_on() ) {
			Makaazi_Agents::save_fields( $aid, isset( $p['agent'] ) && is_array( $p['agent'] ) ? $p['agent'] : array() );
			wp_update_post(
				array(
					'ID'           => $aid,
					'post_title'   => $name,
					'post_content' => isset( $p['bio'] ) ? wp_kses_post( $p['bio'] ) : get_post_field( 'post_content', $aid ),
				)
			);
			if ( ! empty( $_FILES['photo']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$type = wp_check_filetype( sanitize_file_name( wp_unslash( $_FILES['photo']['name'] ) ) ); // phpcs:ignore
				if ( in_array( $type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
					$att = media_handle_upload( 'photo', $aid );
					if ( ! is_wp_error( $att ) ) {
						set_post_thumbnail( $aid, $att );
					}
				}
			}
			makaazi_bump_cache();
		}
		self::$notice = array( 'ok', __( 'Profile saved.', 'makaazi' ) );
	}

	/**
	 * Block sign-in until the email is confirmed or the account is approved.
	 *
	 * @param WP_User|WP_Error|null $user User.
	 * @return WP_User|WP_Error|null
	 */
	public static function block_pending( $user ) {
		if ( ! $user instanceof WP_User ) {
			return $user;
		}
		$pending = get_user_meta( $user->ID, '_mk_pending', true );
		if ( 'email' === $pending ) {
			return new WP_Error( 'makaazi_pending', __( 'Please confirm your email first. Use the link we sent when you signed up.', 'makaazi' ) );
		}
		if ( 'approval' === $pending ) {
			return new WP_Error( 'makaazi_pending', __( 'Your account is waiting for approval. We will email you when it is ready.', 'makaazi' ) );
		}
		return $user;
	}

	/* ---------------------------------------------------------------------
	 * Users screen
	 * ------------------------------------------------------------------- */

	/**
	 * Users list column.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function user_columns( $cols ) {
		$cols['mk_account'] = __( 'Makaazi account', 'makaazi' );
		return $cols;
	}

	/**
	 * Column value.
	 *
	 * @param string $out Output.
	 * @param string $col Column.
	 * @param int    $uid User ID.
	 * @return string
	 */
	public static function user_column( $out, $col, $uid ) {
		if ( 'mk_account' !== $col ) {
			return $out;
		}
		$pending = get_user_meta( $uid, '_mk_pending', true );
		if ( 'email' === $pending ) {
			return '<span style="color:#b26200">' . esc_html__( 'Email not confirmed', 'makaazi' ) . '</span>';
		}
		if ( 'approval' === $pending ) {
			return '<span style="color:#b26200">' . esc_html__( 'Waiting for approval', 'makaazi' ) . '</span>';
		}
		$u = get_userdata( $uid );
		if ( Makaazi_Roles::is_agent( $u ) ) {
			$aid = Makaazi_Agents::for_user( $uid );
			return $aid ? '<a href="' . esc_url( get_edit_post_link( $aid ) ) . '">' . esc_html__( 'Agent profile', 'makaazi' ) . '</a>' : esc_html__( 'Agent (no profile linked)', 'makaazi' );
		}
		return Makaazi_Roles::is_owner( $u ) ? esc_html__( 'Owner · active', 'makaazi' ) : '';
	}

	/**
	 * "Activate" row action.
	 *
	 * @param array   $actions Actions.
	 * @param WP_User $u       User.
	 * @return array
	 */
	public static function user_actions( $actions, $u ) {
		if ( current_user_can( 'edit_users' ) && get_user_meta( $u->ID, '_mk_pending', true ) ) {
			$actions['mk_activate'] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=makaazi_activate_user&uid=' . $u->ID ), 'mk_activate_' . $u->ID ) ) . '">' . esc_html__( 'Activate', 'makaazi' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Activate a pending account.
	 */
	public static function activate_user() {
		$uid = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
		check_admin_referer( 'mk_activate_' . $uid );
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_die( esc_html__( 'You are not allowed to activate accounts.', 'makaazi' ) );
		}
		delete_user_meta( $uid, '_mk_pending' );
		delete_user_meta( $uid, '_mk_verify' );
		$u = get_userdata( $uid );
		if ( $u ) {
			/* translators: %s: site name */
			wp_mail( $u->user_email, sprintf( __( 'Your %s account is ready', 'makaazi' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ), sprintf( /* translators: %s: link */ __( "Your account is active. Sign in and list your property:\n%s", 'makaazi' ), makaazi_dashboard_url() ) );
		}
		wp_safe_redirect( admin_url( 'users.php?mk_activated=1' ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Shortcodes
	 * ------------------------------------------------------------------- */

	/**
	 * Field error helper.
	 *
	 * @param string $k Field.
	 * @return string
	 */
	public static function err( $k ) {
		return isset( self::$errors[ $k ] ) ? '<span class="mk-ferr" id="mk-err-' . esc_attr( $k ) . '">' . esc_html( self::$errors[ $k ] ) . '</span>' : '';
	}

	/**
	 * Notice HTML.
	 *
	 * @return string
	 */
	public static function notice() {
		$n = self::$notice;
		if ( ! $n && isset( $_GET['mk_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$msgs = array(
				'submitted' => array( 'ok', __( 'Thanks. Your listing has been sent for review. We will email you when it is live.', 'makaazi' ) ),
				'published' => array( 'ok', __( 'Your listing is live.', 'makaazi' ) ),
				'draft'     => array( 'ok', __( 'Draft saved. You can finish it any time.', 'makaazi' ) ),
				'updated'   => array( 'ok', __( 'Listing updated.', 'makaazi' ) ),
				'closed'    => array( 'ok', __( 'Listing marked as no longer available. Its page stays online but it is hidden from search.', 'makaazi' ) ),
				'relisted'  => array( 'ok', __( 'Listing is available again.', 'makaazi' ) ),
				'deleted'   => array( 'ok', __( 'Listing deleted.', 'makaazi' ) ),
			);
			$k    = sanitize_key( wp_unslash( $_GET['mk_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$n    = isset( $msgs[ $k ] ) ? $msgs[ $k ] : array();
		}
		if ( self::$errors && ! $n ) {
			$n = array( 'error', __( 'Please fix the highlighted fields.', 'makaazi' ) );
		}
		return $n ? '<div class="mk-notice mk-notice--' . esc_attr( $n[0] ) . '" role="' . ( 'error' === $n[0] ? 'alert' : 'status' ) . '">' . esc_html( $n[1] ) . '</div>' : '';
	}

	/**
	 * Posted value for re-filling forms.
	 *
	 * @param string $k       Key.
	 * @param string $default Default.
	 * @return string
	 */
	public static function old( $k, $default = '' ) {
		return isset( $_POST[ $k ] ) && is_string( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : $default; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * [makaazi_dashboard].
	 *
	 * @return string
	 */
	public static function dashboard() {
		Makaazi_Assets::enqueue();
		if ( ! makaazi_owners_on() && ! makaazi_agents_on() && ! current_user_can( 'edit_properties' ) ) {
			return '<div class="mk-block mk-dash"><p class="mk-notice mk-notice--info">' . esc_html__( 'Accounts are not available on this site.', 'makaazi' ) . '</p></div>';
		}
		if ( ! is_user_logged_in() ) {
			return '<div class="mk-block mk-dash mk-dash--auth">' . self::notice() . self::auth_forms() . '</div>';
		}
		$u    = wp_get_current_user();
		$tabs = array( 'listings' => __( 'My listings', 'makaazi' ) );
		if ( self::can_list( $u ) ) {
			$tabs['submit'] = __( 'Add property', 'makaazi' );
		}
		$tabs['enquiries'] = __( 'Enquiries', 'makaazi' );
		$tabs['profile']   = __( 'Profile', 'makaazi' );
		$tab               = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'listings'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'edit' === $tab ) {
			$tab = 'submit';
		}
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'listings';
		}
		$is_agent = Makaazi_Roles::is_agent( $u );
		if ( ! self::can_list( $u ) && ! $is_agent && ! Makaazi_Roles::is_owner( $u ) && ! current_user_can( 'edit_properties' ) ) {
			return '<div class="mk-block mk-dash"><div class="mk-dash__head"><div><p class="mk-eyebrow">' . esc_html__( 'My account', 'makaazi' ) . '</p><h1 class="mk-dash__title">' . esc_html( $u->display_name ) . '</h1></div><a class="mk-btn mk-btn--ghost mk-btn--sm" href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">' . esc_html__( 'Sign out', 'makaazi' ) . '</a></div><p>' . esc_html__( 'This account is for saving properties. To list a property, sign up as an owner or contact us.', 'makaazi' ) . '</p>' . ( makaazi_shortlist_url( 'favourites' ) ? '<p><a class="mk-btn mk-btn--sm" href="' . esc_url( makaazi_shortlist_url( 'favourites' ) ) . '">' . esc_html__( 'My saved properties', 'makaazi' ) . '</a></p>' : '' ) . '</div>';
		}
		$role = $is_agent ? __( 'Agent', 'makaazi' ) : ( Makaazi_Roles::is_owner( $u ) ? __( 'Property owner', 'makaazi' ) : __( 'Staff', 'makaazi' ) );
		$h    = '<div class="mk-block mk-dash"><div class="mk-dash__head"><div><p class="mk-eyebrow">' . esc_html( $role ) . '</p><h1 class="mk-dash__title">' . esc_html( sprintf( /* translators: %s: first name */ __( 'Welcome back, %s', 'makaazi' ), strtok( $u->display_name, ' ' ) ) ) . '</h1></div><div class="mk-dash__headacts">';
		if ( current_user_can( 'edit_properties' ) ) {
			$h .= '<a class="mk-btn mk-btn--ghost mk-btn--sm" href="' . esc_url( admin_url( 'edit.php?post_type=property' ) ) . '">' . esc_html__( 'Open admin', 'makaazi' ) . '</a>';
		}
		$h .= '<a class="mk-btn mk-btn--ghost mk-btn--sm" href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">' . esc_html__( 'Sign out', 'makaazi' ) . '</a></div></div>';
		if ( Makaazi_Roles::is_owner( $u ) && ! makaazi_owners_on() ) {
			$h .= '<div class="mk-notice mk-notice--info">' . esc_html__( 'Listing new properties is switched off at the moment. Your existing listings are shown below.', 'makaazi' ) . '</div>';
		}
		$h .= '<nav class="mk-dash__tabs" aria-label="' . esc_attr__( 'Account', 'makaazi' ) . '">';
		foreach ( $tabs as $k => $label ) {
			$h .= '<a href="' . esc_url( makaazi_dashboard_url( 'listings' === $k ? array() : array( 'tab' => $k ) ) ) . '"' . ( $k === $tab ? ' aria-current="page" class="is-on"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		$fav = makaazi_shortlist_url( 'favourites' );
		if ( $fav ) {
			$h .= '<a href="' . esc_url( $fav ) . '">' . esc_html__( 'Saved', 'makaazi' ) . '</a>';
		}
		$h .= '</nav>' . self::notice();
		switch ( $tab ) {
			case 'submit':
				$h .= Makaazi_Submission::form( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
				break;
			case 'enquiries':
				$h .= self::enquiries_tab( $u );
				break;
			case 'profile':
				$h .= self::profile_tab( $u );
				break;
			default:
				$h .= Makaazi_Submission::listings_tab( $u );
		}
		return $h . '</div>';
	}

	/**
	 * Sign in / sign up forms.
	 *
	 * @return string
	 */
	private static function auth_forms() {
		$mode = isset( $_GET['mode'] ) ? sanitize_key( $_GET['mode'] ) : ( isset( $_POST['mk_account'] ) && 'register' === $_POST['mk_account'] ? 'register' : 'login' ); // phpcs:ignore WordPress.Security.NonceVerification
		$reg  = self::can_register();
		if ( ! $reg ) {
			$mode = 'login';
		}
		$h  = '<div class="mk-auth">';
		$h .= '<div class="mk-auth__intro"><p class="mk-eyebrow">' . esc_html__( 'My account', 'makaazi' ) . '</p><h1 class="mk-dash__title">' . esc_html( 'register' === $mode ? __( 'List your property', 'makaazi' ) : __( 'Sign in', 'makaazi' ) ) . '</h1>';
		$h .= '<p>' . esc_html( 'register' === $mode ? __( 'Create a free account, add photos and details, and our team will check your listing before it goes live.', 'makaazi' ) : __( 'Owners and agents sign in here to manage listings and enquiries.', 'makaazi' ) ) . '</p>';
		if ( $reg ) {
			$h .= '<div class="mk-seg mk-auth__switch" role="tablist"><a role="tab" href="' . esc_url( makaazi_dashboard_url() ) . '" class="' . ( 'login' === $mode ? 'is-on' : '' ) . '" aria-selected="' . ( 'login' === $mode ? 'true' : 'false' ) . '">' . esc_html__( 'Sign in', 'makaazi' ) . '</a><a role="tab" href="' . esc_url( makaazi_dashboard_url( array( 'mode' => 'register' ) ) ) . '" class="' . ( 'register' === $mode ? 'is-on' : '' ) . '" aria-selected="' . ( 'register' === $mode ? 'true' : 'false' ) . '">' . esc_html__( 'Create account', 'makaazi' ) . '</a></div>';
		}
		$h .= '</div>';
		if ( 'register' === $mode ) {
			$h .= '<form class="mk-box mk-form" method="post" novalidate>' . wp_nonce_field( 'mk_account_register', '_mk_nonce', true, false ) . '<input type="hidden" name="mk_account" value="register"><input type="hidden" name="ts" value="' . esc_attr( time() ) . '">'
				. '<div class="mk-hp" aria-hidden="true"><label>' . esc_html__( 'Leave this empty', 'makaazi' ) . '<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
				. self::field( 'full_name', __( 'Full name', 'makaazi' ), 'text', 'name' )
				. self::field( 'email', __( 'Email', 'makaazi' ), 'email', 'email' )
				. self::field( 'phone', __( 'Phone', 'makaazi' ), 'tel', 'tel', '07XX XXX XXX' )
				. self::field( 'pass', __( 'Password (8+ characters)', 'makaazi' ), 'password', 'new-password', '', false )
				. ( 'approval' === makaazi_settings( 'owner_registration' ) ? '<p class="mk-hint">' . esc_html__( 'New accounts are checked by our team before you can sign in.', 'makaazi' ) . '</p>' : '' )
				. '<button type="submit" class="mk-btn mk-btn--block">' . esc_html__( 'Create account', 'makaazi' ) . '</button></form>';
		} else {
			$h .= '<form class="mk-box mk-form" method="post">' . wp_nonce_field( 'mk_account_login', '_mk_nonce', true, false ) . '<input type="hidden" name="mk_account" value="login">'
				. self::field( 'log', __( 'Email or username', 'makaazi' ), 'text', 'username' )
				. self::field( 'pwd', __( 'Password', 'makaazi' ), 'password', 'current-password', '', false )
				. '<label class="mk-check"><input type="checkbox" name="remember" value="1"> <span>' . esc_html__( 'Keep me signed in', 'makaazi' ) . '</span></label>'
				. '<button type="submit" class="mk-btn mk-btn--block">' . esc_html__( 'Sign in', 'makaazi' ) . '</button>'
				. '<a class="mk-link" href="' . esc_url( wp_lostpassword_url( makaazi_dashboard_url() ) ) . '">' . esc_html__( 'Forgot your password?', 'makaazi' ) . '</a>'
				. ( ! $reg && makaazi_owners_on() ? '<p class="mk-hint">' . esc_html__( 'Accounts are created by invitation. Contact us to list a property.', 'makaazi' ) . '</p>' : '' ) . '</form>';
		}
		return $h . '</div>';
	}

	/**
	 * Labelled input.
	 *
	 * @param string $k     Name.
	 * @param string $label Label.
	 * @param string $type  Type.
	 * @param string $ac    Autocomplete.
	 * @param string $ph    Placeholder.
	 * @param bool   $keep  Refill the posted value.
	 * @param string $val   Default value.
	 * @return string
	 */
	public static function field( $k, $label, $type = 'text', $ac = '', $ph = '', $keep = true, $val = '' ) {
		$id  = 'mk-f-' . $k;
		$err = self::err( $k );
		return '<label class="mk-field' . ( $err ? ' has-error' : '' ) . '" for="' . esc_attr( $id ) . '"><span>' . esc_html( $label ) . '</span><input id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $k ) . '" value="' . esc_attr( $keep ? self::old( $k, $val ) : '' ) . '"' . ( $ac ? ' autocomplete="' . esc_attr( $ac ) . '"' : '' ) . ( $ph ? ' placeholder="' . esc_attr( $ph ) . '"' : '' ) . ( $err ? ' aria-invalid="true" aria-describedby="mk-err-' . esc_attr( $k ) . '"' : '' ) . '>' . $err . '</label>';
	}

	/**
	 * Enquiries for this user's listings (and agent profile).
	 *
	 * @param WP_User $u User.
	 * @return string
	 */
	private static function enquiries_tab( $u ) {
		$pids = Makaazi_Submission::user_property_ids( $u, array( 'publish', 'pending', 'draft', 'private' ) );
		$aid  = Makaazi_Agents::for_user( $u->ID );
		$mq   = array( 'relation' => 'OR' );
		if ( $pids ) {
			$mq[] = array(
				'key'     => '_mk_property_id',
				'value'   => $pids,
				'compare' => 'IN',
			);
		}
		if ( $aid ) {
			$mq[] = array(
				'key'   => '_mk_agent_id',
				'value' => $aid,
			);
		}
		if ( count( $mq ) < 2 ) {
			return '<div class="mk-empty"><h3>' . esc_html__( 'No enquiries yet', 'makaazi' ) . '</h3><p>' . esc_html__( 'Messages from people interested in your listings will appear here.', 'makaazi' ) . '</p></div>';
		}
		$enq = get_posts(
			array(
				'post_type'      => 'mk_enquiry',
				'post_status'    => 'private',
				'posts_per_page' => 100,
				'meta_query'     => $mq, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		if ( ! $enq ) {
			return '<div class="mk-empty"><h3>' . esc_html__( 'No enquiries yet', 'makaazi' ) . '</h3><p>' . esc_html__( 'Messages from people interested in your listings will appear here.', 'makaazi' ) . '</p></div>';
		}
		$h = '<ul class="mk-enqlist">';
		foreach ( $enq as $e ) {
			$pid   = (int) get_post_meta( $e->ID, '_mk_property_id', true );
			$phone = (string) get_post_meta( $e->ID, '_mk_phone', true );
			$email = (string) get_post_meta( $e->ID, '_mk_email', true );
			$tel   = preg_replace( '/[^\d+]/', '', $phone );
			$wa    = '254' === substr( preg_replace( '/\D/', '', $phone ), 0, 3 ) ? preg_replace( '/\D/', '', $phone ) : '254' . ltrim( preg_replace( '/\D/', '', $phone ), '0' );
			$h    .= '<li class="mk-enq"><div class="mk-enq__top"><strong>' . esc_html( get_post_meta( $e->ID, '_mk_name', true ) ) . '</strong><time datetime="' . esc_attr( get_post_time( 'c', true, $e ) ) . '">' . esc_html( human_time_diff( get_post_time( 'U', true, $e ) ) . ' ' . __( 'ago', 'makaazi' ) ) . '</time></div>'
				. ( $pid ? '<a class="mk-enq__about" href="' . esc_url( get_permalink( $pid ) ) . '">' . esc_html( get_the_title( $pid ) ) . '</a>' : '<span class="mk-enq__about">' . esc_html__( 'General message', 'makaazi' ) . '</span>' )
				. '<p class="mk-enq__msg">' . esc_html( $e->post_content ) . '</p><div class="mk-enq__acts">'
				. ( $tel ? '<a class="mk-btn mk-btn--outline mk-btn--sm" href="tel:' . esc_attr( $tel ) . '">' . makaazi_icon( 'phone', 14 ) . ' ' . esc_html( $phone ) . '</a><a class="mk-btn mk-btn--wa mk-btn--sm" href="' . esc_url( makaazi_whatsapp_url( $wa, '' ) ) . '" target="_blank" rel="noopener">' . makaazi_icon( 'wa', 14 ) . ' WhatsApp</a>' : '' )
				. ( $email ? '<a class="mk-btn mk-btn--ghost mk-btn--sm" href="mailto:' . esc_attr( $email ) . '">' . makaazi_icon( 'mail', 14 ) . ' ' . esc_html( $email ) . '</a>' : '' ) . '</div></li>';
		}
		return $h . '</ul>';
	}

	/**
	 * Profile tab.
	 *
	 * @param WP_User $u User.
	 * @return string
	 */
	private static function profile_tab( $u ) {
		$aid = makaazi_agents_on() ? Makaazi_Agents::for_user( $u->ID ) : 0;
		$h   = '<form class="mk-box mk-form mk-form--wide" method="post" enctype="multipart/form-data" novalidate>' . wp_nonce_field( 'mk_account_profile', '_mk_nonce', true, false ) . '<input type="hidden" name="mk_account" value="profile">'
			. '<div class="mk-g2">' . self::field( 'full_name', __( 'Full name', 'makaazi' ), 'text', 'name', '', true, $u->display_name )
			. self::field( 'email', __( 'Email', 'makaazi' ), 'email', 'email', '', true, $u->user_email )
			. self::field( 'phone', __( 'Phone', 'makaazi' ), 'tel', 'tel', '', true, (string) get_user_meta( $u->ID, '_mk_phone', true ) )
			. self::field( 'pass', __( 'New password (leave empty to keep)', 'makaazi' ), 'password', 'new-password', '', false ) . '</div>';
		if ( $aid ) {
			$d  = Makaazi_Agents::data( $aid );
			$h .= '<h2 class="mk-sec__title">' . esc_html__( 'Public agent profile', 'makaazi' ) . '</h2><div class="mk-agent__who">' . Makaazi_Agents::photo( $d ) . '<label class="mk-field"><span>' . esc_html__( 'New photo (JPG, PNG or WebP)', 'makaazi' ) . '</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div><div class="mk-g2">';
			foreach ( Makaazi_Agents::fields() as $k => $f ) {
				$h .= '<label class="mk-field" for="mk-a-' . esc_attr( $k ) . '"><span>' . esc_html( $f[0] ) . '</span><input id="mk-a-' . esc_attr( $k ) . '" type="' . esc_attr( 'url' === $f[1] ? 'url' : ( 'email' === $f[1] ? 'email' : 'text' ) ) . '" name="agent[' . esc_attr( $k ) . ']" value="' . esc_attr( $d[ $k ] ) . '"></label>';
			}
			$h .= '</div><label class="mk-field" for="mk-a-bio"><span>' . esc_html__( 'Bio', 'makaazi' ) . '</span><textarea id="mk-a-bio" name="bio" rows="5">' . esc_textarea( get_post_field( 'post_content', $aid ) ) . '</textarea></label><p><a class="mk-link" href="' . esc_url( $d['url'] ) . '">' . esc_html__( 'View my public profile', 'makaazi' ) . '</a></p>';
		}
		return $h . '<button type="submit" class="mk-btn">' . esc_html__( 'Save profile', 'makaazi' ) . '</button></form>';
	}

	/**
	 * [makaazi_account_link].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function link( $atts ) {
		if ( ! makaazi_owners_on() && ! makaazi_agents_on() ) {
			return '';
		}
		$url = makaazi_dashboard_url();
		if ( ! $url ) {
			return '';
		}
		$a  = shortcode_atts(
			array(
				'signed_out' => __( 'Sign in', 'makaazi' ),
				'signed_in'  => __( 'My account', 'makaazi' ),
			),
			$atts,
			'makaazi_account_link'
		);
		$in = is_user_logged_in();
		Makaazi_Assets::enqueue();
		return '<a class="mk-acctlink" href="' . esc_url( $url ) . '">' . makaazi_icon( 'user', 17 ) . '<span>' . esc_html( $in ? $a['signed_in'] : $a['signed_out'] ) . '</span></a>';
	}

	/**
	 * Shortcode reference entries.
	 *
	 * @param array $docs Docs.
	 * @return array
	 */
	public static function docs( $docs ) {
		$docs[] = array(
			'tag'      => 'makaazi_dashboard',
			'group'    => __( 'Accounts', 'makaazi' ),
			'desc'     => __( 'My account: sign in and sign up when signed out; listings, Add property, enquiries and profile when signed in. Follows the Owner and Agent switches in Settings › Features.', 'makaazi' ),
			'atts'     => array(),
			'examples' => array( '[makaazi_dashboard]' ),
		);
		$docs[] = array(
			'tag'      => 'makaazi_account_link',
			'group'    => __( 'Accounts', 'makaazi' ),
			'desc'     => __( 'Header link: "Sign in" or "My account". Hidden when both Owners and Agents are switched off.', 'makaazi' ),
			'atts'     => array(
				'signed_out' => array( 'Sign in', '' ),
				'signed_in'  => array( 'My account', '' ),
			),
			'examples' => array( '[makaazi_account_link]', '[makaazi_account_link signed_out="List your property"]' ),
		);
		return $docs;
	}
}
