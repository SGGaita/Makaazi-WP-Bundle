<?php
/**
 * Property index: one row per property with the numbers search filters and sorts on.
 *
 * WordPress stores price, bedrooms and so on as post meta, which needs one table join per
 * condition. With thousands of listings that gets slow. This table keeps those values in
 * typed, indexed columns. Makaazi_Query uses it automatically once it has been built.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * The {prefix}mk_index table.
 */
class Makaazi_Index {

	const VER   = 1;
	const BATCH = 300;

	/**
	 * Properties to re-index at the end of the request.
	 *
	 * @var int[]
	 */
	private static $dirty = array();

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 2 );
		add_action( 'save_post_property', array( __CLASS__, 'mark' ), 30 );
		add_action( 'set_object_terms', array( __CLASS__, 'terms_changed' ), 10, 4 );
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $h ) {
			add_action( $h, array( __CLASS__, 'meta_changed' ), 10, 3 );
		}
		add_action( 'transition_post_status', array( __CLASS__, 'status_changed' ), 10, 3 );
		add_action( 'deleted_post', array( __CLASS__, 'delete' ) );
		add_action( 'shutdown', array( __CLASS__, 'flush' ) );
		add_action( 'makaazi_index_build', array( __CLASS__, 'build_batch' ) );
		add_action( 'admin_post_makaazi_index_rebuild', array( __CLASS__, 'handle_rebuild' ) );
		add_filter( 'posts_clauses', array( __CLASS__, 'clauses' ), 10, 2 );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'makaazi index', 'Makaazi_Index_CLI' );
		}
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'mk_index';
	}

	/**
	 * Create or upgrade the table, then queue a full build.
	 */
	public static function maybe_install() {
		if ( (int) get_option( 'makaazi_index_ver' ) >= self::VER ) {
			return;
		}
		self::install();
	}

	/**
	 * Create the table.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$t       = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$t} (
				post_id bigint(20) unsigned NOT NULL,
				status varchar(20) NOT NULL DEFAULT '',
				price decimal(15,2) DEFAULT NULL,
				por tinyint(1) NOT NULL DEFAULT 0,
				beds smallint(5) unsigned DEFAULT NULL,
				baths decimal(4,1) DEFAULT NULL,
				parking smallint(5) unsigned DEFAULT NULL,
				area decimal(12,2) DEFAULT NULL,
				featured tinyint(1) NOT NULL DEFAULT 0,
				closed tinyint(1) NOT NULL DEFAULT 0,
				agent_id bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (post_id),
				KEY status_price (closed,status,price),
				KEY price (price),
				KEY beds (beds),
				KEY area (area),
				KEY featured (featured),
				KEY agent (agent_id)
			) {$charset};"
		);
		update_option( 'makaazi_index_ver', self::VER );
		self::start_build();
	}

	/**
	 * Is the index complete and switched on?
	 *
	 * @return bool
	 */
	public static function ready() {
		/**
		 * Whether searches use the index table. Return false to fall back to meta queries.
		 *
		 * @param bool $use Use the index.
		 */
		return (bool) get_option( 'makaazi_index_ready' ) && (bool) apply_filters( 'makaazi_use_index', true );
	}

	/* ---------------------------------------------------------------------
	 * Keeping rows up to date
	 * ------------------------------------------------------------------- */

	/**
	 * Queue a property for re-indexing.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function mark( $post_id ) {
		if ( ! wp_is_post_revision( $post_id ) ) {
			self::$dirty[ (int) $post_id ] = true;
		}
	}

	/**
	 * Meta changed.
	 *
	 * @param int    $meta_id Meta ID.
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 */
	public static function meta_changed( $meta_id, $post_id, $key ) {
		unset( $meta_id );
		if ( in_array( $key, array( '_mk_price', '_mk_price_on_request', '_mk_bedrooms', '_mk_bathrooms', '_mk_parking', '_mk_area_sqm', '_mk_featured', '_mk_availability', '_mk_agent_id' ), true ) && 'property' === get_post_type( $post_id ) ) {
			self::mark( $post_id );
		}
	}

	/**
	 * Status term changed.
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     Terms.
	 * @param array  $tt_ids    Term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy.
	 */
	public static function terms_changed( $object_id, $terms, $tt_ids, $taxonomy ) {
		unset( $terms, $tt_ids );
		if ( 'property_status' === $taxonomy ) {
			self::mark( $object_id );
		}
	}

	/**
	 * Post status changed.
	 *
	 * @param string  $new  New status.
	 * @param string  $old  Old status.
	 * @param WP_Post $post Post.
	 */
	public static function status_changed( $new, $old, $post ) {
		if ( $new !== $old && 'property' === $post->post_type ) {
			self::mark( $post->ID );
		}
	}

	/**
	 * Write queued rows.
	 */
	public static function flush() {
		if ( ! self::$dirty || (int) get_option( 'makaazi_index_ver' ) < self::VER ) {
			return;
		}
		foreach ( array_keys( self::$dirty ) as $id ) {
			self::sync( $id );
		}
		self::$dirty = array();
	}

	/**
	 * The row for a property.
	 *
	 * @param int $id Property ID.
	 * @return array
	 */
	public static function row( $id ) {
		$num   = function ( $k ) use ( $id ) {
			$v = get_post_meta( $id, '_mk_' . $k, true );
			return '' === $v || null === $v ? null : (float) $v;
		};
		$avail = (string) get_post_meta( $id, '_mk_availability', true );
		return array(
			'post_id'  => (int) $id,
			'status'   => (string) makaazi_status( $id ),
			'price'    => $num( 'price' ),
			'por'      => get_post_meta( $id, '_mk_price_on_request', true ) ? 1 : 0,
			'beds'     => null === $num( 'bedrooms' ) ? null : (int) $num( 'bedrooms' ),
			'baths'    => $num( 'bathrooms' ),
			'parking'  => null === $num( 'parking' ) ? null : (int) $num( 'parking' ),
			'area'     => $num( 'area_sqm' ),
			'featured' => get_post_meta( $id, '_mk_featured', true ) ? 1 : 0,
			'closed'   => in_array( $avail, array( 'sold', 'let' ), true ) ? 1 : 0,
			'agent_id' => (int) get_post_meta( $id, '_mk_agent_id', true ),
		);
	}

	/**
	 * Insert or update one property.
	 *
	 * @param int $id Property ID.
	 */
	public static function sync( $id ) {
		global $wpdb;
		$post = get_post( $id );
		if ( ! $post || 'property' !== $post->post_type || 'trash' === $post->post_status ) {
			self::delete( $id );
			return;
		}
		$wpdb->replace( self::table(), self::row( $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Remove a row.
	 *
	 * @param int $id Post ID.
	 */
	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'post_id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/* ---------------------------------------------------------------------
	 * Full builds
	 * ------------------------------------------------------------------- */

	/**
	 * Start (or restart) a full build. Searches use meta queries until it finishes.
	 */
	public static function start_build() {
		update_option( 'makaazi_index_ready', 0 );
		update_option( 'makaazi_index_cursor', 0, false );
		// Small sites finish in this request; big ones continue in the background.
		if ( self::build_batch() ) {
			return;
		}
		if ( ! wp_next_scheduled( 'makaazi_index_build' ) ) {
			wp_schedule_single_event( time() + 5, 'makaazi_index_build' );
		}
	}

	/**
	 * Index the next batch.
	 *
	 * @param int $size Batch size.
	 * @return bool True when the build is complete.
	 */
	public static function build_batch( $size = 0 ) {
		global $wpdb;
		$size   = $size ? (int) $size : self::BATCH;
		$cursor = (int) get_option( 'makaazi_index_cursor', 0 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property' AND post_status NOT IN ('trash','auto-draft','inherit') AND ID > %d ORDER BY ID ASC LIMIT %d", $cursor, $size ) );
		foreach ( $ids as $id ) {
			self::sync( (int) $id );
		}
		if ( count( $ids ) < $size ) {
			// Remove rows for posts that no longer exist.
			$t = self::table();
			$wpdb->query( "DELETE FROM {$t} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property')" ); // phpcs:ignore WordPress.DB
			update_option( 'makaazi_index_ready', 1 );
			delete_option( 'makaazi_index_cursor' );
			makaazi_bump_cache();
			return true;
		}
		update_option( 'makaazi_index_cursor', (int) end( $ids ), false );
		wp_schedule_single_event( time() + 2, 'makaazi_index_build' );
		return false;
	}

	/**
	 * Rows and progress.
	 *
	 * @return array rows, total, ready
	 */
	public static function stats() {
		global $wpdb;
		$t = self::table();
		// phpcs:disable WordPress.DB
		$rows  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" );
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'property' AND post_status NOT IN ('trash','auto-draft','inherit')" );
		// phpcs:enable
		return array(
			'rows'  => $rows,
			'total' => $total,
			'ready' => self::ready(),
		);
	}

	/**
	 * Settings › General: Rebuild button.
	 */
	public static function handle_rebuild() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'makaazi' ) );
		}
		check_admin_referer( 'makaazi_index_rebuild' );
		self::start_build();
		wp_safe_redirect( admin_url( 'edit.php?post_type=property&page=makaazi-settings&tab=general&index=rebuilt' ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Querying
	 * ------------------------------------------------------------------- */

	/**
	 * Apply the mk_index query var: join the table, add conditions and the sort.
	 *
	 * @param array    $c Clauses.
	 * @param WP_Query $q Query.
	 * @return array
	 */
	public static function clauses( $c, $q ) {
		$ix = $q->get( 'mk_index' );
		if ( ! is_array( $ix ) || ! $ix ) {
			return $c;
		}
		global $wpdb;
		$t          = self::table();
		$c['join'] .= " INNER JOIN {$t} AS mki ON mki.post_id = {$wpdb->posts}.ID";
		$w          = array();
		if ( empty( $ix['include_closed'] ) ) {
			$w[] = 'mki.closed = 0';
		}
		if ( ! empty( $ix['min_price'] ) || ! empty( $ix['max_price'] ) ) {
			$w[] = 'mki.por = 0';
		}
		if ( ! empty( $ix['min_price'] ) ) {
			$w[] = $wpdb->prepare( 'mki.price >= %f', (float) $ix['min_price'] );
		}
		if ( ! empty( $ix['max_price'] ) ) {
			$w[] = $wpdb->prepare( 'mki.price <= %f', (float) $ix['max_price'] );
		}
		foreach ( array( 'beds', 'baths', 'parking' ) as $k ) {
			if ( ! empty( $ix[ $k ] ) ) {
				$w[] = $wpdb->prepare( "mki.{$k} >= %f", (float) $ix[ $k ] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}
		if ( ! empty( $ix['featured'] ) ) {
			$w[] = 'mki.featured = 1';
		}
		if ( ! empty( $ix['agent'] ) ) {
			$w[] = $wpdb->prepare( 'mki.agent_id = %d', (int) $ix['agent'] );
		}
		if ( $w ) {
			$c['where'] .= ' AND ' . implode( ' AND ', $w );
		}
		$date = "{$wpdb->posts}.post_date DESC, {$wpdb->posts}.ID DESC";
		$sort = isset( $ix['sort'] ) ? $ix['sort'] : '';
		switch ( $sort ) {
			case 'price_asc':
				$c['orderby'] = "mki.por ASC, mki.price IS NULL, mki.price ASC, {$date}";
				break;
			case 'price_desc':
				$c['orderby'] = "mki.por ASC, mki.price IS NULL, mki.price DESC, {$date}";
				break;
			case 'largest':
				$c['orderby'] = "mki.area IS NULL, mki.area DESC, {$date}";
				break;
			case 'featured':
				$c['orderby'] = "mki.featured DESC, {$date}";
				break;
		}
		return $c;
	}
}

/**
 * WP-CLI: wp makaazi index <rebuild|status>.
 */
class Makaazi_Index_CLI {

	/**
	 * Rebuild the property index now.
	 *
	 * ## EXAMPLES
	 *
	 *     wp makaazi index rebuild
	 */
	public function rebuild() {
		Makaazi_Index::install();
		update_option( 'makaazi_index_ready', 0 );
		update_option( 'makaazi_index_cursor', 0, false );
		$n = 0;
		while ( ! Makaazi_Index::build_batch( 500 ) ) {
			$n += 500;
			WP_CLI::log( sprintf( '%d…', $n ) );
		}
		wp_clear_scheduled_hook( 'makaazi_index_build' );
		$s = Makaazi_Index::stats();
		WP_CLI::success( sprintf( 'Indexed %d of %d properties.', $s['rows'], $s['total'] ) );
	}

	/**
	 * Show index status.
	 */
	public function status() {
		$s = Makaazi_Index::stats();
		WP_CLI::log( sprintf( 'Rows: %d  Properties: %d  In use: %s', $s['rows'], $s['total'], $s['ready'] ? 'yes' : 'no (building)' ) );
	}
}
