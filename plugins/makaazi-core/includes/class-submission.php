<?php
/**
 * Front-end listing submission, owner/agent listings, moderation and view counts.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Submission.
 */
class Makaazi_Submission {

	/**
	 * Values posted back after a failed save.
	 *
	 * @var array|null
	 */
	private static $posted = null;

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'handle' ), 6 );
		add_action( 'template_redirect', array( __CLASS__, 'count_view' ), 20 );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_makaazi_moderate', array( __CLASS__, 'moderate' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'on_status' ), 10, 3 );
		add_action( 'admin_menu', array( __CLASS__, 'pending_bubble' ), 99 );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_filter( 'display_post_states', array( __CLASS__, 'post_states' ), 10, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Ownership
	 * ------------------------------------------------------------------- */

	/**
	 * Property IDs a user manages (authored, or assigned to their agent profile).
	 *
	 * @param WP_User $u        User.
	 * @param array   $statuses Statuses.
	 * @return int[]
	 */
	public static function user_property_ids( $u, $statuses = array( 'publish', 'pending', 'draft' ) ) {
		$ids = get_posts(
			array(
				'post_type'      => 'property',
				'post_status'    => $statuses,
				'author'         => $u->ID,
				'posts_per_page' => 500,
				'fields'         => 'ids',
			)
		);
		$aid = Makaazi_Agents::for_user( $u->ID );
		if ( $aid ) {
			$ids = array_merge(
				$ids,
				get_posts(
					array(
						'post_type'      => 'property',
						'post_status'    => $statuses,
						'posts_per_page' => 500,
						'fields'         => 'ids',
						'meta_key'       => '_mk_agent_id', // phpcs:ignore WordPress.DB.SlowDBQuery
						'meta_value'     => $aid, // phpcs:ignore WordPress.DB.SlowDBQuery
					)
				)
			);
		}
		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}

	/**
	 * Can the user edit this property from the front end?
	 *
	 * @param int     $pid Property ID.
	 * @param WP_User $u   User.
	 * @return bool
	 */
	public static function can_manage( $pid, $u = null ) {
		$u = $u ? $u : wp_get_current_user();
		if ( ! $pid || 'property' !== get_post_type( $pid ) || ! $u->exists() ) {
			return false;
		}
		if ( user_can( $u, 'edit_others_properties' ) ) {
			return true;
		}
		if ( (int) get_post_field( 'post_author', $pid ) === $u->ID ) {
			return true;
		}
		$aid = Makaazi_Agents::for_user( $u->ID );
		return $aid && makaazi_agents_on() && (int) get_post_meta( $pid, '_mk_agent_id', true ) === $aid;
	}

	/* ---------------------------------------------------------------------
	 * Listings tab
	 * ------------------------------------------------------------------- */

	/**
	 * Listing state label.
	 *
	 * @param int $pid Property ID.
	 * @return array [key, label]
	 */
	public static function state( $pid ) {
		$status = get_post_status( $pid );
		$avail  = makaazi_meta( $pid, 'availability', 'available' );
		if ( 'publish' === $status ) {
			if ( 'sold' === $avail ) {
				return array( 'closed', __( 'Sold', 'makaazi' ) );
			}
			if ( 'let' === $avail ) {
				return array( 'closed', __( 'Let', 'makaazi' ) );
			}
			return array( 'live', __( 'Live', 'makaazi' ) );
		}
		if ( 'pending' === $status ) {
			return array( 'pending', __( 'Pending review', 'makaazi' ) );
		}
		if ( get_post_meta( $pid, '_mk_rejected', true ) ) {
			return array( 'rejected', __( 'Needs changes', 'makaazi' ) );
		}
		return array( 'draft', __( 'Draft', 'makaazi' ) );
	}

	/**
	 * Small POST form for a listing action.
	 *
	 * @param int    $pid    Property ID.
	 * @param string $action Action.
	 * @param string $label  Button label.
	 * @param string $cls    Extra class.
	 * @return string
	 */
	private static function action_form( $pid, $action, $label, $cls = 'mk-btn--ghost' ) {
		return '<form method="post" class="mk-inline-form"' . ( 'delete' === $action ? ' data-mk-confirm="' . esc_attr__( 'Tap again to delete', 'makaazi' ) . '"' : '' ) . '>' . wp_nonce_field( 'mk_listing_' . $action . '_' . $pid, '_mk_nonce', true, false ) . '<input type="hidden" name="mk_listing" value="' . esc_attr( $action ) . '"><input type="hidden" name="id" value="' . (int) $pid . '"><button type="submit" class="mk-btn ' . esc_attr( $cls ) . ' mk-btn--sm">' . esc_html( $label ) . '</button></form>';
	}

	/**
	 * The user's listings.
	 *
	 * @param WP_User $u User.
	 * @return string
	 */
	public static function listings_tab( $u ) {
		$ids  = self::user_property_ids( $u );
		$can  = Makaazi_Accounts::can_list( $u );
		$add  = $can ? '<a class="mk-btn mk-btn--sm" href="' . esc_url( makaazi_dashboard_url( array( 'tab' => 'submit' ) ) ) . '">+ ' . esc_html__( 'Add property', 'makaazi' ) . '</a>' : '';
		if ( ! $ids ) {
			return '<div class="mk-empty"><h3>' . esc_html__( 'No listings yet', 'makaazi' ) . '</h3><p>' . esc_html__( 'Add your first property. It takes about five minutes with photos ready.', 'makaazi' ) . '</p>' . $add . '</div>';
		}
		$posts  = get_posts(
			array(
				'post_type'      => 'property',
				'post__in'       => $ids,
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => 500,
				'orderby'        => 'modified',
			)
		);
		$counts = array();
		foreach ( $posts as $p ) {
			$st              = self::state( $p->ID );
			$counts[ $st[0] ] = isset( $counts[ $st[0] ] ) ? $counts[ $st[0] ] + 1 : 1;
		}
		$h = '<div class="mk-dash__bar"><div class="mk-dash__stats">';
		foreach ( array(
			'live'     => __( 'Live', 'makaazi' ),
			'pending'  => __( 'Pending review', 'makaazi' ),
			'rejected' => __( 'Needs changes', 'makaazi' ),
			'draft'    => __( 'Drafts', 'makaazi' ),
			'closed'   => __( 'Sold / let', 'makaazi' ),
		) as $k => $l ) {
			if ( ! empty( $counts[ $k ] ) ) {
				$h .= '<span class="mk-pill mk-pill--' . esc_attr( $k ) . '">' . esc_html( $l ) . ' ' . (int) $counts[ $k ] . '</span>';
			}
		}
		$h .= '</div>' . $add . '</div><ul class="mk-mylist">';
		foreach ( $posts as $p ) {
			$st     = self::state( $p->ID );
			$g      = makaazi_gallery_ids( $p->ID );
			$reason = (string) get_post_meta( $p->ID, '_mk_rejected', true );
			$edit   = makaazi_dashboard_url(
				array(
					'tab' => 'submit',
					'id'  => $p->ID,
				)
			);
			$h     .= '<li class="mk-mylist__row"><a class="mk-mylist__img" href="' . esc_url( 'publish' === $p->post_status ? get_permalink( $p ) : $edit ) . '" tabindex="-1">' . ( $g ? wp_get_attachment_image( $g[0], 'thumbnail', false, array( 'alt' => '' ) ) : '<img src="' . esc_url( makaazi_placeholder() ) . '" alt="">' ) . '</a>'
				. '<div class="mk-mylist__main"><div class="mk-mylist__top"><h3>' . esc_html( $p->post_title ? $p->post_title : __( '(Untitled)', 'makaazi' ) ) . '</h3><span class="mk-pill mk-pill--' . esc_attr( $st[0] ) . '">' . esc_html( $st[1] ) . '</span></div>'
				. '<p class="mk-mylist__meta"><span>' . esc_html( makaazi_meta( $p->ID, 'ref' ) ) . '</span><span>' . wp_kses_post( makaazi_price( $p->ID, false ) ) . '</span><span>' . esc_html( makaazi_location_text( $p->ID ) ) . '</span>'
				/* translators: %d: views */
				. '<span>' . makaazi_icon( 'eye', 13 ) . ' ' . esc_html( sprintf( _n( '%d view', '%d views', (int) makaazi_meta( $p->ID, 'views', 0 ), 'makaazi' ), (int) makaazi_meta( $p->ID, 'views', 0 ) ) ) . '</span>'
				/* translators: %d: enquiries */
				. '<span>' . makaazi_icon( 'mail', 13 ) . ' ' . esc_html( sprintf( _n( '%d enquiry', '%d enquiries', (int) makaazi_meta( $p->ID, 'leads', 0 ), 'makaazi' ), (int) makaazi_meta( $p->ID, 'leads', 0 ) ) ) . '</span></p>'
				. ( 'rejected' === $st[0] && $reason ? '<p class="mk-mylist__reason"><strong>' . esc_html__( 'Reviewer note:', 'makaazi' ) . '</strong> ' . esc_html( $reason ) . '</p>' : '' )
				. ( 'pending' === $st[0] ? '<p class="mk-mylist__hint">' . esc_html__( 'Our team usually reviews new listings within one working day.', 'makaazi' ) . '</p>' : '' )
				. '</div><div class="mk-mylist__acts">';
			if ( 'publish' === $p->post_status ) {
				$h .= '<a class="mk-btn mk-btn--outline mk-btn--sm" href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html__( 'View', 'makaazi' ) . '</a>';
			}
			if ( $can ) {
				$h .= '<a class="mk-btn mk-btn--ghost mk-btn--sm" href="' . esc_url( $edit ) . '">' . esc_html( 'rejected' === $st[0] ? __( 'Fix and resubmit', 'makaazi' ) : __( 'Edit', 'makaazi' ) ) . '</a>';
				if ( 'live' === $st[0] ) {
					$h .= self::action_form( $p->ID, 'close', 'stay' === makaazi_status( $p->ID ) ? __( 'Mark unavailable', 'makaazi' ) : ( 'rent' === makaazi_status( $p->ID ) ? __( 'Mark let', 'makaazi' ) : __( 'Mark sold', 'makaazi' ) ) );
				} elseif ( 'closed' === $st[0] ) {
					$h .= self::action_form( $p->ID, 'relist', __( 'Relist', 'makaazi' ) );
				}
				$h .= self::action_form( $p->ID, 'delete', __( 'Delete', 'makaazi' ), 'mk-btn--danger' );
			}
			$h .= '</div></li>';
		}
		return $h . '</ul>';
	}

	/* ---------------------------------------------------------------------
	 * Form
	 * ------------------------------------------------------------------- */

	/**
	 * Current values for the form (posted values after an error, else the saved property).
	 *
	 * @param int $pid Property ID.
	 * @return array
	 */
	private static function values( $pid ) {
		if ( is_array( self::$posted ) ) {
			return self::$posted;
		}
		$v = array(
			'title'     => '',
			'status'    => 'sale',
			'type'      => '',
			'use'       => array( 'residential' ),
			'category'  => array(),
			'price'     => '',
			'period'    => 'total',
			'por'       => 0,
			'desc'      => '',
			'bedrooms'  => '',
			'bathrooms' => '',
			'parking'   => '',
			'area'      => '',
			'land_size' => '',
			'land_unit' => 'acres',
			'year'      => '',
			'features'  => array(),
			'photos'    => array(),
			'location'  => '',
			'label'     => '',
			'lat'       => '',
			'lng'       => '',
		);
		if ( ! $pid ) {
			return $v;
		}
		$terms = function ( $tax, $field = 'slug' ) use ( $pid ) {
			$t = wp_get_object_terms( $pid, $tax, array( 'fields' => 'slugs' === $field ? 'slugs' : 'ids' ) );
			return is_wp_error( $t ) ? array() : $t;
		};
		$sqm             = (float) makaazi_meta( $pid, 'area_sqm', 0 );
		$st              = $terms( 'property_status', 'slugs' );
		$ty              = $terms( 'property_type', 'slugs' );
		$loc             = makaazi_location_term( $pid );
		$v['title']      = get_the_title( $pid );
		$v['status']     = $st ? $st[0] : 'sale';
		$v['type']       = $ty ? $ty[0] : '';
		$v['use']        = $terms( 'property_use', 'slugs' );
		$v['category']   = $terms( 'property_category', 'slugs' );
		$v['features']   = $terms( 'property_feature', 'slugs' );
		$v['price']      = makaazi_meta( $pid, 'price' );
		$v['period']     = makaazi_meta( $pid, 'price_period', 'total' );
		$v['por']        = (int) makaazi_meta( $pid, 'price_on_request', 0 );
		$v['desc']       = get_post_field( 'post_content', $pid );
		$v['bedrooms']   = makaazi_meta( $pid, 'bedrooms' );
		$v['bathrooms']  = makaazi_meta( $pid, 'bathrooms' );
		$v['parking']    = makaazi_meta( $pid, 'parking' );
		$v['area']       = $sqm ? ( 'sqft' === makaazi_settings( 'unit' ) ? round( $sqm * 10.7639 ) : $sqm ) : '';
		$v['land_size']  = makaazi_meta( $pid, 'land_size' );
		$v['land_unit']  = makaazi_meta( $pid, 'land_unit', 'acres' );
		$v['year']       = makaazi_meta( $pid, 'year_built' );
		$v['photos']     = makaazi_gallery_ids( $pid );
		$v['location']   = $loc ? $loc->slug : '';
		$v['label']      = makaazi_meta( $pid, 'location_label' );
		$v['lat']        = makaazi_meta( $pid, 'lat' );
		$v['lng']        = makaazi_meta( $pid, 'lng' );
		return $v;
	}

	/**
	 * Term checkboxes or radios.
	 *
	 * @param string $tax      Taxonomy.
	 * @param string $name     Field name.
	 * @param array  $selected Selected slugs.
	 * @param string $type     checkbox|radio.
	 * @param string $cls      Wrapper class.
	 * @return string
	 */
	private static function term_inputs( $tax, $name, $selected, $type = 'checkbox', $cls = 'mk-chips' ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
			)
		);
		$h     = '<div class="' . esc_attr( $cls ) . '">';
		foreach ( (array) $terms as $t ) {
			$h .= '<label class="' . ( 'mk-chips' === $cls ? 'mk-chip' : 'mk-check' ) . '"><input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $t->slug ) . '"' . checked( in_array( $t->slug, (array) $selected, true ), true, false ) . '><span>' . esc_html( $t->name ) . '</span></label>';
		}
		return $h . '</div>';
	}

	/**
	 * The 5-step property form.
	 *
	 * @param int $pid Property ID to edit (0 for new).
	 * @return string
	 */
	public static function form( $pid ) {
		$u = wp_get_current_user();
		if ( ! Makaazi_Accounts::can_list( $u ) ) {
			return '<div class="mk-notice mk-notice--info">' . esc_html__( 'Adding listings is not available for this account.', 'makaazi' ) . '</div>';
		}
		if ( $pid && ! self::can_manage( $pid, $u ) ) {
			return '<div class="mk-notice mk-notice--error">' . esc_html__( 'You can only edit your own listings.', 'makaazi' ) . '</div>';
		}
		$v     = self::values( $pid );
		$e     = Makaazi_Accounts::$errors;
		$err   = function ( $k ) {
			return Makaazi_Accounts::err( $k );
		};
		$unit  = 'sqft' === makaazi_settings( 'unit' ) ? 'ft²' : 'm²';
		$max   = max( 3, (int) makaazi_settings( 'max_photos' ) );
		$live  = $pid && 'publish' === get_post_status( $pid );
		$types = get_terms(
			array(
				'taxonomy'   => 'property_type',
				'hide_empty' => false,
			)
		);
		Makaazi_Assets::enqueue( true );

		$step_of = array(
			'title'    => 1,
			'price'    => 1,
			'type'     => 1,
			'desc'     => 1,
			'area'     => 2,
			'photos'   => 3,
			'location' => 4,
			'label'    => 4,
		);
		$first = 1;
		if ( $e ) {
			$first = 5;
			foreach ( array_keys( $e ) as $k ) {
				if ( isset( $step_of[ $k ] ) ) {
					$first = min( $first, $step_of[ $k ] );
				}
			}
		}

		$h  = '<form class="mk-submit" method="post" data-mk-submit data-first="' . (int) $first . '" data-max-photos="' . (int) $max . '" novalidate>';
		$h .= wp_nonce_field( 'mk_save_property', '_mk_nonce', true, false ) . '<input type="hidden" name="mk_listing" value="save"><input type="hidden" name="id" value="' . (int) $pid . '">';
		$h .= '<div class="mk-submit__head"><h2 class="mk-sec__title">' . esc_html( $pid ? __( 'Edit listing', 'makaazi' ) : __( 'List your property', 'makaazi' ) ) . '</h2>';
		if ( $live && Makaazi_Roles::is_owner( $u ) && makaazi_settings( 'owner_edit_review' ) && ! makaazi_settings( 'owner_autopublish' ) ) {
			$h .= '<p class="mk-hint">' . esc_html__( 'This listing is live. Saving changes sends it back for a quick review.', 'makaazi' ) . '</p>';
		}
		$h    .= '<ol class="mk-steps" aria-label="' . esc_attr__( 'Steps', 'makaazi' ) . '">';
		$steps = array( __( 'Basics', 'makaazi' ), __( 'Details', 'makaazi' ), __( 'Photos', 'makaazi' ), __( 'Location', 'makaazi' ), __( 'Review', 'makaazi' ) );
		foreach ( $steps as $i => $s ) {
			$h .= '<li><button type="button" data-mk-go="' . ( $i + 1 ) . '"><i aria-hidden="true"></i><span>' . ( $i + 1 ) . '. ' . esc_html( $s ) . '</span></button></li>';
		}
		$h .= '</ol></div><div class="mk-submit__grid"><div class="mk-box mk-submit__box">';

		// Step 1.
		$h .= '<fieldset class="mk-step" data-step="1"><legend class="mk-step__title">' . esc_html__( 'The basics', 'makaazi' ) . '</legend>';
		$h .= '<label class="mk-field' . ( isset( $e['title'] ) ? ' has-error' : '' ) . '" for="mk-s-title"><span>' . esc_html__( 'Listing title', 'makaazi' ) . '</span><input id="mk-s-title" type="text" name="title" maxlength="80" required value="' . esc_attr( $v['title'] ) . '" placeholder="' . esc_attr__( 'e.g. Sunny 3-bedroom apartment in Lavington', 'makaazi' ) . '">' . $err( 'title' ) . '</label>';
		$h .= '<div class="mk-fg"><span class="mk-fg__label">' . esc_html__( 'I want to', 'makaazi' ) . '</span><div class="mk-seg mk-seg--inline"><label><input type="radio" name="status" value="sale"' . checked( $v['status'], 'sale', false ) . ' data-mk-status><span>' . esc_html__( 'Sell', 'makaazi' ) . '</span></label><label><input type="radio" name="status" value="rent"' . checked( $v['status'], 'rent', false ) . ' data-mk-status><span>' . esc_html__( 'Rent out', 'makaazi' ) . '</span></label>' . ( isset( makaazi_statuses()['stay'] ) || 'stay' === $v['status'] ? '<label><input type="radio" name="status" value="stay"' . checked( $v['status'], 'stay', false ) . ' data-mk-status><span>' . esc_html__( 'Host short stays', 'makaazi' ) . '</span></label>' : '' ) . '</div></div>';
		$opts = '<option value="">' . esc_html__( 'Choose a type', 'makaazi' ) . '</option>';
		foreach ( (array) $types as $t ) {
			$opts .= '<option value="' . esc_attr( $t->slug ) . '"' . selected( $v['type'], $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>';
		}
		$h .= '<div class="mk-g2"><label class="mk-field' . ( isset( $e['type'] ) ? ' has-error' : '' ) . '" for="mk-s-type"><span>' . esc_html__( 'Property type', 'makaazi' ) . '</span><select id="mk-s-type" name="type" required data-mk-type>' . $opts . '</select>' . $err( 'type' ) . '</label>';
		$h .= '<div class="mk-fg"><span class="mk-fg__label">' . esc_html__( 'Use', 'makaazi' ) . '</span>' . self::term_inputs( 'property_use', 'use[]', $v['use'] ) . '</div></div>';
		$h .= '<div class="mk-g2"><label class="mk-field' . ( isset( $e['price'] ) ? ' has-error' : '' ) . '" for="mk-s-price"><span data-mk-price-label data-sale="' . esc_attr( sprintf( /* translators: %s: currency */ __( 'Asking price (%s)', 'makaazi' ), makaazi_settings( 'currency' ) ) ) . '" data-rent="' . esc_attr( sprintf( /* translators: %s: currency */ __( 'Rent (%s)', 'makaazi' ), makaazi_settings( 'currency' ) ) ) . '" data-stay="' . esc_attr( sprintf( /* translators: %s: currency */ __( 'Nightly rate (%s)', 'makaazi' ), makaazi_settings( 'currency' ) ) ) . '">' . esc_html( sprintf( 'stay' === $v['status'] ? __( 'Nightly rate (%s)', 'makaazi' ) : ( 'rent' === $v['status'] ? __( 'Rent (%s)', 'makaazi' ) : __( 'Asking price (%s)', 'makaazi' ) ), makaazi_settings( 'currency' ) ) ) . '</span><input id="mk-s-price" type="number" min="0" step="1" name="price" inputmode="numeric" value="' . esc_attr( $v['price'] ) . '" placeholder="' . esc_attr( 'stay' === $v['status'] ? '8000' : ( 'rent' === $v['status'] ? '150000' : '25000000' ) ) . '">' . $err( 'price' ) . '</label>';
		$h .= '<label class="mk-field" for="mk-s-period"><span>' . esc_html__( 'Price is', 'makaazi' ) . '</span><select id="mk-s-period" name="period" data-mk-period>';
		foreach ( array(
			'total' => __( 'Total (sale)', 'makaazi' ),
			'month' => __( 'Per month', 'makaazi' ),
			'year'  => __( 'Per year', 'makaazi' ),
			'night' => __( 'Per night', 'makaazi' ),
		) as $k => $l ) {
			$h .= '<option value="' . esc_attr( $k ) . '"' . selected( $v['period'], $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		$h .= '</select></label></div><label class="mk-check"><input type="checkbox" name="por" value="1"' . checked( (int) $v['por'], 1, false ) . '> <span>' . esc_html__( 'Show "Price on request" instead', 'makaazi' ) . '</span></label>';
		$h .= '<label class="mk-field' . ( isset( $e['desc'] ) ? ' has-error' : '' ) . '" for="mk-s-desc"><span>' . esc_html__( 'Description', 'makaazi' ) . '</span><textarea id="mk-s-desc" name="desc" rows="6" placeholder="' . esc_attr__( 'Describe the layout, finishes, security, water and power, and what is nearby.', 'makaazi' ) . '">' . esc_textarea( $v['desc'] ) . '</textarea>' . $err( 'desc' ) . '</label>';
		$cats = get_terms( array( 'taxonomy' => 'property_category', 'hide_empty' => false ) );
		if ( $cats && ! is_wp_error( $cats ) ) {
			$h .= '<div class="mk-fg"><span class="mk-fg__label">' . esc_html__( 'Category (optional)', 'makaazi' ) . '</span>' . self::term_inputs( 'property_category', 'category[]', $v['category'] ) . '</div>';
		}
		$h .= '</fieldset>';

		// Step 2.
		$num = function ( $k, $label, $step = '1' ) use ( $v ) {
			return '<label class="mk-field" for="mk-s-' . $k . '" data-mk-room><span>' . esc_html( $label ) . '</span><input id="mk-s-' . $k . '" type="number" min="0" max="500" step="' . $step . '" inputmode="decimal" name="' . $k . '" value="' . esc_attr( $v[ $k ] ) . '"></label>';
		};
		$h .= '<fieldset class="mk-step" data-step="2"><legend class="mk-step__title">' . esc_html__( 'Details', 'makaazi' ) . '</legend><div class="mk-g3">' . $num( 'bedrooms', __( 'Bedrooms', 'makaazi' ) ) . $num( 'bathrooms', __( 'Bathrooms', 'makaazi' ), '0.5' ) . $num( 'parking', __( 'Parking spaces', 'makaazi' ) ) . '</div>';
		$h .= '<div class="mk-g3"><label class="mk-field' . ( isset( $e['area'] ) ? ' has-error' : '' ) . '" for="mk-s-area"><span>' . esc_html( sprintf( /* translators: %s: unit */ __( 'Floor area (%s)', 'makaazi' ), $unit ) ) . '</span><input id="mk-s-area" type="number" min="0" step="0.01" inputmode="decimal" name="area" value="' . esc_attr( $v['area'] ) . '">' . $err( 'area' ) . '</label>';
		$h .= '<label class="mk-field" for="mk-s-land"><span>' . esc_html__( 'Land size', 'makaazi' ) . '</span><span class="mk-inline"><input id="mk-s-land" type="number" min="0" step="0.01" inputmode="decimal" name="land_size" value="' . esc_attr( $v['land_size'] ) . '"><select name="land_unit" aria-label="' . esc_attr__( 'Land unit', 'makaazi' ) . '">';
		foreach ( array( 'acres' => __( 'acres', 'makaazi' ), 'hectares' => __( 'hectares', 'makaazi' ), 'sqm' => 'm²' ) as $k => $l ) {
			$h .= '<option value="' . esc_attr( $k ) . '"' . selected( $v['land_unit'], $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		$h .= '</select></span></label><label class="mk-field" for="mk-s-year"><span>' . esc_html__( 'Year built (optional)', 'makaazi' ) . '</span><input id="mk-s-year" type="number" min="1900" max="2100" name="year_built" value="' . esc_attr( $v['year'] ) . '"></label></div>';
		$h .= '<div class="mk-fg"><span class="mk-fg__label">' . esc_html__( 'Features', 'makaazi' ) . '</span>' . self::term_inputs( 'property_feature', 'features[]', $v['features'], 'checkbox', 'mk-checks' ) . '</div></fieldset>';

		// Step 3.
		$h .= '<fieldset class="mk-step" data-step="3"><legend class="mk-step__title">' . esc_html__( 'Photos', 'makaazi' ) . '</legend>';
		$h .= '<input type="hidden" name="photos" value="' . esc_attr( implode( ',', $v['photos'] ) ) . '" data-mk-photos>';
		$h .= '<div class="mk-drop" data-mk-drop><label class="mk-drop__in" for="mk-s-files">' . makaazi_icon( 'photo', 28 ) . '<strong>' . esc_html__( 'Drag photos here or tap to choose', 'makaazi' ) . '</strong><span>' . esc_html( sprintf( /* translators: %d: max photos */ __( 'Up to %d photos. JPG, PNG or WebP. Large photos are resized before upload.', 'makaazi' ), $max ) ) . '</span></label><input id="mk-s-files" type="file" accept="image/jpeg,image/png,image/webp" multiple data-mk-files class="mk-drop__input"></div>';
		$h .= ( isset( $e['photos'] ) ? '<span class="mk-ferr">' . esc_html( $e['photos'] ) . '</span>' : '' );
		$h .= '<ul class="mk-thumbs" data-mk-thumbs>';
		foreach ( $v['photos'] as $i => $aid ) {
			$h .= '<li data-id="' . (int) $aid . '" draggable="true"><img src="' . esc_url( wp_get_attachment_image_url( $aid, 'thumbnail' ) ) . '" alt=""><span class="mk-thumbs__badge">' . esc_html__( 'Cover', 'makaazi' ) . '</span><span class="mk-thumbs__acts"><button type="button" data-mk-cover aria-label="' . esc_attr__( 'Make cover photo', 'makaazi' ) . '">★</button><button type="button" data-mk-left aria-label="' . esc_attr__( 'Move earlier', 'makaazi' ) . '">◀</button><button type="button" data-mk-right aria-label="' . esc_attr__( 'Move later', 'makaazi' ) . '">▶</button><button type="button" data-mk-remove aria-label="' . esc_attr__( 'Remove photo', 'makaazi' ) . '">✕</button></span></li>';
		}
		$h .= '</ul><p class="mk-hint" data-mk-photo-count></p></fieldset>';

		// Step 4.
		$h .= '<fieldset class="mk-step" data-step="4"><legend class="mk-step__title">' . esc_html__( 'Location', 'makaazi' ) . '</legend><div class="mk-g2">';
		$h .= '<label class="mk-field' . ( isset( $e['location'] ) ? ' has-error' : '' ) . '" for="mk-s-loc"><span>' . esc_html__( 'Area', 'makaazi' ) . '</span><select id="mk-s-loc" name="location" required>' . Makaazi_Render::location_options( $v['location'], __( 'Choose an area', 'makaazi' ), false ) . '</select>' . $err( 'location' ) . '</label>';
		$h .= '<label class="mk-field' . ( isset( $e['label'] ) ? ' has-error' : '' ) . '" for="mk-s-label"><span>' . esc_html__( 'Road or landmark (shown publicly)', 'makaazi' ) . '</span><input id="mk-s-label" type="text" name="label" maxlength="90" required value="' . esc_attr( $v['label'] ) . '" placeholder="' . esc_attr__( 'e.g. Off Muthangari Drive, Lavington', 'makaazi' ) . '">' . $err( 'label' ) . '</label></div>';
		$h .= '<input type="hidden" name="lat" value="' . esc_attr( $v['lat'] ) . '" data-mk-lat><input type="hidden" name="lng" value="' . esc_attr( $v['lng'] ) . '" data-mk-lng>';
		$h .= '<div class="mk-inline"><input type="text" data-mk-geo placeholder="' . esc_attr__( 'Search a road or estate to move the map', 'makaazi' ) . '" aria-label="' . esc_attr__( 'Search the map', 'makaazi' ) . '"><button type="button" class="mk-btn mk-btn--ghost mk-btn--sm" data-mk-geo-go>' . esc_html__( 'Find', 'makaazi' ) . '</button></div>';
		$h .= '<div class="mk-map mk-map--pin" data-mk-pinmap data-center="' . esc_attr( makaazi_settings( 'map_center' ) ) . '" role="application" aria-label="' . esc_attr__( 'Map: tap to place the pin', 'makaazi' ) . '"></div>';
		$h .= '<p class="mk-hint">' . esc_html__( 'Tap the map to drop a pin on the exact spot (optional). Visitors only see an approximate area, never the exact address.', 'makaazi' ) . '</p></fieldset>';

		// Step 5.
		$h .= '<fieldset class="mk-step" data-step="5"><legend class="mk-step__title">' . esc_html__( 'Review and submit', 'makaazi' ) . '</legend><dl class="mk-details" data-mk-review></dl>';
		$review_note = Makaazi_Roles::is_owner( $u ) && ! makaazi_settings( 'owner_autopublish' ) ? __( 'When you submit, our team checks the listing, usually within one working day. We will email you when it is live.', 'makaazi' ) : '';
		if ( Makaazi_Roles::is_agent( $u ) && makaazi_settings( 'agent_review' ) ) {
			$review_note = __( 'When you submit, an admin reviews the listing before it goes live.', 'makaazi' );
		}
		$h .= $review_note ? '<p class="mk-hint">' . esc_html( $review_note ) . '</p>' : '';
		$h .= '</fieldset>';

		$h .= '<div class="mk-submit__nav"><button type="button" class="mk-btn mk-btn--ghost" data-mk-back>' . esc_html__( 'Back', 'makaazi' ) . '</button><span class="mk-sp"></span><button type="submit" name="intent" value="draft" class="mk-btn mk-btn--ghost" formnovalidate>' . esc_html__( 'Save draft', 'makaazi' ) . '</button><button type="button" class="mk-btn" data-mk-next>' . esc_html__( 'Next', 'makaazi' ) . '</button><button type="submit" name="intent" value="submit" class="mk-btn mk-btn--gold" data-mk-final>' . esc_html( $review_note ? __( 'Submit for review', 'makaazi' ) : __( 'Publish listing', 'makaazi' ) ) . '</button></div>';
		$h .= '</div><aside class="mk-box mk-submit__tips"><strong>' . esc_html__( 'Tips for a quick approval', 'makaazi' ) . '</strong><ul><li>' . esc_html__( 'Bright, level photos of every room, and at least one of the outside.', 'makaazi' ) . '</li><li>' . esc_html__( 'Mention water, backup power, security and parking.', 'makaazi' ) . '</li><li>' . esc_html__( 'Use a nearby road or landmark, not the house number.', 'makaazi' ) . '</li></ul><p>' . esc_html__( 'Your exact address is never shown publicly.', 'makaazi' ) . '</p></aside></div></form>';
		return $h;
	}

	/* ---------------------------------------------------------------------
	 * Saving and actions
	 * ------------------------------------------------------------------- */

	/**
	 * Handle the submission form and listing actions.
	 */
	public static function handle() {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['mk_listing'] ) || ! is_user_logged_in() ) { // phpcs:ignore
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['mk_listing'] ) );
		$pid    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$nonce  = isset( $_POST['_mk_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_mk_nonce'] ) ) : '';
		if ( 'save' === $action ) {
			if ( ! wp_verify_nonce( $nonce, 'mk_save_property' ) ) {
				Makaazi_Accounts::$notice = array( 'error', __( 'Your session expired. Please try again.', 'makaazi' ) );
				return;
			}
			self::save( $pid, wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised in save().
			return;
		}
		if ( ! wp_verify_nonce( $nonce, 'mk_listing_' . $action . '_' . $pid ) || ! self::can_manage( $pid ) || ! Makaazi_Accounts::can_list() ) {
			Makaazi_Accounts::$notice = array( 'error', __( 'That action is not allowed.', 'makaazi' ) );
			return;
		}
		$msg = '';
		switch ( $action ) {
			case 'close':
				update_post_meta( $pid, '_mk_availability', in_array( makaazi_status( $pid ), array( 'rent', 'stay' ), true ) ? 'let' : 'sold' );
				$msg = 'closed';
				break;
			case 'relist':
				update_post_meta( $pid, '_mk_availability', 'available' );
				$msg = 'relisted';
				break;
			case 'delete':
				wp_trash_post( $pid );
				$msg = 'deleted';
				break;
		}
		makaazi_bump_cache();
		wp_safe_redirect( makaazi_dashboard_url( array( 'mk_msg' => $msg ) ) );
		exit;
	}

	/**
	 * Validate and save a property from the front end.
	 *
	 * @param int   $pid Property ID (0 = new).
	 * @param array $p   Posted data (unslashed).
	 */
	private static function save( $pid, $p ) {
		$u = wp_get_current_user();
		if ( ! Makaazi_Accounts::can_list( $u ) || ( $pid && ! self::can_manage( $pid, $u ) ) ) {
			Makaazi_Accounts::$notice = array( 'error', __( 'You can only edit your own listings.', 'makaazi' ) );
			return;
		}
		$intent = isset( $p['intent'] ) && 'draft' === $p['intent'] ? 'draft' : 'submit';
		$list   = function ( $k ) use ( $p ) {
			return isset( $p[ $k ] ) ? array_values( array_filter( array_map( 'sanitize_title', (array) $p[ $k ] ) ) ) : array();
		};
		$v      = array(
			'title'     => isset( $p['title'] ) ? sanitize_text_field( $p['title'] ) : '',
			'status'    => isset( $p['status'] ) && in_array( $p['status'], array( 'rent', 'stay' ), true ) ? $p['status'] : 'sale',
			'type'      => isset( $p['type'] ) ? sanitize_title( $p['type'] ) : '',
			'use'       => $list( 'use' ),
			'category'  => $list( 'category' ),
			'price'     => isset( $p['price'] ) && '' !== $p['price'] ? absint( $p['price'] ) : '',
			'period'    => isset( $p['period'] ) && in_array( $p['period'], array( 'total', 'month', 'year', 'night' ), true ) ? $p['period'] : 'total',
			'por'       => empty( $p['por'] ) ? 0 : 1,
			'desc'      => isset( $p['desc'] ) ? sanitize_textarea_field( $p['desc'] ) : '',
			'bedrooms'  => isset( $p['bedrooms'] ) && '' !== $p['bedrooms'] ? absint( $p['bedrooms'] ) : '',
			'bathrooms' => isset( $p['bathrooms'] ) && '' !== $p['bathrooms'] ? (float) $p['bathrooms'] : '',
			'parking'   => isset( $p['parking'] ) && '' !== $p['parking'] ? absint( $p['parking'] ) : '',
			'area'      => isset( $p['area'] ) && '' !== $p['area'] ? (float) $p['area'] : '',
			'land_size' => isset( $p['land_size'] ) && '' !== $p['land_size'] ? (float) $p['land_size'] : '',
			'land_unit' => isset( $p['land_unit'] ) && in_array( $p['land_unit'], array( 'acres', 'hectares', 'sqm' ), true ) ? $p['land_unit'] : 'acres',
			'year'      => isset( $p['year_built'] ) && '' !== $p['year_built'] ? absint( $p['year_built'] ) : '',
			'features'  => $list( 'features' ),
			'photos'    => isset( $p['photos'] ) ? array_values( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $p['photos'] ) ) ) ) ) : array(),
			'location'  => isset( $p['location'] ) ? sanitize_title( $p['location'] ) : '',
			'label'     => isset( $p['label'] ) ? sanitize_text_field( $p['label'] ) : '',
			'lat'       => isset( $p['lat'] ) && is_numeric( $p['lat'] ) && abs( (float) $p['lat'] ) <= 90 ? (float) $p['lat'] : '',
			'lng'       => isset( $p['lng'] ) && is_numeric( $p['lng'] ) && abs( (float) $p['lng'] ) <= 180 ? (float) $p['lng'] : '',
		);
		if ( 'rent' === $v['status'] && 'total' === $v['period'] ) {
			$v['period'] = 'month';
		}
		if ( 'stay' === $v['status'] && 'night' !== $v['period'] && 'month' !== $v['period'] ) {
			$v['period'] = 'night';
		}
		// Only the user's own uploads (or photos already on this listing) can be used.
		$existing    = $pid ? makaazi_gallery_ids( $pid ) : array();
		$v['photos'] = array_values(
			array_filter(
				$v['photos'],
				function ( $aid ) use ( $u, $existing ) {
					return in_array( $aid, $existing, true ) || ( 'attachment' === get_post_type( $aid ) && (int) get_post_field( 'post_author', $aid ) === $u->ID );
				}
			)
		);
		$v['photos'] = array_slice( $v['photos'], 0, max( 3, (int) makaazi_settings( 'max_photos' ) ) );

		$e = array();
		if ( '' === $v['title'] ) {
			$e['title'] = __( 'Add a title so people can find your listing.', 'makaazi' );
		}
		if ( 'submit' === $intent ) {
			if ( ! $v['type'] || ! term_exists( $v['type'], 'property_type' ) ) {
				$e['type'] = __( 'Choose the property type.', 'makaazi' );
			}
			if ( ! $v['por'] && ! $v['price'] ) {
				$e['price'] = __( 'Enter a price in Kenya shillings, for example 150000, or tick "Price on request".', 'makaazi' );
			}
			if ( strlen( $v['desc'] ) < 40 ) {
				$e['desc'] = __( 'Write at least a couple of sentences about the property.', 'makaazi' );
			}
			if ( ! $v['area'] && ! $v['land_size'] ) {
				$e['area'] = __( 'Enter the floor area, or the land size for a plot.', 'makaazi' );
			}
			if ( count( $v['photos'] ) < 3 ) {
				$e['photos'] = __( 'Add at least 3 photos. Listings with more photos get more enquiries.', 'makaazi' );
			}
			if ( ! $v['location'] || ! term_exists( $v['location'], 'property_location' ) ) {
				$e['location'] = __( 'Choose the area the property is in.', 'makaazi' );
			}
			if ( '' === $v['label'] ) {
				$e['label'] = __( 'Add a nearby road or landmark.', 'makaazi' );
			}
		}
		if ( $e ) {
			Makaazi_Accounts::$errors = $e;
			self::$posted             = $v;
			return;
		}

		$was_live = $pid && 'publish' === get_post_status( $pid );
		if ( 'draft' === $intent ) {
			$status = $was_live ? 'publish' : 'draft';
		} elseif ( Makaazi_Roles::is_owner( $u ) ) {
			$status = makaazi_settings( 'owner_autopublish' ) ? 'publish' : ( ( $was_live && ! makaazi_settings( 'owner_edit_review' ) ) ? 'publish' : 'pending' );
		} else {
			$status = user_can( $u, 'publish_properties' ) ? 'publish' : 'pending';
		}
		if ( 'draft' === $intent && $was_live && Makaazi_Roles::is_owner( $u ) && makaazi_settings( 'owner_edit_review' ) && ! makaazi_settings( 'owner_autopublish' ) ) {
			$status = 'pending';
		}

		$post = array(
			'post_type'    => 'property',
			'post_title'   => $v['title'],
			'post_content' => $v['desc'],
			'post_excerpt' => wp_trim_words( $v['desc'], 24 ),
			'post_status'  => $status,
		);
		if ( $pid ) {
			$post['ID'] = $pid;
		} else {
			$post['post_author'] = $u->ID;
		}
		$pid = $pid ? wp_update_post( $post, true ) : wp_insert_post( $post, true );
		if ( is_wp_error( $pid ) ) {
			Makaazi_Accounts::$notice = array( 'error', __( 'We could not save the listing. Please try again.', 'makaazi' ) );
			self::$posted             = $v;
			return;
		}

		wp_set_object_terms( $pid, $v['status'], 'property_status' );
		wp_set_object_terms( $pid, $v['type'] ? array( $v['type'] ) : array(), 'property_type' );
		wp_set_object_terms( $pid, $v['use'], 'property_use' );
		wp_set_object_terms( $pid, $v['category'], 'property_category' );
		wp_set_object_terms( $pid, $v['features'], 'property_feature' );
		$loc = $v['location'] ? get_term_by( 'slug', $v['location'], 'property_location' ) : null;
		wp_set_object_terms( $pid, $loc ? array( (int) $loc->term_id ) : array(), 'property_location' );

		$area = (float) $v['area'];
		if ( 'sqft' === makaazi_settings( 'unit' ) ) {
			$area = $area / 10.7639;
		}
		$meta = array(
			'_mk_price'            => (float) $v['price'],
			'_mk_price_period'     => $v['period'],
			'_mk_price_on_request' => $v['por'],
			'_mk_area_sqm'         => round( $area, 2 ),
			'_mk_land_unit'        => $v['land_unit'],
			'_mk_gallery'          => $v['photos'],
			'_mk_location_label'   => $v['label'],
		);
		foreach ( $meta as $k => $val ) {
			update_post_meta( $pid, $k, $val );
		}
		foreach ( array(
			'_mk_bedrooms'   => 'bedrooms',
			'_mk_bathrooms'  => 'bathrooms',
			'_mk_parking'    => 'parking',
			'_mk_land_size'  => 'land_size',
			'_mk_year_built' => 'year',
			'_mk_lat'        => 'lat',
			'_mk_lng'        => 'lng',
		) as $k => $f ) {
			if ( '' === $v[ $f ] ) {
				delete_post_meta( $pid, $k );
			} else {
				update_post_meta( $pid, $k, $v[ $f ] );
			}
		}
		if ( '' === (string) get_post_meta( $pid, '_mk_availability', true ) ) {
			update_post_meta( $pid, '_mk_availability', 'available' );
		}
		if ( $v['photos'] ) {
			set_post_thumbnail( $pid, $v['photos'][0] );
			foreach ( $v['photos'] as $aid ) {
				if ( ! wp_get_post_parent_id( $aid ) ) {
					wp_update_post(
						array(
							'ID'          => $aid,
							'post_parent' => $pid,
						)
					);
				}
			}
		}
		$aid = Makaazi_Agents::for_user( $u->ID );
		if ( $aid && ! get_post_meta( $pid, '_mk_agent_id', true ) ) {
			update_post_meta( $pid, '_mk_agent_id', $aid );
		}
		if ( 'submit' === $intent ) {
			delete_post_meta( $pid, '_mk_rejected' );
		}
		Makaazi_Meta::after_save( $pid, get_post( $pid ) );

		if ( 'pending' === $status && 'submit' === $intent ) {
			/* translators: %s: title */
			wp_mail( makaazi_settings( 'enquiry_email' ) && is_email( makaazi_settings( 'enquiry_email' ) ) ? makaazi_settings( 'enquiry_email' ) : get_option( 'admin_email' ), sprintf( __( 'Listing waiting for review: %s', 'makaazi' ), $v['title'] ), sprintf( "%s\n%s\n\n%s", $u->display_name, $u->user_email, admin_url( 'edit.php?post_status=pending&post_type=property' ) ) );
		}
		/**
		 * Fires after an agent or owner saves a listing from the front-end dashboard.
		 *
		 * @param int    $pid    Property ID.
		 * @param string $status New post status (draft, pending, publish).
		 * @param string $intent draft or submit.
		 * @param int    $user   User ID.
		 */
		do_action( 'makaazi_property_submitted', $pid, $status, $intent, $u->ID );
		$msg = 'draft' === $intent ? ( 'publish' === $status ? 'updated' : 'draft' ) : ( 'pending' === $status ? 'submitted' : ( $was_live ? 'updated' : 'published' ) );
		wp_safe_redirect( makaazi_dashboard_url( array( 'mk_msg' => $msg ) ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Uploads
	 * ------------------------------------------------------------------- */

	/**
	 * REST routes.
	 */
	public static function routes() {
		register_rest_route(
			Makaazi_Rest::NS,
			'/upload',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'upload' ),
				'permission_callback' => function () {
					return Makaazi_Accounts::can_list();
				},
			)
		);
	}

	/**
	 * Upload one photo.
	 *
	 * @param WP_REST_Request $r Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function upload( $r ) {
		$files = $r->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new WP_Error( 'makaazi_upload', __( 'No photo received.', 'makaazi' ), array( 'status' => 400 ) );
		}
		$f    = $files['file'];
		$type = wp_check_filetype_and_ext( $f['tmp_name'], $f['name'] );
		if ( ! in_array( $type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
			return new WP_Error( 'makaazi_upload', __( 'Use JPG, PNG or WebP photos.', 'makaazi' ), array( 'status' => 400 ) );
		}
		if ( (int) $f['size'] > 8 * MB_IN_BYTES ) {
			return new WP_Error( 'makaazi_upload', __( 'That photo is larger than 8 MB.', 'makaazi' ), array( 'status' => 400 ) );
		}
		$uid  = get_current_user_id();
		$key  = 'mk_up_' . $uid;
		$hits = (int) get_transient( $key );
		if ( $hits > 120 ) {
			return new WP_Error( 'makaazi_upload', __( 'Upload limit reached for now. Try again in an hour.', 'makaazi' ), array( 'status' => 429 ) );
		}
		set_transient( $key, $hits + 1, HOUR_IN_SECONDS );
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$_FILES['file'] = $f; // media_handle_upload reads $_FILES.
		$id             = media_handle_upload( 'file', 0, array( 'post_author' => $uid ) );
		if ( is_wp_error( $id ) ) {
			return new WP_Error( 'makaazi_upload', $id->get_error_message(), array( 'status' => 400 ) );
		}
		return rest_ensure_response(
			array(
				'id'    => $id,
				'thumb' => wp_get_attachment_image_url( $id, 'thumbnail' ),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Moderation
	 * ------------------------------------------------------------------- */

	/**
	 * Approve / Reject row actions on pending properties.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( 'property' !== $post->post_type || 'pending' !== $post->post_status || ! current_user_can( 'publish_properties' ) || ! current_user_can( 'edit_others_properties' ) ) {
			return $actions;
		}
		$url = function ( $do ) use ( $post ) {
			return wp_nonce_url( admin_url( 'admin-post.php?action=makaazi_moderate&do=' . $do . '&id=' . $post->ID ), 'mk_moderate_' . $post->ID );
		};
		$new = array(
			'mk_approve' => '<a href="' . esc_url( $url( 'approve' ) ) . '" style="color:#00a32a;font-weight:600">' . esc_html__( 'Approve & publish', 'makaazi' ) . '</a>',
			'mk_reject'  => '<a href="' . esc_url( $url( 'reject' ) ) . '" class="mk-reject" style="color:#b32d2e" data-prompt="' . esc_attr__( 'What should the owner change? This is emailed to them.', 'makaazi' ) . '">' . esc_html__( 'Reject…', 'makaazi' ) . '</a>',
		);
		return array_merge( $new, $actions );
	}

	/**
	 * Approve or reject.
	 */
	public static function moderate() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'mk_moderate_' . $id );
		if ( ! current_user_can( 'publish_properties' ) || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'You are not allowed to moderate listings.', 'makaazi' ) );
		}
		$do = isset( $_GET['do'] ) ? sanitize_key( $_GET['do'] ) : '';
		if ( 'approve' === $do ) {
			delete_post_meta( $id, '_mk_rejected' );
			// wp_update_post (not wp_publish_post) so the listing gets its URL slug.
			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => 'publish',
					'post_name'   => wp_unique_post_slug( sanitize_title( get_the_title( $id ) ), $id, 'publish', 'property', 0 ),
				)
			);
			makaazi_bump_cache();
			/**
			 * Fires when a moderator approves a submitted listing.
			 *
			 * @param int $id Property ID.
			 */
			do_action( 'makaazi_listing_approved', $id );
			$msg = 'approved';
		} else {
			$reason = isset( $_GET['reason'] ) ? sanitize_textarea_field( wp_unslash( $_GET['reason'] ) ) : '';
			$reason = $reason ? $reason : __( 'Please check the photos and details, then resubmit.', 'makaazi' );
			update_post_meta( $id, '_mk_rejected', $reason );
			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => 'draft',
				)
			);
			$author = get_userdata( (int) get_post_field( 'post_author', $id ) );
			if ( $author ) {
				/* translators: %s: title */
				wp_mail( $author->user_email, sprintf( __( 'Changes needed: %s', 'makaazi' ), get_the_title( $id ) ), sprintf( /* translators: 1: reason, 2: link */ __( "Thanks for your listing. Before it can go live, please make this change:\n\n%1\$s\n\nEdit and resubmit here:\n%2\$s", 'makaazi' ), $reason, makaazi_dashboard_url( array( 'tab' => 'submit', 'id' => $id ) ) ) );
			}
			/**
			 * Fires when a moderator sends a listing back for changes.
			 *
			 * @param int    $id     Property ID.
			 * @param string $reason Reason shown to the owner.
			 */
			do_action( 'makaazi_listing_rejected', $id, $reason );
			$msg = 'rejected';
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=property&post_status=pending&mk_mod=' . $msg ) );
		exit;
	}

	/**
	 * Email the owner when their listing goes live.
	 *
	 * @param string  $new  New status.
	 * @param string  $old  Old status.
	 * @param WP_Post $post Post.
	 */
	public static function on_status( $new, $old, $post ) {
		if ( 'property' !== $post->post_type || 'publish' !== $new || 'publish' === $old ) {
			return;
		}
		$author = get_userdata( (int) $post->post_author );
		if ( $author && Makaazi_Roles::is_owner( $author ) && get_current_user_id() !== $author->ID ) {
			/* translators: %s: title */
			wp_mail( $author->user_email, sprintf( __( 'Your listing is live: %s', 'makaazi' ), $post->post_title ), sprintf( /* translators: 1: link, 2: dashboard */ __( "Good news, your listing is now live:\n%1\$s\n\nManage it here:\n%2\$s", 'makaazi' ), get_permalink( $post ), makaazi_dashboard_url() ) );
		}
	}

	/**
	 * Pending count bubble on the Properties menu.
	 */
	public static function pending_bubble() {
		global $menu;
		if ( ! current_user_can( 'edit_others_properties' ) ) {
			return;
		}
		$n = (int) wp_count_posts( 'property' )->pending;
		if ( ! $n ) {
			return;
		}
		foreach ( (array) $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=property' === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . $n . '"><span class="pending-count">' . $n . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			}
		}
	}

	/**
	 * Notices after moderation.
	 */
	public static function admin_notice() {
		if ( empty( $_GET['mk_mod'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$m = sanitize_key( $_GET['mk_mod'] ); // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( 'approved' === $m ? __( 'Listing published. The owner has been emailed.', 'makaazi' ) : __( 'Listing sent back to the owner with your note.', 'makaazi' ) ) . '</p></div>';
	}

	/**
	 * "Needs changes" label in the admin list.
	 *
	 * @param array   $states States.
	 * @param WP_Post $post   Post.
	 * @return array
	 */
	public static function post_states( $states, $post ) {
		if ( 'property' === $post->post_type && 'draft' === $post->post_status && get_post_meta( $post->ID, '_mk_rejected', true ) ) {
			$states['mk_rejected'] = __( 'Needs changes', 'makaazi' );
		}
		return $states;
	}

	/**
	 * Count a view (skips the author, staff and obvious bots).
	 */
	public static function count_view() {
		if ( ! is_singular( 'property' ) ) {
			return;
		}
		$pid = get_queried_object_id();
		if ( is_user_logged_in() && ( current_user_can( 'edit_others_properties' ) || (int) get_post_field( 'post_author', $pid ) === get_current_user_id() ) ) {
			return;
		}
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp/', $ua ) ) {
			return;
		}
		update_post_meta( $pid, '_mk_views', (int) get_post_meta( $pid, '_mk_views', true ) + 1 );
	}
}
