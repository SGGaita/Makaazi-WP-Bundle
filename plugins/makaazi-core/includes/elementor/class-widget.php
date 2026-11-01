<?php
/**
 * Base class for Makaazi Elementor widgets.
 *
 * A widget declares its shortcode and its fields; this class turns the fields into Elementor
 * controls, adds shared Style controls, and renders the shortcode with the chosen values.
 *
 * Field definition (key = shortcode attribute):
 *   'type'       text|textarea|number|select|multi|switch|slider_px
 *   'label'      Control label.
 *   'options'    For select/multi: value => label (or a callable returning that).
 *   'default'    Default value.
 *   'responsive' true for columns-style controls (adds _tablet/_mobile attributes).
 *   'section'    Section key from sections(); defaults to 'content'.
 *   'condition'  Elementor condition array.
 *   'yesno'      For switch: send yes/no (default) or 1/0 when 'bool'.
 *   'help'       Description under the control.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Shortcode-backed widget.
 */
abstract class Makaazi_Elementor_Widget extends Widget_Base {

	/**
	 * Shortcode tag.
	 *
	 * @return string
	 */
	abstract protected function tag();

	/**
	 * Field definitions.
	 *
	 * @return array
	 */
	abstract protected function fields();

	/**
	 * Content sections: key => label.
	 *
	 * @return array
	 */
	protected function sections() {
		return array( 'content' => __( 'Content', 'makaazi' ) );
	}

	/**
	 * Help Center article for the "Need help?" link.
	 *
	 * @return string
	 */
	protected function help_article() {
		return 'elementor';
	}

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return str_replace( '_', '-', $this->tag() );
	}

	/**
	 * Category.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'makaazi' );
	}

	/**
	 * Keywords for the panel search.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'makaazi', 'property', 'properties', 'real estate', 'listing' );
	}

	/**
	 * Help link in the panel.
	 *
	 * @return string
	 */
	public function get_custom_help_url() {
		return class_exists( 'Makaazi_Help' ) ? Makaazi_Help::url( $this->help_article() ) : '';
	}

	/**
	 * Scripts.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'makaazi', 'makaazi-elementor' );
	}

	/**
	 * Styles.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'makaazi' );
	}

	/**
	 * Output depends on the database (listings change), so never cache it.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * No extra inner wrapper.
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ---------------------------------------------------------------------
	 * Option helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Terms as slug => name.
	 *
	 * @param string $tax   Taxonomy.
	 * @param string $empty Label for the empty choice ('' for none).
	 * @return array
	 */
	protected static function terms( $tax, $empty = '' ) {
		$out   = $empty ? array( '' => $empty ) : array();
		$terms = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return $out;
		}
		$by_parent = array();
		foreach ( $terms as $t ) {
			$by_parent[ $t->parent ][] = $t;
		}
		$walk = function ( $parent, $depth ) use ( &$walk, &$out, $by_parent ) {
			if ( empty( $by_parent[ $parent ] ) ) {
				return;
			}
			foreach ( $by_parent[ $parent ] as $t ) {
				$out[ $t->slug ] = str_repeat( '— ', $depth ) . $t->name;
				$walk( $t->term_id, $depth + 1 );
			}
		};
		$walk( 0, 0 );
		return $out;
	}

	/**
	 * Posts as ID => title.
	 *
	 * @param string $type  Post type.
	 * @param string $empty Label for the empty choice.
	 * @param int    $limit Max.
	 * @return array
	 */
	protected static function posts( $type, $empty = '', $limit = 200 ) {
		$out = $empty ? array( '' => $empty ) : array();
		foreach ( get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		) as $p ) {
			$out[ (string) $p->ID ] = $p->post_title ? $p->post_title : '#' . $p->ID;
		}
		return $out;
	}

	/**
	 * Card parts that can be hidden.
	 *
	 * @return array
	 */
	protected static function card_parts() {
		return array(
			'badges'    => __( 'Badges', 'makaazi' ),
			'category'  => __( 'Category', 'makaazi' ),
			'use'       => __( 'Residential / Commercial', 'makaazi' ),
			'type'      => __( 'Type', 'makaazi' ),
			'location'  => __( 'Location', 'makaazi' ),
			'beds'      => __( 'Bedrooms', 'makaazi' ),
			'baths'     => __( 'Bathrooms', 'makaazi' ),
			'parking'   => __( 'Parking', 'makaazi' ),
			'area'      => __( 'Area', 'makaazi' ),
			'favourite' => __( 'Favourite heart', 'makaazi' ),
			'compare'   => __( 'Compare button', 'makaazi' ),
		);
	}

	/**
	 * Common heading fields.
	 *
	 * @param string $section Section key.
	 * @return array
	 */
	protected static function heading_fields( $section = 'heading' ) {
		return array(
			'eyebrow' => array(
				'type'    => 'text',
				'label'   => __( 'Small label', 'makaazi' ),
				'section' => $section,
			),
			'title'   => array(
				'type'    => 'text',
				'label'   => __( 'Title', 'makaazi' ),
				'section' => $section,
			),
		);
	}

	/* ---------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------- */

	/**
	 * Register controls from fields(), then the shared Style controls.
	 */
	protected function register_controls() {
		$fields   = $this->fields();
		$sections = $this->sections();
		foreach ( $sections as $skey => $slabel ) {
			$mine = array_filter(
				$fields,
				function ( $f ) use ( $skey ) {
					return ( isset( $f['section'] ) ? $f['section'] : 'content' ) === $skey;
				}
			);
			if ( ! $mine ) {
				continue;
			}
			$this->start_controls_section(
				'mk_section_' . $skey,
				array(
					'label' => $slabel,
					'tab'   => Controls_Manager::TAB_CONTENT,
				)
			);
			foreach ( $mine as $key => $f ) {
				$this->add_field( $key, $f );
			}
			$this->end_controls_section();
		}
		$this->register_style_controls();
	}

	/**
	 * One control.
	 *
	 * @param string $key Attribute.
	 * @param array  $f   Field.
	 */
	protected function add_field( $key, $f ) {
		$args = array( 'label' => $f['label'] );
		if ( isset( $f['help'] ) ) {
			$args['description'] = $f['help'];
		}
		if ( isset( $f['condition'] ) ) {
			$args['condition'] = $f['condition'];
		}
		$options = isset( $f['options'] ) ? ( is_callable( $f['options'] ) ? call_user_func( $f['options'] ) : $f['options'] ) : array();
		switch ( $f['type'] ) {
			case 'select':
				$args['type']    = Controls_Manager::SELECT;
				$args['options'] = $options;
				$args['default'] = isset( $f['default'] ) ? (string) $f['default'] : (string) key( $options );
				break;
			case 'multi':
				$args['type']        = Controls_Manager::SELECT2;
				$args['options']     = $options;
				$args['multiple']    = true;
				$args['label_block'] = true;
				$args['default']     = isset( $f['default'] ) ? (array) $f['default'] : array();
				break;
			case 'switch':
				$args['type']         = Controls_Manager::SWITCHER;
				$args['return_value'] = 'yes';
				$args['default']      = ! empty( $f['default'] ) ? 'yes' : '';
				break;
			case 'number':
				$args['type'] = Controls_Manager::NUMBER;
				foreach ( array( 'min', 'max', 'step' ) as $k ) {
					if ( isset( $f[ $k ] ) ) {
						$args[ $k ] = $f[ $k ];
					}
				}
				$args['default'] = isset( $f['default'] ) ? $f['default'] : '';
				break;
			case 'textarea':
				$args['type']    = Controls_Manager::TEXTAREA;
				$args['default'] = isset( $f['default'] ) ? $f['default'] : '';
				break;
			default:
				$args['type']        = Controls_Manager::TEXT;
				$args['default']     = isset( $f['default'] ) ? $f['default'] : '';
				$args['label_block'] = ! empty( $f['label_block'] );
				if ( isset( $f['placeholder'] ) ) {
					$args['placeholder'] = $f['placeholder'];
				}
		}
		if ( ! empty( $f['responsive'] ) ) {
			$args['tablet_default'] = isset( $f['tablet_default'] ) ? $f['tablet_default'] : '';
			$args['mobile_default'] = isset( $f['mobile_default'] ) ? $f['mobile_default'] : '';
			$this->add_responsive_control( $key, $args );
		} else {
			$this->add_control( $key, $args );
		}
	}

	/**
	 * Selectors that carry the --mk-* colour variables.
	 *
	 * @return string
	 */
	protected function var_selector() {
		$parts = array( '.mk-block', '.mk-listing', '.mk-single', '.mk-search', '.mk-property', '.mk-card' );
		return implode(
			', ',
			array_map(
				function ( $s ) {
					return '{{WRAPPER}} ' . $s;
				},
				$parts
			)
		);
	}

	/**
	 * Typography targets for this widget: key => array( label, selector ).
	 *
	 * @return array
	 */
	protected function typography_targets() {
		return array(
			'heading'    => array( __( 'Section title', 'makaazi' ), '{{WRAPPER}} .mk-sechead__title' ),
			'card_title' => array( __( 'Card title', 'makaazi' ), '{{WRAPPER}} .mk-card__title' ),
			'card_price' => array( __( 'Price', 'makaazi' ), '{{WRAPPER}} .mk-card__price' ),
			'button'     => array( __( 'Buttons', 'makaazi' ), '{{WRAPPER}} .mk-btn' ),
		);
	}

	/**
	 * Shared Style tab: colours, corners and typography.
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'mk_style_colours',
			array(
				'label' => __( 'Colours', 'makaazi' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'mk_style_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Leave empty to use the theme colours.', 'makaazi' ),
				'content_classes' => 'elementor-descriptor',
			)
		);
		$vars = array(
			'primary' => __( 'Primary (buttons, links)', 'makaazi' ),
			'accent'  => __( 'Accent (labels, highlights)', 'makaazi' ),
			'ink'     => __( 'Text', 'makaazi' ),
			'muted'   => __( 'Secondary text', 'makaazi' ),
			'bg'      => __( 'Card background', 'makaazi' ),
			'surface' => __( 'Soft background', 'makaazi' ),
			'line'    => __( 'Borders', 'makaazi' ),
		);
		foreach ( $vars as $k => $label ) {
			$this->add_control(
				'mk_c_' . $k,
				array(
					'label'     => $label,
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $this->var_selector() => '--mk-' . $k . ': {{VALUE}};' ),
				)
			);
		}
		$this->add_control(
			'mk_radius',
			array(
				'label'      => __( 'Corner radius', 'makaazi' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 24,
					),
				),
				'selectors'  => array( $this->var_selector() => '--mk-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();

		$targets = $this->typography_targets();
		if ( $targets ) {
			$this->start_controls_section(
				'mk_style_type',
				array(
					'label' => __( 'Typography', 'makaazi' ),
					'tab'   => Controls_Manager::TAB_STYLE,
				)
			);
			foreach ( $targets as $k => $t ) {
				$this->add_group_control(
					Group_Control_Typography::get_type(),
					array(
						'name'     => 'mk_t_' . $k,
						'label'    => $t[0],
						'selector' => $t[1],
					)
				);
			}
			$this->end_controls_section();
		}
	}

	/* ---------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------- */

	/**
	 * Settings → shortcode attributes.
	 *
	 * @param array $s Settings.
	 * @return array
	 */
	protected function atts( $s ) {
		$atts = array();
		foreach ( $this->fields() as $key => $f ) {
			if ( ! empty( $f['virtual'] ) ) {
				continue;
			}
			$keys = ! empty( $f['responsive'] ) ? array( $key, $key . '_tablet', $key . '_mobile' ) : array( $key );
			foreach ( $keys as $k ) {
				if ( ! array_key_exists( $k, $s ) ) {
					continue;
				}
				$v = $s[ $k ];
				if ( 'switch' === $f['type'] ) {
					$v = 'yes' === $v ? 'yes' : 'no';
					if ( isset( $f['yesno'] ) && 'bool' === $f['yesno'] ) {
						$v = 'yes' === $v ? '1' : '0';
					}
				} elseif ( is_array( $v ) ) {
					$v = implode( ',', array_filter( array_map( 'strval', $v ), 'strlen' ) );
				}
				$v = trim( (string) $v );
				if ( '' === $v ) {
					continue;
				}
				$atts[ $k ] = $v;
			}
		}
		return $atts;
	}

	/**
	 * Build the shortcode string.
	 *
	 * @param string $tag  Tag.
	 * @param array  $atts Attributes.
	 * @return string
	 */
	protected static function build( $tag, $atts ) {
		$out = '[' . $tag;
		foreach ( $atts as $k => $v ) {
			$out .= ' ' . sanitize_key( $k ) . '="' . str_replace( array( '"', '[', ']' ), array( '&quot;', '&#91;', '&#93;' ), $v ) . '"';
		}
		return $out . ']';
	}

	/**
	 * Placeholder shown in the editor when a widget has nothing to show.
	 *
	 * @param string $msg Message.
	 * @return string
	 */
	protected function placeholder( $msg ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return '';
		}
		return '<div class="mk-ew-empty" style="padding:28px;border:1px dashed #c8cbd0;border-radius:6px;text-align:center;color:#5e6678;font:14px/1.5 system-ui,sans-serif">' . esc_html( $msg ) . '</div>';
	}

	/**
	 * Is this the Elementor editor?
	 *
	 * @return bool
	 */
	protected static function editing() {
		return class_exists( '\Elementor\Plugin' ) && ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() );
	}

	/**
	 * Output.
	 */
	protected function render() {
		$html = do_shortcode( self::build( $this->tag(), $this->atts( $this->get_settings_for_display() ) ) );
		if ( '' === trim( wp_strip_all_tags( $html, true ) ) && false === strpos( $html, '<img' ) && false === strpos( $html, 'data-mk' ) ) {
			$html = $this->placeholder( $this->empty_message() );
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- shortcode output is escaped by its renderer.
	}

	/**
	 * Editor message when the shortcode renders nothing.
	 *
	 * @return string
	 */
	protected function empty_message() {
		return __( 'Nothing to show yet. Check the widget settings.', 'makaazi' );
	}
}
