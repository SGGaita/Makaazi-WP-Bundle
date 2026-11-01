<?php
/**
 * Help: the admin Help Center (Properties › Help), contextual help tabs on Makaazi screens,
 * and the Help tab in the agent/owner dashboard.
 *
 * Developer documentation is separate: docs/developer in the Makaazi repository.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Help articles and where they are shown.
 */
class Makaazi_Help {

	const SLUG = 'makaazi-help';

	/**
	 * Hook in.
	 */
	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
			add_action( 'current_screen', array( __CLASS__, 'help_tabs' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		}
		add_filter( 'makaazi_dashboard_tabs', array( __CLASS__, 'dashboard_tab' ), 50, 2 );
		add_filter( 'makaazi_dashboard_tab_help', array( __CLASS__, 'dashboard_help' ), 10, 2 );
	}

	/**
	 * Articles for a context.
	 *
	 * @param string $context admin|account.
	 * @return array[]
	 */
	public static function articles( $context = 'admin' ) {
		$file     = MAKAAZI_DIR . 'includes/help/' . ( 'account' === $context ? 'account' : 'admin' ) . '-articles.php';
		$articles = file_exists( $file ) ? include $file : array();
		/**
		 * Filter the Help articles. Add your own, change the text, or remove ones you do not need.
		 *
		 * Each article: id, group, title, summary, body (HTML), keywords, screens (admin screen IDs for contextual help).
		 *
		 * @param array[] $articles Articles.
		 * @param string  $context  admin (Help Center and help tabs) or account (dashboard Help tab).
		 */
		$articles = apply_filters( 'makaazi_help_articles', (array) $articles, $context );
		$out      = array();
		foreach ( $articles as $a ) {
			if ( empty( $a['id'] ) || empty( $a['title'] ) ) {
				continue;
			}
			$out[ sanitize_key( $a['id'] ) ] = wp_parse_args(
				$a,
				array(
					'group'    => __( 'Other', 'makaazi' ),
					'summary'  => '',
					'body'     => '',
					'keywords' => '',
					'screens'  => array(),
				)
			);
		}
		return $out;
	}

	/**
	 * Where the developer documentation lives.
	 *
	 * @return string URL, or '' when unknown.
	 */
	public static function developer_docs_url() {
		/**
		 * Filter the link to the developer documentation shown in the Help Center.
		 *
		 * @param string $url URL of docs/developer in your copy of the repository.
		 */
		return (string) apply_filters( 'makaazi_developer_docs_url', '' );
	}

	/**
	 * Help Center URL.
	 *
	 * @param string $article Article ID.
	 * @return string
	 */
	public static function url( $article = '' ) {
		$url = admin_url( 'edit.php?post_type=property&page=' . self::SLUG );
		return $article ? add_query_arg( 'article', $article, $url ) : $url;
	}

	/**
	 * Replace link tokens and sanitize an article body.
	 *
	 * @param array  $a       Article.
	 * @param string $context admin|account.
	 * @return string
	 */
	public static function body( $a, $context = 'admin' ) {
		$all  = self::articles( $context );
		$html = preg_replace_callback(
			'/\{(url|help):([a-z0-9_:-]+)\}/',
			function ( $m ) use ( $all, $context ) {
				if ( 'help' === $m[1] ) {
					if ( ! isset( $all[ $m[2] ] ) ) {
						return '';
					}
					$href = 'account' === $context ? '#mk-help-' . $m[2] : self::url( $m[2] );
					return '<a href="' . esc_url( $href ) . '" data-mk-help-link="' . esc_attr( $m[2] ) . '">' . esc_html( $all[ $m[2] ]['title'] ) . '</a>';
				}
				return esc_url( self::token_url( $m[2] ) );
			},
			$a['body']
		);
		return wp_kses_post( $html );
	}

	/**
	 * URL for a {url:…} token.
	 *
	 * @param string $key Token key.
	 * @return string
	 */
	private static function token_url( $key ) {
		$parts = explode( ':', $key );
		switch ( $parts[0] ) {
			case 'settings':
				return admin_url( 'edit.php?post_type=property&page=makaazi-settings' . ( isset( $parts[1] ) ? '&tab=' . $parts[1] : '' ) );
			case 'new':
				return admin_url( 'post-new.php?post_type=property' );
			case 'properties':
				return admin_url( 'edit.php?post_type=property' );
			case 'pending':
				return admin_url( 'edit.php?post_type=property&post_status=pending' );
			case 'slider':
				return admin_url( 'edit.php?post_type=mk_slider' );
			case 'agents':
				return admin_url( 'edit.php?post_type=mk_agent' );
			case 'enquiries':
				return admin_url( 'edit.php?post_type=mk_enquiry' );
			case 'users':
				return admin_url( 'users.php' );
			case 'shortcodes':
				return admin_url( 'edit.php?post_type=property&page=makaazi-shortcodes' );
			case 'demo':
				return admin_url( 'edit.php?post_type=property&page=makaazi-demo' );
			case 'locations':
				return admin_url( 'edit-tags.php?taxonomy=property_location&post_type=property' );
			case 'submit':
			case 'profile':
				return makaazi_dashboard_url( array( 'tab' => $parts[0] ) );
			case 'enquiries-tab':
				return makaazi_dashboard_url( array( 'tab' => 'enquiries' ) );
			case 'listings':
				return makaazi_dashboard_url();
		}
		return '#';
	}

	/* ---------------------------------------------------------------------
	 * Admin Help Center
	 * ------------------------------------------------------------------- */

	/**
	 * Properties › Help.
	 */
	public static function menu() {
		add_submenu_page( 'edit.php?post_type=property', __( 'Makaazi Help', 'makaazi' ), __( 'Help', 'makaazi' ), 'edit_properties', self::SLUG, array( __CLASS__, 'page' ) );
	}

	/**
	 * Styles and script for the Help Center.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'makaazi-admin' );
		wp_enqueue_script( 'makaazi-help', MAKAAZI_URL . 'assets/js/help.js', array(), MAKAAZI_VERSION . '.' . (int) filemtime( MAKAAZI_DIR . 'assets/js/help.js' ), true );
	}

	/**
	 * Render the Help Center.
	 */
	public static function page() {
		$articles = self::articles( 'admin' );
		$current  = isset( $_GET['article'] ) ? sanitize_key( $_GET['article'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $articles[ $current ] ) ) {
			$current = (string) key( $articles );
		}
		$groups = array();
		foreach ( $articles as $id => $a ) {
			$groups[ $a['group'] ][ $id ] = $a;
		}
		$dev = self::developer_docs_url();
		?>
		<div class="wrap mk-help" data-mk-help>
			<div class="mk-help__top">
				<div>
					<h1><?php esc_html_e( 'Help Center', 'makaazi' ); ?></h1>
					<p class="mk-help__lede"><?php esc_html_e( 'Guides for running your property site: listings, the home page, agents, owner sign-ups and settings.', 'makaazi' ); ?></p>
				</div>
				<label class="mk-help__search">
					<span class="screen-reader-text"><?php esc_html_e( 'Search help', 'makaazi' ); ?></span>
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<input type="search" placeholder="<?php esc_attr_e( 'Search help, e.g. slider, approve, email', 'makaazi' ); ?>" data-mk-help-search autocomplete="off">
				</label>
			</div>
			<div class="mk-help__layout">
				<nav class="mk-help__nav" aria-label="<?php esc_attr_e( 'Help articles', 'makaazi' ); ?>">
					<p class="mk-help__count" data-mk-help-count hidden></p>
					<?php foreach ( $groups as $group => $items ) : ?>
						<div class="mk-help__group" data-mk-help-group>
							<h2><?php echo esc_html( $group ); ?></h2>
							<ul>
								<?php foreach ( $items as $id => $a ) : ?>
									<li data-mk-help-item="<?php echo esc_attr( $id ); ?>" data-search="<?php echo esc_attr( strtolower( $a['title'] . ' ' . $a['summary'] . ' ' . $a['keywords'] . ' ' . wp_strip_all_tags( $a['body'] ) ) ); ?>">
										<a href="<?php echo esc_url( self::url( $id ) ); ?>"<?php echo $id === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $a['title'] ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
					<p class="mk-help__empty" data-mk-help-empty hidden><?php esc_html_e( 'No guides match. Try another word.', 'makaazi' ); ?></p>
				</nav>
				<main class="mk-help__main">
					<?php foreach ( $articles as $id => $a ) : ?>
						<article class="mk-help__article" id="mk-help-<?php echo esc_attr( $id ); ?>" data-mk-help-article="<?php echo esc_attr( $id ); ?>"<?php echo $id === $current ? '' : ' hidden'; ?>>
							<p class="mk-help__crumb"><?php echo esc_html( $a['group'] ); ?></p>
							<h2><?php echo esc_html( $a['title'] ); ?></h2>
							<?php if ( $a['summary'] ) : ?>
								<p class="mk-help__summary"><?php echo esc_html( $a['summary'] ); ?></p>
							<?php endif; ?>
							<div class="mk-help__body"><?php echo self::body( $a ); // phpcs:ignore WordPress.Security.EscapeOutput -- kses'd. ?></div>
						</article>
					<?php endforeach; ?>
					<aside class="mk-help__more">
						<div>
							<h3><?php esc_html_e( 'Shortcode reference', 'makaazi' ); ?></h3>
							<p><?php esc_html_e( 'Every shortcode with its options and copy buttons.', 'makaazi' ); ?></p>
							<a class="button" href="<?php echo esc_url( self::token_url( 'shortcodes' ) ); ?>"><?php esc_html_e( 'Open Shortcodes', 'makaazi' ); ?></a>
						</div>
						<div>
							<h3><?php esc_html_e( 'For developers', 'makaazi' ); ?></h3>
							<p>
								<?php
								if ( $dev ) {
									esc_html_e( 'Hooks, template overrides, REST endpoints and the data model are in the separate developer documentation.', 'makaazi' );
								} else {
									echo wp_kses_post( __( 'Hooks, template overrides, REST endpoints and the data model are in the separate developer documentation: <code>docs/developer</code> in the Makaazi repository.', 'makaazi' ) );
								}
								?>
							</p>
							<?php if ( $dev ) : ?>
								<a class="button" href="<?php echo esc_url( $dev ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Developer docs', 'makaazi' ); ?></a>
							<?php endif; ?>
						</div>
					</aside>
				</main>
			</div>
			<p class="mk-help__credit">
				<?php
				/* translators: %s: Ascension Dynamics link */
				printf( esc_html__( 'Makaazi is made by %s.', 'makaazi' ), '<a href="https://ascensiondynamics.co/" target="_blank" rel="noopener">Ascension Dynamics</a>' );
				?>
			</p>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Contextual help tabs (the "Help" button, top right)
	 * ------------------------------------------------------------------- */

	/**
	 * Add help tabs to Makaazi screens.
	 *
	 * @param WP_Screen $screen Screen.
	 */
	public static function help_tabs( $screen ) {
		if ( ! $screen || ! current_user_can( 'edit_properties' ) ) {
			return;
		}
		$ours = in_array( $screen->post_type, array( 'property', 'mk_slider', 'mk_agent', 'mk_enquiry' ), true ) || 0 === strpos( (string) $screen->taxonomy, 'property_' ) || false !== strpos( $screen->id, 'makaazi-' );
		$list = array();
		foreach ( self::articles( 'admin' ) as $id => $a ) {
			if ( in_array( $screen->id, (array) $a['screens'], true ) ) {
				$list[ $id ] = $a;
			}
		}
		if ( ! $list && ! $ours ) {
			return;
		}
		if ( false !== strpos( $screen->id, self::SLUG ) ) {
			$list = array();
		}
		foreach ( array_slice( $list, 0, 5, true ) as $id => $a ) {
			$screen->add_help_tab(
				array(
					'id'       => 'makaazi-' . $id,
					'priority' => $ours ? 10 : 30, // After WordPress' own tabs on core screens.
					'title'    => $a['title'],
					'content'  => '<h2>' . esc_html( $a['title'] ) . '</h2>' . self::body( $a ),
				)
			);
		}
		$sidebar  = '<p><strong>' . esc_html__( 'Makaazi', 'makaazi' ) . '</strong></p>';
		$sidebar .= '<p><a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Help Center', 'makaazi' ) . '</a></p>';
		$sidebar .= '<p><a href="' . esc_url( self::token_url( 'shortcodes' ) ) . '">' . esc_html__( 'Shortcodes', 'makaazi' ) . '</a></p>';
		if ( self::developer_docs_url() ) {
			$sidebar .= '<p><a href="' . esc_url( self::developer_docs_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Developer docs', 'makaazi' ) . '</a></p>';
		}
		if ( $list || $ours ) {
			// Keep WordPress' own sidebar on core screens such as Users; add ours below it.
			$screen->set_help_sidebar( ( $ours ? '' : $screen->get_help_sidebar() ) . $sidebar );
		}
		if ( ! $list && $ours && false === strpos( $screen->id, self::SLUG ) ) {
			$screen->add_help_tab(
				array(
					'id'      => 'makaazi-help-center',
					'title'   => __( 'Help Center', 'makaazi' ),
					'content' => '<p>' . esc_html__( 'Step-by-step guides for every part of Makaazi are in the Help Center.', 'makaazi' ) . '</p><p><a class="button" href="' . esc_url( self::url() ) . '">' . esc_html__( 'Open the Help Center', 'makaazi' ) . '</a></p>',
				)
			);
		}
	}

	/* ---------------------------------------------------------------------
	 * Dashboard Help tab for agents and owners
	 * ------------------------------------------------------------------- */

	/**
	 * Add the Help tab.
	 *
	 * @param array   $tabs Tabs.
	 * @param WP_User $user User.
	 * @return array
	 */
	public static function dashboard_tab( $tabs, $user ) {
		unset( $user );
		$tabs['help'] = __( 'Help', 'makaazi' );
		return $tabs;
	}

	/**
	 * Render the Help tab.
	 *
	 * @param string  $html HTML.
	 * @param WP_User $user User.
	 * @return string
	 */
	public static function dashboard_help( $html, $user ) {
		unset( $user );
		$articles = self::articles( 'account' );
		$groups   = array();
		foreach ( $articles as $id => $a ) {
			$groups[ $a['group'] ][ $id ] = $a;
		}
		$h = '<div class="mk-dhelp"><p class="mk-dhelp__lede">' . esc_html__( 'Answers to common questions about listing with us. Still stuck? Reply to any of our emails or use the contact page.', 'makaazi' ) . '</p>';
		foreach ( $groups as $group => $items ) {
			$h .= '<section class="mk-dhelp__group"><h2 class="mk-dhelp__h">' . esc_html( $group ) . '</h2>';
			foreach ( $items as $id => $a ) {
				$h .= '<details class="mk-dhelp__item" id="mk-help-' . esc_attr( $id ) . '"><summary><span class="mk-dhelp__title">' . esc_html( $a['title'] ) . '</span>' . ( $a['summary'] ? '<span class="mk-dhelp__sum">' . esc_html( $a['summary'] ) . '</span>' : '' ) . '</summary><div class="mk-dhelp__body">' . self::body( $a, 'account' ) . '</div></details>';
			}
			$h .= '</section>';
		}
		return $html . $h . '</div>';
	}
}
