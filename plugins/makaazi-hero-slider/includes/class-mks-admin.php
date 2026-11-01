<?php
/**
 * Slider editing screen.
 *
 * @package MakaaziSlider
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta boxes for slides, behaviour and the search bar.
 */
class MKS_Admin {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'add_meta_boxes_mk_slider', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_mk_slider', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'manage_mk_slider_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_mk_slider_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
	}

	/**
	 * Assets.
	 */
	public static function assets() {
		$s = get_current_screen();
		if ( ! $s || 'mk_slider' !== $s->post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'mks-admin', MKS_URL . 'assets/css/admin.css', array(), MKS_VERSION );
		wp_enqueue_script( 'mks-admin', MKS_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), MKS_VERSION, true );
		wp_localize_script(
			'mks-admin',
			'MKS_ADMIN',
			array(
				'choose' => __( 'Choose image', 'makaazi-slider' ),
				'copied' => __( 'Copied', 'makaazi-slider' ),
				'slide'  => __( 'Slide', 'makaazi-slider' ),
				'confirm' => __( 'Click again to delete', 'makaazi-slider' ),
			)
		);
	}

	/**
	 * List columns.
	 *
	 * @param array $c Columns.
	 * @return array
	 */
	public static function columns( $c ) {
		return array(
			'cb'         => $c['cb'],
			'title'      => $c['title'],
			'mks_slides' => __( 'Slides', 'makaazi-slider' ),
			'mks_code'   => __( 'Shortcode', 'makaazi-slider' ),
			'date'       => $c['date'],
		);
	}

	/**
	 * Column values.
	 *
	 * @param string $col Column.
	 * @param int    $id  Post ID.
	 */
	public static function column( $col, $id ) {
		if ( 'mks_slides' === $col ) {
			echo (int) count( MKS_Slider::slides( $id ) );
		} elseif ( 'mks_code' === $col ) {
			echo '<code>[makaazi_slider slug="' . esc_html( get_post_field( 'post_name', $id ) ) . '"]</code>';
		}
	}

	/**
	 * Boxes.
	 */
	public static function boxes() {
		add_meta_box( 'mks_slides', __( 'Slides', 'makaazi-slider' ), array( __CLASS__, 'slides_box' ), 'mk_slider', 'normal', 'high' );
		add_meta_box( 'mks_settings', __( 'Slider settings', 'makaazi-slider' ), array( __CLASS__, 'settings_box' ), 'mk_slider', 'normal', 'default' );
		add_meta_box( 'mks_search', __( 'Search bar', 'makaazi-slider' ), array( __CLASS__, 'search_box' ), 'mk_slider', 'normal', 'default' );
		add_meta_box( 'mks_code', __( 'Shortcode', 'makaazi-slider' ), array( __CLASS__, 'code_box' ), 'mk_slider', 'side', 'high' );
	}

	/**
	 * Shortcode box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function code_box( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Publish the slider to get its shortcode.', 'makaazi-slider' ) . '</p>';
			return;
		}
		foreach ( array( '[makaazi_slider slug="' . $post->post_name . '"]', '[makaazi_slider id="' . $post->ID . '"]' ) as $code ) {
			echo '<p class="mks-code"><code>' . esc_html( $code ) . '</code> <button type="button" class="button button-small" data-mks-copy="' . esc_attr( $code ) . '">' . esc_html__( 'Copy', 'makaazi-slider' ) . '</button></p>';
		}
		echo '<p class="description">' . esc_html__( 'Paste it into a Shortcode block or page builder. The slug version keeps working after you move the site.', 'makaazi-slider' ) . '</p>';
	}

	/**
	 * One slide editor row.
	 *
	 * @param string|int $k Index key.
	 * @param array      $s Slide.
	 * @return string
	 */
	private static function slide_row( $k, $s ) {
		$s    = wp_parse_args( $s, MKS_Slider::slide_defaults() );
		$name = function ( $f ) use ( $k ) {
			return 'mks_slides[' . $k . '][' . $f . ']';
		};
		$img  = function ( $f, $label ) use ( $s, $name ) {
			$id = (int) $s[ $f ];
			return '<div class="mks-img" data-mks-img><span class="mks-img__label">' . esc_html( $label ) . '</span><input type="hidden" name="' . esc_attr( $name( $f ) ) . '" value="' . $id . '"><span class="mks-img__preview">' . ( $id ? wp_get_attachment_image( $id, 'medium' ) : '' ) . '</span><span class="mks-img__btns"><button type="button" class="button" data-pick>' . esc_html__( 'Choose', 'makaazi-slider' ) . '</button> <button type="button" class="button-link" data-clear>' . esc_html__( 'Remove', 'makaazi-slider' ) . '</button></span></div>';
		};
		$txt  = function ( $f, $label, $ph = '' ) use ( $s, $name ) {
			return '<label>' . esc_html( $label ) . '<input type="text" name="' . esc_attr( $name( $f ) ) . '" value="' . esc_attr( $s[ $f ] ) . '" placeholder="' . esc_attr( $ph ) . '"></label>';
		};
		$h  = '<div class="mks-slide" data-mks-slide><div class="mks-slide__head"><span class="mks-slide__handle dashicons dashicons-menu" title="' . esc_attr__( 'Drag to reorder', 'makaazi-slider' ) . '"></span><strong class="mks-slide__title">' . esc_html( $s['title'] ? $s['title'] : __( 'New slide', 'makaazi-slider' ) ) . '</strong><span class="mks-slide__acts"><button type="button" class="button-link" data-mks-toggle>' . esc_html__( 'Edit', 'makaazi-slider' ) . '</button> · <button type="button" class="button-link" data-mks-dup>' . esc_html__( 'Duplicate', 'makaazi-slider' ) . '</button> · <button type="button" class="button-link mks-del" data-mks-del>' . esc_html__( 'Delete', 'makaazi-slider' ) . '</button></span></div>';
		$h .= '<div class="mks-slide__body"><div class="mks-slide__media">' . $img( 'image', __( 'Background image', 'makaazi-slider' ) ) . $img( 'mobile_image', __( 'Phone image (optional)', 'makaazi-slider' ) ) . '<label>' . esc_html__( 'Background video URL (optional MP4)', 'makaazi-slider' ) . '<input type="url" name="' . esc_attr( $name( 'video' ) ) . '" value="' . esc_attr( $s['video'] ) . '" placeholder="https://"></label></div>';
		$h .= '<div class="mks-slide__fields">' . $txt( 'eyebrow', __( 'Small label', 'makaazi-slider' ), __( 'e.g. New this week', 'makaazi-slider' ) ) . $txt( 'title', __( 'Heading', 'makaazi-slider' ) );
		$h .= '<label class="mks-span">' . esc_html__( 'Text', 'makaazi-slider' ) . '<textarea rows="2" name="' . esc_attr( $name( 'text' ) ) . '">' . esc_textarea( $s['text'] ) . '</textarea></label>';
		$h .= $txt( 'btn1_text', __( 'Button 1 text', 'makaazi-slider' ) ) . $txt( 'btn1_url', __( 'Button 1 link', 'makaazi-slider' ), '/properties/' ) . $txt( 'btn2_text', __( 'Button 2 text', 'makaazi-slider' ) ) . $txt( 'btn2_url', __( 'Button 2 link', 'makaazi-slider' ) );
		$h .= '<label>' . esc_html__( 'Text alignment', 'makaazi-slider' ) . '<select name="' . esc_attr( $name( 'align' ) ) . '">';
		foreach ( array(
			'left' => __( 'Left', 'makaazi-slider' ),
			'center' => __( 'Centre', 'makaazi-slider' ),
			'right' => __( 'Right', 'makaazi-slider' ),
		) as $v => $l ) {
			$h .= '<option value="' . $v . '"' . selected( $s['align'], $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		$h .= '</select></label><label>' . esc_html__( 'Overlay darkness', 'makaazi-slider' ) . ' <output>' . (int) $s['overlay'] . '%</output><input type="range" min="0" max="90" step="5" name="' . esc_attr( $name( 'overlay' ) ) . '" value="' . (int) $s['overlay'] . '" oninput="this.previousElementSibling.value=this.value+\'%\'"></label>';
		$h .= '<label>' . esc_html__( 'Show from', 'makaazi-slider' ) . '<input type="date" name="' . esc_attr( $name( 'from' ) ) . '" value="' . esc_attr( $s['from'] ) . '"></label><label>' . esc_html__( 'Show until', 'makaazi-slider' ) . '<input type="date" name="' . esc_attr( $name( 'until' ) ) . '" value="' . esc_attr( $s['until'] ) . '"></label>';
		$h .= '</div></div></div>';
		return $h;
	}

	/**
	 * Slides box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function slides_box( $post ) {
		wp_nonce_field( 'mks_save', 'mks_nonce' );
		$slides = MKS_Slider::slides( $post->ID );
		echo '<p class="description">' . esc_html__( 'Images look best at 1920 × 1080 px or larger. Drag slides to reorder. Slides with dates only show between them.', 'makaazi-slider' ) . '</p>';
		echo '<div class="mks-slides" data-mks-slides>';
		foreach ( $slides as $i => $s ) {
			echo self::slide_row( $i, $s ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
		}
		echo '</div><p><button type="button" class="button button-primary" data-mks-add>' . esc_html__( 'Add slide', 'makaazi-slider' ) . '</button></p>';
		echo '<script type="text/html" id="mks-slide-tpl">' . self::slide_row( '__i__', array() ) . '</script>'; // phpcs:ignore
	}

	/**
	 * Radio group helper.
	 *
	 * @param string $key  Setting key.
	 * @param array  $opts Options.
	 * @param array  $s    Settings.
	 * @return string
	 */
	private static function radios( $key, $opts, $s ) {
		$h = '<span class="mks-radios">';
		foreach ( $opts as $v => $l ) {
			$h .= '<label><input type="radio" name="mks[' . esc_attr( $key ) . ']" value="' . esc_attr( $v ) . '"' . checked( (string) $s[ $key ], (string) $v, false ) . '> ' . esc_html( $l ) . '</label>';
		}
		return $h . '</span>';
	}

	/**
	 * Checkbox helper.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $s     Settings.
	 * @return string
	 */
	private static function check( $key, $label, $s ) {
		return '<label class="mks-ck"><input type="checkbox" name="mks[' . esc_attr( $key ) . ']" value="1"' . checked( (bool) $s[ $key ], true, false ) . '> ' . esc_html( $label ) . '</label>';
	}

	/**
	 * Settings box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function settings_box( $post ) {
		$s = MKS_Slider::settings( $post->ID );
		echo '<table class="form-table mks-form"><tbody>';
		echo '<tr><th>' . esc_html__( 'Slides from', 'makaazi-slider' ) . '</th><td>' . self::radios( 'source', array( 'manual' => __( 'Slides above', 'makaazi-slider' ), 'featured' => __( 'Featured properties', 'makaazi-slider' ), 'mixed' => __( 'Both', 'makaazi-slider' ) ), $s ) . ' <label>' . esc_html__( 'Featured count', 'makaazi-slider' ) . ' <input type="number" min="1" max="10" class="small-text" name="mks[featured_count]" value="' . (int) $s['featured_count'] . '"></label></td></tr>'; // phpcs:ignore
		echo '<tr><th>' . esc_html__( 'Effect', 'makaazi-slider' ) . '</th><td>' . self::radios( 'effect', array( 'fade' => __( 'Fade', 'makaazi-slider' ), 'slide' => __( 'Slide', 'makaazi-slider' ), 'kenburns' => __( 'Ken Burns (slow zoom)', 'makaazi-slider' ) ), $s ) . '</td></tr>'; // phpcs:ignore
		echo '<tr><th>' . esc_html__( 'Autoplay', 'makaazi-slider' ) . '</th><td>' . self::check( 'autoplay', __( 'Play automatically', 'makaazi-slider' ), $s ) . ' <label>' . esc_html__( 'every', 'makaazi-slider' ) . ' <input type="number" min="2" max="30" class="small-text" name="mks[delay]" value="' . (int) $s['delay'] . '"> ' . esc_html__( 'seconds', 'makaazi-slider' ) . '</label> ' . self::check( 'pause_hover', __( 'Pause on hover', 'makaazi-slider' ), $s ) . ' ' . self::check( 'loop', __( 'Loop', 'makaazi-slider' ), $s ) . '<p class="description">' . esc_html__( 'Visitors who prefer reduced motion never get autoplay or zoom.', 'makaazi-slider' ) . '</p></td></tr>'; // phpcs:ignore
		echo '<tr><th>' . esc_html__( 'Transition speed', 'makaazi-slider' ) . '</th><td><input type="number" min="200" max="3000" step="100" class="small-text" name="mks[speed]" value="' . (int) $s['speed'] . '"> ms</td></tr>';
		echo '<tr><th>' . esc_html__( 'Navigation', 'makaazi-slider' ) . '</th><td>' . self::check( 'arrows', __( 'Arrows', 'makaazi-slider' ), $s ) . ' ' . self::check( 'dots', __( 'Dots', 'makaazi-slider' ), $s ) . ' ' . self::check( 'progress', __( 'Progress bar', 'makaazi-slider' ), $s ) . '</td></tr>'; // phpcs:ignore
		echo '<tr><th>' . esc_html__( 'Height', 'makaazi-slider' ) . '</th><td>' . self::check( 'full', __( 'Full screen', 'makaazi-slider' ), $s ) . ' <span class="mks-h"><label>' . esc_html__( 'Desktop', 'makaazi-slider' ) . ' <input type="number" class="small-text" name="mks[height_desktop]" value="' . (int) $s['height_desktop'] . '"></label> <label>' . esc_html__( 'Tablet', 'makaazi-slider' ) . ' <input type="number" class="small-text" name="mks[height_tablet]" value="' . (int) $s['height_tablet'] . '"></label> <label>' . esc_html__( 'Phone', 'makaazi-slider' ) . ' <input type="number" class="small-text" name="mks[height_mobile]" value="' . (int) $s['height_mobile'] . '"></label> px</span></td></tr>'; // phpcs:ignore
		echo '</tbody></table>';
	}

	/**
	 * Search bar box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function search_box( $post ) {
		$s = MKS_Slider::settings( $post->ID );
		if ( ! class_exists( 'Makaazi_Render' ) ) {
			echo '<p>' . esc_html__( 'Activate Makaazi Core to show the property search bar.', 'makaazi-slider' ) . '</p>';
		}
		$all     = array(
			'location' => __( 'Location', 'makaazi-slider' ),
			'type'     => __( 'Property type', 'makaazi-slider' ),
			'price'    => __( 'Price range', 'makaazi-slider' ),
			'beds'     => __( 'Bedrooms', 'makaazi-slider' ),
			'keyword'  => __( 'Keyword or reference', 'makaazi-slider' ),
		);
		$enabled = array_values( array_intersect( array_map( 'trim', explode( ',', $s['search_fields'] ) ), array_keys( $all ) ) );
		$order   = array_merge( $enabled, array_diff( array_keys( $all ), $enabled ) );
		echo '<table class="form-table mks-form"><tbody>';
		echo '<tr><th>' . esc_html__( 'Show search', 'makaazi-slider' ) . '</th><td>' . self::check( 'search', __( 'Show the Buy/Rent search bar', 'makaazi-slider' ), $s ) . '</td></tr>'; // phpcs:ignore
		echo '<tr><th>' . esc_html__( 'Position', 'makaazi-slider' ) . '</th><td>' . self::radios( 'search_position', array( 'overlap' => __( 'Overlapping the bottom edge', 'makaazi-slider' ), 'inside' => __( 'Inside the slide', 'makaazi-slider' ), 'below' => __( 'Below the slider', 'makaazi-slider' ) ), $s ) . '</td></tr>'; // phpcs:ignore
		echo '<tr><th>' . esc_html__( 'Style', 'makaazi-slider' ) . '</th><td>' . self::radios( 'search_style', array( 'solid' => __( 'Solid', 'makaazi-slider' ), 'glass' => __( 'Glass (over the image)', 'makaazi-slider' ), 'minimal' => __( 'Minimal', 'makaazi-slider' ) ), $s ) . '</td></tr>'; // phpcs:ignore
		echo '<tr><th>' . esc_html__( 'Fields', 'makaazi-slider' ) . '</th><td><input type="hidden" name="mks[search_fields]" value="' . esc_attr( implode( ',', $enabled ) ) . '" data-mks-fields-input><ul class="mks-fields" data-mks-fields>';
		foreach ( $order as $k ) {
			echo '<li data-k="' . esc_attr( $k ) . '"><span class="dashicons dashicons-menu"></span><label><input type="checkbox"' . checked( in_array( $k, $enabled, true ), true, false ) . '> ' . esc_html( $all[ $k ] ) . '</label></li>';
		}
		echo '</ul><p class="description">' . esc_html__( 'Tick the fields to show and drag to change their order. Price brackets for Buy and Rent are set in Properties › Settings › Price brackets.', 'makaazi-slider' ) . '</p></td></tr>';
		echo '<tr><th>' . esc_html__( 'Labels', 'makaazi-slider' ) . '</th><td class="mks-labels"><label>' . esc_html__( 'Buy tab', 'makaazi-slider' ) . ' <input type="text" name="mks[buy_label]" value="' . esc_attr( $s['buy_label'] ) . '"></label> <label>' . esc_html__( 'Rent tab', 'makaazi-slider' ) . ' <input type="text" name="mks[rent_label]" value="' . esc_attr( $s['rent_label'] ) . '"></label> <label>' . esc_html__( 'Short Stay tab', 'makaazi-slider' ) . ' <input type="text" name="mks[stay_label]" value="' . esc_attr( $s['stay_label'] ) . '"></label> <label>' . esc_html__( 'Button', 'makaazi-slider' ) . ' <input type="text" name="mks[button_text]" value="' . esc_attr( $s['button_text'] ) . '"></label></td></tr>';
		echo '<tr><th>' . esc_html__( 'Open on', 'makaazi-slider' ) . '</th><td>' . self::radios( 'default_status', array( 'sale' => __( 'Buy', 'makaazi-slider' ), 'rent' => __( 'Rent', 'makaazi-slider' ), 'stay' => __( 'Short Stay', 'makaazi-slider' ) ), $s ) . '</td></tr>'; // phpcs:ignore
		echo '</tbody></table>';
	}

	/**
	 * Save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['mks_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mks_nonce'] ) ), 'mks_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw    = isset( $_POST['mks_slides'] ) && is_array( $_POST['mks_slides'] ) ? wp_unslash( $_POST['mks_slides'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$slides = array();
		foreach ( $raw as $k => $s ) {
			if ( '__i__' === (string) $k || ! is_array( $s ) ) {
				continue;
			}
			$slides[] = array(
				'image'        => isset( $s['image'] ) ? absint( $s['image'] ) : 0,
				'mobile_image' => isset( $s['mobile_image'] ) ? absint( $s['mobile_image'] ) : 0,
				'video'        => isset( $s['video'] ) ? esc_url_raw( $s['video'] ) : '',
				'eyebrow'      => isset( $s['eyebrow'] ) ? sanitize_text_field( $s['eyebrow'] ) : '',
				'title'        => isset( $s['title'] ) ? sanitize_text_field( $s['title'] ) : '',
				'text'         => isset( $s['text'] ) ? sanitize_textarea_field( $s['text'] ) : '',
				'btn1_text'    => isset( $s['btn1_text'] ) ? sanitize_text_field( $s['btn1_text'] ) : '',
				'btn1_url'     => isset( $s['btn1_url'] ) ? esc_url_raw( $s['btn1_url'] ) : '',
				'btn2_text'    => isset( $s['btn2_text'] ) ? sanitize_text_field( $s['btn2_text'] ) : '',
				'btn2_url'     => isset( $s['btn2_url'] ) ? esc_url_raw( $s['btn2_url'] ) : '',
				'align'        => isset( $s['align'] ) && in_array( $s['align'], array( 'left', 'center', 'right' ), true ) ? $s['align'] : 'left',
				'overlay'      => isset( $s['overlay'] ) ? max( 0, min( 90, (int) $s['overlay'] ) ) : 55,
				'from'         => isset( $s['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s['from'] ) ? $s['from'] : '',
				'until'        => isset( $s['until'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s['until'] ) ? $s['until'] : '',
			);
		}
		update_post_meta( $post_id, '_mks_slides', $slides );

		$in  = isset( $_POST['mks'] ) && is_array( $_POST['mks'] ) ? wp_unslash( $_POST['mks'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$d   = MKS_Slider::defaults();
		$out = array();
		$enum = array(
			'source'          => array( 'manual', 'featured', 'mixed' ),
			'effect'          => array( 'fade', 'slide', 'kenburns' ),
			'search_position' => array( 'overlap', 'inside', 'below' ),
			'search_style'    => array( 'solid', 'glass', 'minimal' ),
			'default_status'  => array( 'sale', 'rent', 'stay' ),
		);
		foreach ( $enum as $k => $allowed ) {
			$out[ $k ] = isset( $in[ $k ] ) && in_array( $in[ $k ], $allowed, true ) ? $in[ $k ] : $d[ $k ];
		}
		foreach ( array( 'autoplay', 'pause_hover', 'loop', 'arrows', 'dots', 'progress', 'full', 'search' ) as $k ) {
			$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		}
		foreach ( array( 'featured_count', 'delay', 'speed', 'height_desktop', 'height_tablet', 'height_mobile' ) as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) && '' !== $in[ $k ] ? absint( $in[ $k ] ) : $d[ $k ];
		}
		foreach ( array( 'buy_label', 'rent_label', 'stay_label', 'button_text' ) as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) && '' !== trim( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : $d[ $k ];
		}
		$fields               = isset( $in['search_fields'] ) ? array_intersect( array_map( 'trim', explode( ',', $in['search_fields'] ) ), array( 'location', 'type', 'price', 'beds', 'keyword' ) ) : array();
		$out['search_fields'] = implode( ',', $fields );
		update_post_meta( $post_id, '_mks_settings', $out );
	}
}
