<?php
/**
 * [makaazi_slider] rendering.
 *
 * @package MakaaziSlider
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end output.
 */
class MKS_Render {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
	}

	/**
	 * Register shortcode.
	 */
	public static function shortcode() {
		add_shortcode( 'makaazi_slider', array( __CLASS__, 'render' ) );
	}

	/**
	 * Register assets.
	 */
	public static function register_assets() {
		$v = MKS_VERSION . '.' . (int) filemtime( MKS_DIR . 'assets/js/slider.js' );
		wp_register_style( 'makaazi-slider', MKS_URL . 'assets/css/slider.css', array(), $v );
		wp_register_script( 'makaazi-slider', MKS_URL . 'assets/js/slider.js', array(), $v, true );
	}

	/**
	 * Find a slider by id or slug.
	 *
	 * @param array $a Attributes.
	 * @return int
	 */
	private static function find( $a ) {
		if ( ! empty( $a['id'] ) && 'mk_slider' === get_post_type( absint( $a['id'] ) ) ) {
			return absint( $a['id'] );
		}
		if ( ! empty( $a['slug'] ) ) {
			$p = get_page_by_path( sanitize_title( $a['slug'] ), OBJECT, 'mk_slider' );
			if ( $p ) {
				return $p->ID;
			}
		}
		if ( empty( $a['id'] ) && empty( $a['slug'] ) ) {
			$latest = get_posts(
				array(
					'post_type'      => 'mk_slider',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			return $latest ? $latest[0] : 0;
		}
		return 0;
	}

	/**
	 * Build slides from featured properties.
	 *
	 * @param int $count How many.
	 * @return array
	 */
	private static function featured_slides( $count ) {
		if ( ! post_type_exists( 'property' ) || ! function_exists( 'makaazi_gallery_ids' ) ) {
			return array();
		}
		$q   = get_posts(
			array(
				'post_type'      => 'property',
				'posts_per_page' => max( 1, (int) $count ),
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_mk_featured', 'value' => '1' ) ), // phpcs:ignore
			)
		);
		$out = array();
		foreach ( $q as $id ) {
			$g     = makaazi_gallery_ids( $id );
			$out[] = wp_parse_args(
				array(
					'image'     => $g ? $g[0] : 0,
					'eyebrow'   => makaazi_location_text( $id ),
					'title'     => get_the_title( $id ),
					'text'      => wp_strip_all_tags( makaazi_price( $id, false ) ),
					'btn1_text' => __( 'View property', 'makaazi-slider' ),
					'btn1_url'  => get_permalink( $id ),
				),
				MKS_Slider::slide_defaults()
			);
		}
		return $out;
	}

	/**
	 * Slide visible today (schedule).
	 *
	 * @param array $s Slide.
	 * @return bool
	 */
	private static function live( $s ) {
		$today = current_time( 'Y-m-d' );
		if ( $s['from'] && $today < $s['from'] ) {
			return false;
		}
		if ( $s['until'] && $today > $s['until'] ) {
			return false;
		}
		return true;
	}

	/**
	 * Render.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = (array) $atts;
		$id   = self::find( $atts );
		if ( ! $id ) {
			return current_user_can( 'edit_posts' ) ? '<p class="mks-missing">' . esc_html__( 'Hero slider not found. Check the id or slug in [makaazi_slider].', 'makaazi-slider' ) . '</p>' : '';
		}
		$s = MKS_Slider::settings( $id );
		// Shortcode overrides.
		$yes = function ( $v ) {
			return in_array( strtolower( (string) $v ), array( '1', 'yes', 'true', 'on' ), true ) ? 1 : 0;
		};
		foreach ( array( 'effect', 'search_position', 'search_style', 'search_fields', 'button_text', 'buy_label', 'rent_label', 'stay_label', 'default_status' ) as $k ) {
			if ( isset( $atts[ $k ] ) && '' !== $atts[ $k ] ) {
				$s[ $k ] = sanitize_text_field( $atts[ $k ] );
			}
		}
		foreach ( array( 'autoplay', 'search', 'arrows', 'dots' ) as $k ) {
			if ( isset( $atts[ $k ] ) ) {
				$s[ $k ] = $yes( $atts[ $k ] );
			}
		}
		if ( isset( $atts['delay'] ) ) {
			$s['delay'] = max( 2, (int) $atts['delay'] );
		}
		if ( isset( $atts['height'] ) ) {
			if ( 'full' === $atts['height'] ) {
				$s['full'] = 1;
			} else {
				$s['height_desktop'] = max( 240, (int) $atts['height'] );
			}
		}

		$slides = array();
		if ( 'featured' !== $s['source'] ) {
			$slides = MKS_Slider::slides( $id );
		}
		if ( 'manual' !== $s['source'] ) {
			$slides = array_merge( $slides, self::featured_slides( $s['featured_count'] ) );
		}
		$slides = array_values( array_filter( $slides, array( __CLASS__, 'live' ) ) );
		if ( ! $slides ) {
			return '';
		}

		wp_enqueue_style( 'makaazi-slider' );
		wp_enqueue_script( 'makaazi-slider' );

		$effect = in_array( $s['effect'], array( 'fade', 'slide', 'kenburns' ), true ) ? $s['effect'] : 'fade';
		$pos    = in_array( $s['search_position'], array( 'overlap', 'inside', 'below' ), true ) ? $s['search_position'] : 'overlap';
		$search = '';
		if ( $s['search'] && class_exists( 'Makaazi_Render' ) ) {
			$style  = $s['search_style'];
			if ( 'glass' === $style && 'below' === $pos ) {
				$style = 'solid';
			}
			$search = Makaazi_Render::search_form(
				array(
					'fields'         => $s['search_fields'],
					'style'          => $style,
					'button_text'    => $s['button_text'],
					'buy_label'      => $s['buy_label'],
					'rent_label'     => $s['rent_label'],
					'stay_label'     => isset( $s['stay_label'] ) ? $s['stay_label'] : '',
					'default_status' => $s['default_status'],
					'id'             => 'mks-' . $id,
				)
			);
		}
		$n      = count( $slides );
		$config = array(
			'autoplay' => $s['autoplay'] && $n > 1 ? 1 : 0,
			'delay'    => max( 2, (int) $s['delay'] ) * 1000,
			'speed'    => max( 200, (int) $s['speed'] ),
			'pause'    => (int) $s['pause_hover'],
			'loop'     => (int) $s['loop'],
			'effect'   => $effect,
		);
		$style  = sprintf( '--mks-h:%dpx;--mks-ht:%dpx;--mks-hm:%dpx;--mks-speed:%dms;--mks-delay:%dms', (int) $s['height_desktop'], (int) $s['height_tablet'], (int) $s['height_mobile'], (int) $config['speed'], (int) $config['delay'] );

		$h  = '<div class="mks-wrap alignfull mks-pos-' . esc_attr( $pos ) . '">';
		$h .= '<section class="mks mks--' . esc_attr( $effect ) . ( $s['full'] ? ' mks--full' : '' ) . '" style="' . esc_attr( $style ) . '" data-mks="' . esc_attr( wp_json_encode( $config ) ) . '" aria-roledescription="carousel" aria-label="' . esc_attr( get_the_title( $id ) ) . '">';
		$h .= '<div class="mks__track" aria-live="off">';
		foreach ( $slides as $i => $sl ) {
			$align = in_array( $sl['align'], array( 'left', 'center', 'right' ), true ) ? $sl['align'] : 'left';
			/* translators: 1: slide number, 2: total */
			$h .= '<div class="mks__slide mks--align-' . $align . ( 0 === $i ? ' is-on' : '' ) . '" role="group" aria-roledescription="slide" aria-label="' . esc_attr( sprintf( __( '%1$d of %2$d', 'makaazi-slider' ), $i + 1, $n ) ) . '"' . ( $i ? ' aria-hidden="true"' : '' ) . ' style="--i:' . (int) $i . '">';
			$h .= '<div class="mks__media">';
			if ( $sl['video'] ) {
				$poster = $sl['image'] ? wp_get_attachment_image_url( $sl['image'], 'full' ) : '';
				$h     .= '<video class="mks__video" src="' . esc_url( $sl['video'] ) . '"' . ( $poster ? ' poster="' . esc_url( $poster ) . '"' : '' ) . ' muted loop playsinline autoplay preload="metadata"></video>';
			} elseif ( $sl['image'] ) {
				$h .= '<picture>';
				if ( $sl['mobile_image'] ) {
					$h .= '<source media="(max-width: 700px)" srcset="' . esc_url( wp_get_attachment_image_url( $sl['mobile_image'], 'large' ) ) . '">';
				}
				$h .= wp_get_attachment_image(
					$sl['image'],
					'full',
					false,
					array(
						'class'         => 'mks__img',
						'loading'       => $i ? 'lazy' : 'eager',
						'fetchpriority' => $i ? 'auto' : 'high',
						'sizes'         => '100vw',
						'alt'           => '',
					)
				);
				$h .= '</picture>';
			}
			$h .= '</div><div class="mks__shade" style="--mks-o:' . ( max( 0, min( 90, (int) $sl['overlay'] ) ) / 100 ) . '"></div>';
			$h .= '<div class="mks__content"><div class="mks__inner">';
			if ( $sl['eyebrow'] ) {
				$h .= '<p class="mks__eyebrow">' . esc_html( $sl['eyebrow'] ) . '</p>';
			}
			if ( $sl['title'] ) {
				$h .= ( 0 === $i ? '<h1' : '<h2' ) . ' class="mks__title">' . esc_html( $sl['title'] ) . ( 0 === $i ? '</h1>' : '</h2>' );
			}
			if ( $sl['text'] ) {
				$h .= '<p class="mks__text">' . esc_html( $sl['text'] ) . '</p>';
			}
			if ( $sl['btn1_text'] || $sl['btn2_text'] ) {
				$h .= '<div class="mks__btns">';
				if ( $sl['btn1_text'] ) {
					$h .= '<a class="mks__btn mks__btn--primary" href="' . esc_url( $sl['btn1_url'] ? $sl['btn1_url'] : '#' ) . '"' . ( $i ? ' tabindex="-1"' : '' ) . '>' . esc_html( $sl['btn1_text'] ) . '</a>';
				}
				if ( $sl['btn2_text'] ) {
					$h .= '<a class="mks__btn mks__btn--ghost" href="' . esc_url( $sl['btn2_url'] ? $sl['btn2_url'] : '#' ) . '"' . ( $i ? ' tabindex="-1"' : '' ) . '>' . esc_html( $sl['btn2_text'] ) . '</a>';
				}
				$h .= '</div>';
			}
			$h .= '</div></div></div>';
		}
		$h .= '</div>';
		if ( 'inside' === $pos && $search ) {
			$h .= '<div class="mks__search mks__search--inside">' . $search . '</div>';
		}
		if ( $n > 1 ) {
			if ( $s['arrows'] ) {
				$h .= '<button type="button" class="mks__arrow mks__arrow--prev" data-mks-go="-1" aria-label="' . esc_attr__( 'Previous slide', 'makaazi-slider' ) . '"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>';
				$h .= '<button type="button" class="mks__arrow mks__arrow--next" data-mks-go="1" aria-label="' . esc_attr__( 'Next slide', 'makaazi-slider' ) . '"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></button>';
			}
			$h .= '<div class="mks__ui">';
			if ( $s['dots'] ) {
				$h .= '<div class="mks__dots">';
				for ( $i = 0; $i < $n; $i++ ) {
					/* translators: %d: slide number */
					$h .= '<button type="button" class="' . ( 0 === $i ? 'is-on' : '' ) . '" data-mks-to="' . $i . '" aria-label="' . esc_attr( sprintf( __( 'Show slide %d', 'makaazi-slider' ), $i + 1 ) ) . '"' . ( 0 === $i ? ' aria-current="true"' : '' ) . '></button>';
				}
				$h .= '</div>';
			}
			if ( $config['autoplay'] ) {
				$h .= '<button type="button" class="mks__pause" data-mks-pause aria-label="' . esc_attr__( 'Pause slideshow', 'makaazi-slider' ) . '" data-label-play="' . esc_attr__( 'Play slideshow', 'makaazi-slider' ) . '" data-label-pause="' . esc_attr__( 'Pause slideshow', 'makaazi-slider' ) . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path class="mks-i-pause" d="M7 5h3v14H7zM14 5h3v14h-3z"/><path class="mks-i-play" d="M8 5l11 7-11 7z"/></svg></button>';
			}
			$h .= '</div>';
			if ( $config['autoplay'] && $s['progress'] ) {
				$h .= '<div class="mks__progress" aria-hidden="true"><i></i></div>';
			}
		}
		$h .= '</section>';
		if ( $search && 'inside' !== $pos ) {
			$h .= '<div class="mks__search mks__search--' . esc_attr( $pos ) . '">' . $search . '</div>';
		}
		return $h . '</div>';
	}
}
