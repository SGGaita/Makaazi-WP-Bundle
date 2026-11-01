<?php
/**
 * Agent profiles.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * mk_agent post type, the agent on each property, agent shortcodes.
 */
class Makaazi_Agents {

	/**
	 * Agent profile fields: key => [label, type].
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'role'      => array( __( 'Job title', 'makaazi' ), 'text' ),
			'office'    => array( __( 'Office', 'makaazi' ), 'text' ),
			'phone'     => array( __( 'Phone', 'makaazi' ), 'text' ),
			'whatsapp'  => array( __( 'WhatsApp (e.g. 254712345678)', 'makaazi' ), 'text' ),
			'email'     => array( __( 'Email for enquiries', 'makaazi' ), 'email' ),
			'facebook'  => array( 'Facebook URL', 'url' ),
			'instagram' => array( 'Instagram URL', 'url' ),
			'linkedin'  => array( 'LinkedIn URL', 'url' ),
			'x'         => array( 'X URL', 'url' ),
		);
	}

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 6 );
		add_action( 'init', array( __CLASS__, 'shortcodes' ) );
		add_action( 'add_meta_boxes_mk_agent', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_mk_agent', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'add_meta_boxes_property', array( __CLASS__, 'property_box' ) );
		add_action( 'save_post_property', array( __CLASS__, 'save_property_agent' ), 15, 2 );
		add_filter( 'manage_mk_agent_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_mk_agent_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'makaazi_shortcode_docs', array( __CLASS__, 'docs' ) );
		add_action( 'save_post_mk_agent', 'makaazi_bump_cache' );
		add_action( 'init', array( __CLASS__, 'maybe_flush' ), 99 );
	}

	/**
	 * Register the post type (hidden when agents are switched off).
	 */
	public static function register() {
		$on = makaazi_agents_on();
		register_post_type(
			'mk_agent',
			array(
				'labels'          => array(
					'name'          => __( 'Agents', 'makaazi' ),
					'singular_name' => __( 'Agent', 'makaazi' ),
					'add_new'       => __( 'Add New Agent', 'makaazi' ),
					'add_new_item'  => __( 'Add New Agent', 'makaazi' ),
					'edit_item'     => __( 'Edit Agent', 'makaazi' ),
					'all_items'     => __( 'Agents', 'makaazi' ),
					'not_found'     => __( 'No agents yet.', 'makaazi' ),
					'featured_image' => __( 'Agent photo', 'makaazi' ),
					'set_featured_image' => __( 'Set agent photo', 'makaazi' ),
				),
				'public'          => $on,
				'show_ui'         => $on,
				'show_in_menu'    => 'edit.php?post_type=property',
				'has_archive'     => $on ? 'agents' : false,
				'rewrite'         => array(
					'slug'       => 'agents',
					'with_front' => false,
				),
				'supports'        => array( 'title', 'editor', 'thumbnail' ),
				'show_in_rest'    => $on,
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Refresh permalinks after the Agents switch changes.
	 */
	public static function maybe_flush() {
		if ( get_option( 'makaazi_flush_rules' ) ) {
			delete_option( 'makaazi_flush_rules' );
			flush_rewrite_rules( false );
		}
	}

	/**
	 * Shortcodes.
	 */
	public static function shortcodes() {
		add_shortcode( 'makaazi_agents', array( __CLASS__, 'sc_agents' ) );
		add_shortcode( 'makaazi_agent', array( __CLASS__, 'sc_agent' ) );
	}

	/* ---------------------------------------------------------------------
	 * Data helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Agent profile linked to a user.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public static function for_user( $user_id ) {
		if ( ! $user_id ) {
			return 0;
		}
		$q = get_posts(
			array(
				'post_type'      => 'mk_agent',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_mk_user_id', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => (int) $user_id, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return $q ? (int) $q[0] : 0;
	}

	/**
	 * The agent for a property (0 when none or agents are off).
	 *
	 * @param int $pid Property ID.
	 * @return int
	 */
	public static function of_property( $pid ) {
		if ( ! makaazi_agents_on() ) {
			return 0;
		}
		$aid = (int) get_post_meta( $pid, '_mk_agent_id', true );
		return ( $aid && 'publish' === get_post_status( $aid ) && 'mk_agent' === get_post_type( $aid ) ) ? $aid : 0;
	}

	/**
	 * Agent display data.
	 *
	 * @param int $aid Agent ID.
	 * @return array
	 */
	public static function data( $aid ) {
		$d = array(
			'id'    => $aid,
			'name'  => get_the_title( $aid ),
			'url'   => get_permalink( $aid ),
			'photo' => get_post_thumbnail_id( $aid ),
		);
		foreach ( array_keys( self::fields() ) as $k ) {
			$d[ $k ] = (string) get_post_meta( $aid, '_mk_' . $k, true );
		}
		$d['count'] = self::listing_count( $aid );
		return $d;
	}

	/**
	 * Number of live listings for an agent.
	 *
	 * @param int $aid Agent ID.
	 * @return int
	 */
	public static function listing_count( $aid ) {
		$f = Makaazi_Query::defaults();
		$q = new WP_Query(
			Makaazi_Query::args(
				$f,
				1,
				array(
					'fields'     => 'ids',
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'key'   => '_mk_agent_id',
							'value' => (int) $aid,
						),
					),
				)
			)
		);
		return (int) $q->found_posts;
	}

	/**
	 * Agent photo HTML (initials when there is no photo).
	 *
	 * @param array  $d    Agent data.
	 * @param string $size Image size.
	 * @param string $cls  Class.
	 * @return string
	 */
	public static function photo( $d, $size = 'thumbnail', $cls = 'mk-agent__avatar' ) {
		if ( $d['photo'] ) {
			return wp_get_attachment_image(
				$d['photo'],
				$size,
				false,
				array(
					'class' => $cls . ' is-photo',
					'alt' => $d['name'],
				)
			);
		}
		$initials = '';
		foreach ( array_slice( preg_split( '/\s+/', trim( $d['name'] ) ), 0, 2 ) as $w ) {
			$initials .= mb_substr( $w, 0, 1 );
		}
		return '<span class="' . esc_attr( $cls ) . '" aria-hidden="true">' . esc_html( mb_strtoupper( $initials ) ) . '</span>';
	}

	/* ---------------------------------------------------------------------
	 * Admin
	 * ------------------------------------------------------------------- */

	/**
	 * Agent screen boxes.
	 */
	public static function boxes() {
		add_meta_box( 'mk_agent_details', __( 'Agent details', 'makaazi' ), array( __CLASS__, 'details_box' ), 'mk_agent', 'normal', 'high' );
		add_meta_box( 'mk_agent_user', __( 'Sign-in account', 'makaazi' ), array( __CLASS__, 'user_box' ), 'mk_agent', 'side', 'default' );
	}

	/**
	 * Details box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function details_box( $post ) {
		wp_nonce_field( 'mk_agent_save', 'mk_agent_nonce' );
		echo '<p class="description">' . esc_html__( 'The title is the agent\'s name, the editor is their bio and the featured image is their photo.', 'makaazi' ) . '</p><div class="mk-g2">';
		foreach ( self::fields() as $k => $f ) {
			echo '<label>' . esc_html( $f[0] ) . '<input type="' . esc_attr( 'url' === $f[1] ? 'url' : ( 'email' === $f[1] ? 'email' : 'text' ) ) . '" class="widefat" name="mk_agent[' . esc_attr( $k ) . ']" value="' . esc_attr( get_post_meta( $post->ID, '_mk_' . $k, true ) ) . '"></label>';
		}
		echo '</div>';
	}

	/**
	 * Linked user box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function user_box( $post ) {
		$uid = (int) get_post_meta( $post->ID, '_mk_user_id', true );
		wp_dropdown_users(
			array(
				'name'             => 'mk_agent[user_id]',
				'selected'         => $uid,
				'show_option_none' => __( '— No sign-in —', 'makaazi' ),
				'option_none_value' => 0,
				'class'            => 'widefat',
				'role__not_in'     => array( 'mk_owner' ),
			)
		);
		echo '<p class="description">' . esc_html__( 'Link a user so this agent can sign in, see their enquiries and manage their own listings. The user is given the Agent role.', 'makaazi' ) . '</p>';
		echo '<p><a href="' . esc_url( admin_url( 'user-new.php' ) ) . '">' . esc_html__( 'Create a user', 'makaazi' ) . '</a></p>';
	}

	/**
	 * Save agent.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['mk_agent_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mk_agent_nonce'] ) ), 'mk_agent_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['mk_agent'] ) && is_array( $_POST['mk_agent'] ) ? wp_unslash( $_POST['mk_agent'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		self::save_fields( $post_id, $in );
		if ( isset( $in['user_id'] ) ) {
			$uid = absint( $in['user_id'] );
			update_post_meta( $post_id, '_mk_user_id', $uid );
			if ( $uid ) {
				$u = get_user_by( 'id', $uid );
				if ( $u && ! user_can( $u, 'edit_others_posts' ) && ! in_array( 'mk_agent', (array) $u->roles, true ) ) {
					$u->set_role( 'mk_agent' );
				}
			}
		}
	}

	/**
	 * Save profile fields (also used by the agent's own dashboard).
	 *
	 * @param int   $post_id Agent ID.
	 * @param array $in      Raw values.
	 */
	public static function save_fields( $post_id, $in ) {
		foreach ( self::fields() as $k => $f ) {
			if ( ! isset( $in[ $k ] ) ) {
				continue;
			}
			$v = 'url' === $f[1] ? esc_url_raw( $in[ $k ] ) : ( 'email' === $f[1] ? sanitize_email( $in[ $k ] ) : sanitize_text_field( $in[ $k ] ) );
			update_post_meta( $post_id, '_mk_' . $k, $v );
		}
	}

	/**
	 * Agent select on the property screen.
	 */
	public static function property_box() {
		if ( ! makaazi_agents_on() ) {
			return;
		}
		add_meta_box(
			'mk_property_agent',
			__( 'Agent', 'makaazi' ),
			function ( $post ) {
				$cur    = (int) get_post_meta( $post->ID, '_mk_agent_id', true );
				$agents = get_posts(
					array(
						'post_type'      => 'mk_agent',
						'posts_per_page' => 200,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				);
				$mine   = self::for_user( get_current_user_id() );
				if ( ! current_user_can( 'edit_others_properties' ) && $mine ) {
					echo '<p>' . esc_html( get_the_title( $mine ) ) . '</p><input type="hidden" name="mk_property_agent" value="' . (int) $mine . '">';
					return;
				}
				echo '<select name="mk_property_agent" class="widefat"><option value="0">' . esc_html__( '— Site contact —', 'makaazi' ) . '</option>';
				foreach ( $agents as $a ) {
					echo '<option value="' . (int) $a->ID . '"' . selected( $cur, $a->ID, false ) . '>' . esc_html( $a->post_title ) . '</option>';
				}
				echo '</select><p class="description">' . esc_html__( 'Shown in the contact box. Enquiries go to this agent.', 'makaazi' ) . '</p>';
			},
			'property',
			'side',
			'default'
		);
	}

	/**
	 * Save the property's agent.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_property_agent( $post_id, $post ) {
		if ( ! isset( $_POST['makaazi_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['makaazi_nonce'] ) ), 'makaazi_save' ) ) {
			return;
		}
		if ( ! isset( $_POST['mk_property_agent'] ) ) {
			return;
		}
		$aid  = absint( $_POST['mk_property_agent'] );
		$mine = self::for_user( get_current_user_id() );
		if ( ! current_user_can( 'edit_others_properties' ) ) {
			$aid = $mine;
		}
		update_post_meta( $post_id, '_mk_agent_id', $aid );
	}

	/**
	 * List columns.
	 *
	 * @param array $c Columns.
	 * @return array
	 */
	public static function columns( $c ) {
		return array(
			'cb'          => $c['cb'],
			'mk_photo'    => '',
			'title'       => __( 'Name', 'makaazi' ),
			'mk_contact'  => __( 'Contact', 'makaazi' ),
			'mk_listings' => __( 'Listings', 'makaazi' ),
			'mk_user'     => __( 'Sign-in', 'makaazi' ),
		);
	}

	/**
	 * Column values.
	 *
	 * @param string $col Column.
	 * @param int    $id  Post ID.
	 */
	public static function column( $col, $id ) {
		$d = self::data( $id );
		if ( 'mk_photo' === $col ) {
			echo self::photo( $d, array( 40, 40 ), 'mk-agent-thumb' ); // phpcs:ignore
		} elseif ( 'mk_contact' === $col ) {
			echo esc_html( $d['phone'] ) . '<br>' . esc_html( $d['email'] );
		} elseif ( 'mk_listings' === $col ) {
			echo (int) $d['count'];
		} elseif ( 'mk_user' === $col ) {
			$u = get_user_by( 'id', (int) get_post_meta( $id, '_mk_user_id', true ) );
			echo $u ? esc_html( $u->user_login ) : '—';
		}
	}

	/* ---------------------------------------------------------------------
	 * Front end
	 * ------------------------------------------------------------------- */

	/**
	 * Contact card used on the property page.
	 *
	 * @param int    $aid Agent ID.
	 * @param string $msg WhatsApp message.
	 * @return string
	 */
	public static function card( $aid, $msg = '' ) {
		$d   = self::data( $aid );
		$wa  = $d['whatsapp'] ? makaazi_whatsapp_url( $d['whatsapp'], $msg ) : '';
		$tel = preg_replace( '/[^\d+]/', '', $d['phone'] );
		$h   = '<div class="mk-box mk-agent"><div class="mk-agent__who">' . self::photo( $d ) . '<div><a class="mk-agent__name" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['name'] ) . '</a><span>' . esc_html( trim( $d['role'] . ( $d['office'] ? ' · ' . $d['office'] : '' ), ' ·' ) ) . '</span></div></div>';
		$h  .= '<div class="mk-row2">' . ( $wa ? '<a class="mk-btn mk-btn--wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">' . makaazi_icon( 'wa' ) . ' WhatsApp</a>' : '' ) . ( $tel ? '<a class="mk-btn mk-btn--outline" href="tel:' . esc_attr( $tel ) . '">' . makaazi_icon( 'phone' ) . ' ' . esc_html__( 'Call', 'makaazi' ) . '</a>' : '' ) . '</div>';
		$h  .= $d['phone'] ? '<p class="mk-agent__num">' . makaazi_icon( 'phone', 14 ) . ' ' . esc_html( $d['phone'] ) . '</p>' : '';
		/* translators: 1: first name, 2: number of listings */
		$h .= '<a class="mk-link" href="' . esc_url( $d['url'] ) . '">' . esc_html( sprintf( _n( 'See %1$s\'s %2$d listing', 'See all %2$d of %1$s\'s listings', $d['count'], 'makaazi' ), strtok( $d['name'], ' ' ), $d['count'] ) ) . '</a>';
		return $h . '</div>';
	}

	/**
	 * [makaazi_agents].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function sc_agents( $atts ) {
		if ( ! makaazi_agents_on() ) {
			return '';
		}
		$a = shortcode_atts(
			array(
				'count'   => 12,
				'columns' => 4,
				'orderby' => 'name',
				'ids'     => '',
				'eyebrow' => '',
				'title'   => '',
			),
			$atts,
			'makaazi_agents'
		);
		$args = array(
			'post_type'      => 'mk_agent',
			'posts_per_page' => max( 1, min( 100, (int) $a['count'] ) ),
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		if ( $a['ids'] ) {
			$args['post__in'] = array_map( 'absint', explode( ',', $a['ids'] ) );
			$args['orderby']  = 'post__in';
		}
		$agents = get_posts( $args );
		if ( ! $agents ) {
			return '';
		}
		$data = array_map(
			function ( $p ) {
				return Makaazi_Agents::data( $p->ID );
			},
			$agents
		);
		if ( 'listings' === $a['orderby'] ) {
			usort(
				$data,
				function ( $x, $y ) {
					return $y['count'] - $x['count'];
				}
			);
		}
		Makaazi_Assets::enqueue();
		$lvl = Makaazi_Render::page_level();
		$hl  = $lvl + 1;
		$h   = '<div class="mk-block">' . Makaazi_Render::section_head( $a['eyebrow'], $a['title'], '', '', '', $lvl ) . '<div class="mk-agents" style="--mk-cols:' . max( 1, min( 6, (int) $a['columns'] ) ) . '">';
		foreach ( $data as $d ) {
			$wa = $d['whatsapp'] ? makaazi_whatsapp_url( $d['whatsapp'], '' ) : '';
			/* translators: %d: listings */
			$h .= '<article class="mk-agentcard"><a class="mk-agentcard__photo" href="' . esc_url( $d['url'] ) . '" tabindex="-1" aria-hidden="true">' . self::photo( $d, 'medium', 'mk-agentcard__img' ) . '</a><div class="mk-agentcard__body"><h' . $hl . '><a href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['name'] ) . '</a></h' . $hl . '><p>' . esc_html( $d['role'] ) . '</p><p class="mk-agentcard__n">' . esc_html( sprintf( _n( '%d listing', '%d listings', $d['count'], 'makaazi' ), $d['count'] ) ) . '</p>'
				. '<div class="mk-row2"><a class="mk-btn mk-btn--outline mk-btn--sm" href="' . esc_url( $d['url'] ) . '">' . esc_html__( 'Profile', 'makaazi' ) . '</a>' . ( $wa ? '<a class="mk-btn mk-btn--wa mk-btn--sm" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( 'WhatsApp ' . $d['name'] ) . '">' . makaazi_icon( 'wa', 15 ) . '</a>' : '' ) . '</div></div></article>';
		}
		return $h . '</div></div>';
	}

	/**
	 * [makaazi_agent] profile with listings.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function sc_agent( $atts ) {
		if ( ! makaazi_agents_on() ) {
			return '';
		}
		$a   = shortcode_atts(
			array(
				'id'    => 0,
				'slug'  => '',
				'count' => 9,
			),
			$atts,
			'makaazi_agent'
		);
		$aid = (int) $a['id'];
		if ( ! $aid && $a['slug'] ) {
			$p   = get_page_by_path( sanitize_title( $a['slug'] ), OBJECT, 'mk_agent' );
			$aid = $p ? $p->ID : 0;
		}
		if ( ! $aid && 'mk_agent' === get_post_type() ) {
			$aid = get_the_ID();
		}
		if ( ! $aid || 'mk_agent' !== get_post_type( $aid ) ) {
			return '';
		}
		$d       = self::data( $aid );
		$wa      = $d['whatsapp'] ? makaazi_whatsapp_url( $d['whatsapp'], '' ) : '';
		$tel     = preg_replace( '/[^\d+]/', '', $d['phone'] );
		$socials = '';
		foreach ( array(
			'facebook' => 'Facebook',
			'instagram' => 'Instagram',
			'linkedin' => 'LinkedIn',
			'x' => 'X',
		) as $k => $l ) {
			if ( $d[ $k ] ) {
				$socials .= '<a href="' . esc_url( $d[ $k ] ) . '" target="_blank" rel="noopener">' . esc_html( $l ) . '</a>';
			}
		}
		Makaazi_Assets::enqueue();
		$bio = wpautop( get_post_field( 'post_content', $aid ) );
		$h   = '<div class="mk-block mk-agentpage"><nav class="mk-crumb" aria-label="' . esc_attr__( 'Breadcrumb', 'makaazi' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'makaazi' ) . '</a><span aria-hidden="true">›</span><a href="' . esc_url( get_post_type_archive_link( 'mk_agent' ) ) . '">' . esc_html__( 'Agents', 'makaazi' ) . '</a><span aria-hidden="true">›</span><span aria-current="page">' . esc_html( $d['name'] ) . '</span></nav>';
		$h  .= '<div class="mk-agenthead">' . self::photo( $d, 'medium', 'mk-agenthead__img' ) . '<div class="mk-agenthead__main"><p class="mk-eyebrow">' . esc_html( trim( $d['role'] . ( $d['office'] ? ' · ' . $d['office'] : '' ), ' ·' ) ) . '</p><h1 class="mk-agenthead__name">' . esc_html( $d['name'] ) . '</h1><div class="mk-desc">' . wp_kses_post( $bio ) . '</div>'
			. ( $d['phone'] ? '<p class="mk-agent__num">' . makaazi_icon( 'phone', 14 ) . ' ' . esc_html( $d['phone'] ) . '</p>' : '' ) . ( $d['email'] ? '<p class="mk-agent__num">' . makaazi_icon( 'mail', 14 ) . ' ' . esc_html( $d['email'] ) . '</p>' : '' )
			. ( $socials ? '<p class="mk-agent__social">' . $socials . '</p>' : '' ) . '</div>';
		$h  .= '<div class="mk-agenthead__side"><div class="mk-box"><div class="mk-row2">' . ( $wa ? '<a class="mk-btn mk-btn--wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">' . makaazi_icon( 'wa' ) . ' WhatsApp</a>' : '' ) . ( $tel ? '<a class="mk-btn mk-btn--outline" href="tel:' . esc_attr( $tel ) . '">' . makaazi_icon( 'phone' ) . ' ' . esc_html__( 'Call', 'makaazi' ) . '</a>' : '' ) . '</div></div>'
			. Makaazi_Render::enquiry_form( 0, $aid, sprintf( /* translators: %s: agent first name */ __( 'Hi %s, I found you on our website and would like help finding a property.', 'makaazi' ), strtok( $d['name'], ' ' ) ), sprintf( /* translators: %s: agent first name */ __( 'Message %s', 'makaazi' ), strtok( $d['name'], ' ' ) ) ) . '</div></div>';
		/* translators: 1: first name, 2: count */
		$h .= Makaazi_Shortcodes::properties(
			array(
				'agent'      => $aid,
				'count'      => (int) $a['count'],
				'columns'    => 3,
				'pagination' => 'loadmore',
				'view_all'   => 'no',
				/* translators: 1: agent first name, 2: number of listings */
				'title'      => sprintf( __( 'Listings by %1$s (%2$d)', 'makaazi' ), strtok( $d['name'], ' ' ), $d['count'] ),
				'empty_text' => __( 'No live listings at the moment.', 'makaazi' ),
			)
		);
		return $h . '</div>';
	}

	/**
	 * Shortcode reference entries.
	 *
	 * @param array $docs Docs.
	 * @return array
	 */
	public static function docs( $docs ) {
		$docs[] = array(
			'tag'      => 'makaazi_agents',
			'group'    => __( 'Agents', 'makaazi' ),
			'desc'     => __( 'Grid of agent cards with listing counts. Hidden when Agents is switched off in Settings › Features.', 'makaazi' ),
			'atts'     => array(
				'count'   => array( '12', '' ),
				'columns' => array( '4', '1–6' ),
				'orderby' => array( 'name', 'name, listings' ),
				'ids'     => array( '', __( 'Agent IDs in order', 'makaazi' ) ),
				'eyebrow' => array( '', '' ),
				'title'   => array( '', '' ),
			),
			'examples' => array( '[makaazi_agents columns="4" title="Our agents"]' ),
		);
		$docs[] = array(
			'tag'      => 'makaazi_agent',
			'group'    => __( 'Agents', 'makaazi' ),
			'desc'     => __( 'Agent profile: photo, bio, contacts, a message form and their listings. Used by the agent page template.', 'makaazi' ),
			'atts'     => array(
				'id'    => array( '', __( 'Agent ID; defaults to the current agent page', 'makaazi' ) ),
				'slug'  => array( '', __( 'Or the agent slug, e.g. grace-achieng', 'makaazi' ) ),
				'count' => array( '9', __( 'Listings per page', 'makaazi' ) ),
			),
			'examples' => array( '[makaazi_agent slug="grace-achieng"]' ),
		);
		return $docs;
	}
}
