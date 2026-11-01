<?php
/**
 * Property Details meta box (Pricing, Specs, Gallery, Location, Attributes).
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tabbed meta box.
 */
class Makaazi_Meta_Box {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'add_meta_boxes_property', array( __CLASS__, 'add' ) );
		add_action( 'save_post_property', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'edit_form_after_title', array( __CLASS__, 'after_title' ) );
	}

	/**
	 * Register boxes.
	 */
	public static function add() {
		add_meta_box( 'mk_details', __( 'Property Details', 'makaazi' ), array( __CLASS__, 'render' ), 'property', 'normal', 'high' );
		add_meta_box( 'mk_listing', __( 'Listing', 'makaazi' ), array( __CLASS__, 'render_side' ), 'property', 'side', 'high' );
		remove_meta_box( 'postcustom', 'property', 'normal' );
	}

	/**
	 * Hint under the title.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function after_title( $post ) {
		if ( 'property' !== $post->post_type ) {
			return;
		}
		echo '<p class="mk-after-title">' . esc_html__( 'Write the description below, then fill in the Property Details. Status, type and location are in the right-hand column.', 'makaazi' ) . '</p>';
	}

	/**
	 * Side box: featured, availability, reference.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_side( $post ) {
		$id    = $post->ID;
		$avail = makaazi_meta( $id, 'availability', 'available' );
		echo '<p><label><input type="checkbox" name="mk[featured]" value="1"' . checked( (bool) makaazi_meta( $id, 'featured' ), true, false ) . '> ' . esc_html__( 'Featured property', 'makaazi' ) . '</label></p>';
		echo '<p><label for="mk-availability"><strong>' . esc_html__( 'Availability', 'makaazi' ) . '</strong></label><br><select id="mk-availability" name="mk[availability]" class="widefat">';
		foreach ( array(
			'available'   => __( 'Available', 'makaazi' ),
			'under_offer' => __( 'Under offer', 'makaazi' ),
			'sold'        => __( 'Sold', 'makaazi' ),
			'let'         => __( 'Let', 'makaazi' ),
		) as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $avail, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select><span class="description">' . esc_html__( 'Sold and let properties stay online but are hidden from search and grids.', 'makaazi' ) . '</span></p>';
		echo '<p><label for="mk-ref"><strong>' . esc_html__( 'Reference', 'makaazi' ) . '</strong></label><br><input id="mk-ref" type="text" class="widefat" name="mk[ref]" value="' . esc_attr( makaazi_meta( $id, 'ref' ) ) . '" placeholder="' . esc_attr__( 'Created on save', 'makaazi' ) . '"></p>';
	}

	/**
	 * Main box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render( $post ) {
		$id   = $post->ID;
		$unit = makaazi_settings( 'unit' );
		$sqm  = (float) makaazi_meta( $id, 'area_sqm', 0 );
		$area = $sqm ? ( 'sqft' === $unit ? round( $sqm * 10.7639 ) : $sqm ) : '';
		wp_nonce_field( 'makaazi_save', 'makaazi_nonce' );
		$tabs = array(
			'pricing'  => __( 'Pricing', 'makaazi' ),
			'specs'    => __( 'Specs', 'makaazi' ),
			'gallery'  => __( 'Gallery', 'makaazi' ),
			'location' => __( 'Location', 'makaazi' ),
			'attrs'    => __( 'Attributes', 'makaazi' ),
		);
		?>
		<div class="mk-tabs" data-mk-tabs>
			<nav class="mk-tabs__nav" role="tablist">
				<?php foreach ( $tabs as $k => $l ) : ?>
					<button type="button" role="tab" class="mk-tabs__tab<?php echo 'pricing' === $k ? ' is-on' : ''; ?>" data-tab="<?php echo esc_attr( $k ); ?>" aria-selected="<?php echo 'pricing' === $k ? 'true' : 'false'; ?>"><?php echo esc_html( $l ); ?></button>
				<?php endforeach; ?>
			</nav>
			<div class="mk-tabs__panes">

			<section class="mk-pane is-on" data-pane="pricing" role="tabpanel">
				<div class="mk-g3">
					<label><?php esc_html_e( 'Price', 'makaazi' ); ?> (<?php echo esc_html( makaazi_settings( 'currency' ) ); ?>)<input type="number" min="0" step="1" name="mk[price]" value="<?php echo esc_attr( makaazi_meta( $id, 'price' ) ); ?>" data-mk-price></label>
					<label><?php esc_html_e( 'Price period', 'makaazi' ); ?>
						<select name="mk[price_period]" data-mk-period>
							<?php
							foreach ( array(
								'total' => __( 'Total (sale)', 'makaazi' ),
								'month' => __( 'Per month', 'makaazi' ),
								'year'  => __( 'Per year', 'makaazi' ),
								'night' => __( 'Per night', 'makaazi' ),
							) as $k => $l ) {
								echo '<option value="' . esc_attr( $k ) . '"' . selected( makaazi_meta( $id, 'price_period', 'total' ), $k, false ) . '>' . esc_html( $l ) . '</option>';
							}
							?>
						</select>
					</label>
					<div class="mk-preview-price"><span><?php esc_html_e( 'Shows as', 'makaazi' ); ?></span><strong data-mk-price-preview><?php echo wp_kses_post( makaazi_price( $id, false ) ); ?></strong></div>
				</div>
				<label class="mk-check"><input type="checkbox" name="mk[price_on_request]" value="1" <?php checked( (bool) makaazi_meta( $id, 'price_on_request' ) ); ?>> <?php esc_html_e( 'Show "Price on request" instead of the price', 'makaazi' ); ?></label>
				<p class="description"><?php esc_html_e( 'Rentals usually use "Per month". The period is added after the price, for example KES 180,000 /month.', 'makaazi' ); ?></p>
			</section>

			<section class="mk-pane" data-pane="specs" role="tabpanel" hidden>
				<div class="mk-g3">
					<label><?php esc_html_e( 'Bedrooms', 'makaazi' ); ?><input type="number" min="0" max="50" name="mk[bedrooms]" value="<?php echo esc_attr( makaazi_meta( $id, 'bedrooms' ) ); ?>"></label>
					<label><?php esc_html_e( 'Bathrooms', 'makaazi' ); ?><input type="number" min="0" max="50" step="0.5" name="mk[bathrooms]" value="<?php echo esc_attr( makaazi_meta( $id, 'bathrooms' ) ); ?>"></label>
					<label><?php esc_html_e( 'Parking spaces', 'makaazi' ); ?><input type="number" min="0" max="500" name="mk[parking]" value="<?php echo esc_attr( makaazi_meta( $id, 'parking' ) ); ?>"></label>
					<label><?php echo esc_html( sprintf( /* translators: %s: unit */ __( 'Floor area (%s)', 'makaazi' ), 'sqft' === $unit ? 'ft²' : 'm²' ) ); ?><input type="number" min="0" step="0.01" name="mk[area]" value="<?php echo esc_attr( $area ); ?>"></label>
					<label><?php esc_html_e( 'Land size', 'makaazi' ); ?>
						<span class="mk-inline"><input type="number" min="0" step="0.01" name="mk[land_size]" value="<?php echo esc_attr( makaazi_meta( $id, 'land_size' ) ); ?>">
						<select name="mk[land_unit]" aria-label="<?php esc_attr_e( 'Land unit', 'makaazi' ); ?>">
							<?php
							foreach ( array(
								'acres'    => __( 'acres', 'makaazi' ),
								'hectares' => __( 'hectares', 'makaazi' ),
								'sqm'      => 'm²',
							) as $k => $l ) {
								echo '<option value="' . esc_attr( $k ) . '"' . selected( makaazi_meta( $id, 'land_unit', 'acres' ), $k, false ) . '>' . esc_html( $l ) . '</option>';
							}
							?>
						</select></span>
					</label>
					<label><?php esc_html_e( 'Year built or completion', 'makaazi' ); ?><input type="number" min="1900" max="2100" name="mk[year_built]" value="<?php echo esc_attr( makaazi_meta( $id, 'year_built' ) ); ?>"></label>
				</div>
				<p class="description"><?php esc_html_e( 'Leave a field empty to hide it on the website. Land listings usually only need the land size. Features are chosen in the Features box on the right.', 'makaazi' ); ?></p>
			</section>

			<section class="mk-pane" data-pane="gallery" role="tabpanel" hidden>
				<?php $gallery = makaazi_gallery_ids( $id ); ?>
				<input type="hidden" name="mk[gallery]" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>" data-mk-gallery-input>
				<ul class="mk-gal" data-mk-gallery>
					<?php foreach ( $gallery as $i => $aid ) : ?>
						<li data-id="<?php echo (int) $aid; ?>"><?php echo wp_get_attachment_image( $aid, 'thumbnail' ); ?><span class="mk-gal__n"><?php echo 0 === $i ? esc_html__( 'Cover', 'makaazi' ) : (int) ( $i + 1 ); ?></span><button type="button" class="mk-gal__x" aria-label="<?php esc_attr_e( 'Remove', 'makaazi' ); ?>">&times;</button></li>
					<?php endforeach; ?>
				</ul>
				<p><button type="button" class="button button-primary" data-mk-gallery-add><?php esc_html_e( 'Add photos', 'makaazi' ); ?></button>
				<span class="description"><?php esc_html_e( 'Drag to reorder. The first photo is the cover on cards and the large photo on the property page.', 'makaazi' ); ?></span></p>
			</section>

			<section class="mk-pane" data-pane="location" role="tabpanel" hidden>
				<div class="mk-g2">
					<label><?php esc_html_e( 'Public location label', 'makaazi' ); ?><input type="text" name="mk[location_label]" value="<?php echo esc_attr( makaazi_meta( $id, 'location_label' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Off Riverside Drive, Westlands', 'makaazi' ); ?>"></label>
					<label><?php esc_html_e( 'Find a place', 'makaazi' ); ?><span class="mk-inline"><input type="text" data-mk-geo placeholder="<?php esc_attr_e( 'Road, estate or landmark', 'makaazi' ); ?>"><button type="button" class="button" data-mk-geo-go><?php esc_html_e( 'Find', 'makaazi' ); ?></button></span></label>
				</div>
				<div class="mk-adminmap" data-mk-pin data-lat="<?php echo esc_attr( makaazi_meta( $id, 'lat' ) ); ?>" data-lng="<?php echo esc_attr( makaazi_meta( $id, 'lng' ) ); ?>"></div>
				<div class="mk-g3">
					<label><?php esc_html_e( 'Latitude', 'makaazi' ); ?><input type="text" name="mk[lat]" value="<?php echo esc_attr( makaazi_meta( $id, 'lat' ) ); ?>" data-mk-lat inputmode="decimal"></label>
					<label><?php esc_html_e( 'Longitude', 'makaazi' ); ?><input type="text" name="mk[lng]" value="<?php echo esc_attr( makaazi_meta( $id, 'lng' ) ); ?>" data-mk-lng inputmode="decimal"></label>
					<label><?php esc_html_e( 'Public privacy radius', 'makaazi' ); ?>
						<select name="mk[map_radius]">
							<?php
							$r = (int) makaazi_meta( $id, 'map_radius', makaazi_settings( 'privacy_radius' ) );
							foreach ( array( 250, 500, 1000, 2000 ) as $v ) {
								echo '<option value="' . (int) $v . '"' . selected( $r, $v, false ) . '>' . (int) $v . ' m</option>';
							}
							?>
						</select>
					</label>
				</div>
				<label class="mk-check"><input type="checkbox" name="mk[exact_pin]" value="1" <?php checked( (bool) makaazi_meta( $id, 'exact_pin' ) ); ?>> <?php esc_html_e( 'Show the exact pin publicly (for offices, shops and developments)', 'makaazi' ); ?></label>
				<p class="mk-note"><?php esc_html_e( 'Click the map or drag the pin to the exact spot. Visitors only see a shaded area around an offset centre, never these coordinates, unless you tick the box above.', 'makaazi' ); ?></p>
			</section>

			<section class="mk-pane" data-pane="attrs" role="tabpanel" hidden>
				<p class="description"><?php esc_html_e( 'Extra rows for the Details table, for example "Service charge" and "KES 8,000 /month".', 'makaazi' ); ?></p>
				<div class="mk-attrs" data-mk-attrs>
					<?php
					$attrs = (array) makaazi_meta( $id, 'attributes', array() );
					$attrs = $attrs ? $attrs : array( array( '', '' ) );
					foreach ( $attrs as $a ) :
						?>
						<div class="mk-attr"><input type="text" name="mk[attr_k][]" value="<?php echo esc_attr( isset( $a[0] ) ? $a[0] : '' ); ?>" placeholder="<?php esc_attr_e( 'Label', 'makaazi' ); ?>" aria-label="<?php esc_attr_e( 'Label', 'makaazi' ); ?>"><input type="text" name="mk[attr_v][]" value="<?php echo esc_attr( isset( $a[1] ) ? $a[1] : '' ); ?>" placeholder="<?php esc_attr_e( 'Value', 'makaazi' ); ?>" aria-label="<?php esc_attr_e( 'Value', 'makaazi' ); ?>"><button type="button" class="button-link mk-attr__x"><?php esc_html_e( 'Remove', 'makaazi' ); ?></button></div>
					<?php endforeach; ?>
				</div>
				<p><button type="button" class="button" data-mk-attr-add><?php esc_html_e( 'Add row', 'makaazi' ); ?></button></p>
				<label class="mk-wide"><?php esc_html_e( 'Video or virtual tour URL', 'makaazi' ); ?><input type="url" name="mk[video_url]" value="<?php echo esc_attr( makaazi_meta( $id, 'video_url' ) ); ?>" placeholder="https://"></label>
			</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['makaazi_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['makaazi_nonce'] ) ), 'makaazi_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['mk'] ) && is_array( $_POST['mk'] ) ? wp_unslash( $_POST['mk'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised per field below.

		$num = function ( $k, $float = false ) use ( $in, $post_id ) {
			$v = isset( $in[ $k ] ) ? trim( (string) $in[ $k ] ) : '';
			if ( '' === $v ) {
				delete_post_meta( $post_id, '_mk_' . $k );
				return;
			}
			update_post_meta( $post_id, '_mk_' . $k, $float ? (float) $v : (int) $v );
		};
		$num( 'price', true );
		$num( 'bedrooms' );
		$num( 'bathrooms', true );
		$num( 'parking' );
		$num( 'land_size', true );
		$num( 'year_built' );
		$num( 'map_radius' );

		$area = isset( $in['area'] ) ? (float) $in['area'] : 0;
		if ( 'sqft' === makaazi_settings( 'unit' ) ) {
			$area = $area / 10.7639;
		}
		update_post_meta( $post_id, '_mk_area_sqm', round( $area, 2 ) );

		$enum = function ( $k, $allowed, $default ) use ( $in, $post_id ) {
			$v = isset( $in[ $k ] ) && in_array( $in[ $k ], $allowed, true ) ? $in[ $k ] : $default;
			update_post_meta( $post_id, '_mk_' . $k, $v );
		};
		$enum( 'price_period', array( 'total', 'month', 'year', 'night' ), 'total' );
		$enum( 'land_unit', array( 'acres', 'hectares', 'sqm' ), 'acres' );
		$enum( 'availability', array( 'available', 'under_offer', 'sold', 'let' ), 'available' );

		foreach ( array( 'featured', 'price_on_request', 'exact_pin' ) as $k ) {
			update_post_meta( $post_id, '_mk_' . $k, empty( $in[ $k ] ) ? 0 : 1 );
		}
		update_post_meta( $post_id, '_mk_location_label', isset( $in['location_label'] ) ? sanitize_text_field( $in['location_label'] ) : '' );
		update_post_meta( $post_id, '_mk_video_url', isset( $in['video_url'] ) ? esc_url_raw( $in['video_url'] ) : '' );

		foreach ( array( 'lat' => 90, 'lng' => 180 ) as $k => $max ) {
			$v = isset( $in[ $k ] ) ? trim( (string) $in[ $k ] ) : '';
			if ( '' === $v || ! is_numeric( $v ) || abs( (float) $v ) > $max ) {
				delete_post_meta( $post_id, '_mk_' . $k );
			} else {
				update_post_meta( $post_id, '_mk_' . $k, round( (float) $v, 7 ) );
			}
		}

		$gallery = isset( $in['gallery'] ) ? array_values( array_filter( array_map( 'absint', explode( ',', (string) $in['gallery'] ) ) ) ) : array();
		update_post_meta( $post_id, '_mk_gallery', $gallery );
		if ( $gallery && (int) get_post_thumbnail_id( $post_id ) !== $gallery[0] ) {
			set_post_thumbnail( $post_id, $gallery[0] );
		}

		$attrs = array();
		if ( ! empty( $in['attr_k'] ) && is_array( $in['attr_k'] ) ) {
			foreach ( $in['attr_k'] as $i => $label ) {
				$label = sanitize_text_field( $label );
				$value = isset( $in['attr_v'][ $i ] ) ? sanitize_text_field( $in['attr_v'][ $i ] ) : '';
				if ( '' !== $label ) {
					$attrs[] = array( $label, $value );
				}
			}
		}
		update_post_meta( $post_id, '_mk_attributes', $attrs );

		$ref = isset( $in['ref'] ) ? sanitize_text_field( $in['ref'] ) : '';
		if ( '' !== $ref ) {
			update_post_meta( $post_id, '_mk_ref', $ref );
		}
	}
}
