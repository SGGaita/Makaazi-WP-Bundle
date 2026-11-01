<?php
/**
 * SEO: Schema.org data, meta descriptions, share previews and robots rules for listings.
 *
 * Works on its own, and hands its data to Yoast SEO or Rank Math when one of them is active
 * so nothing is printed twice.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Search engine and social sharing support.
 */
class Makaazi_SEO {

	/**
	 * Hook in.
	 */
	public static function init() {
		add_action( 'wp', array( __CLASS__, 'setup' ) );
		add_filter( 'document_title_parts', array( __CLASS__, 'archive_title' ) );

		// Yoast SEO.
		add_filter( 'wpseo_schema_graph', array( __CLASS__, 'yoast_graph' ), 20, 2 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'fill_description' ) );
		add_filter( 'wpseo_opengraph_desc', array( __CLASS__, 'fill_description' ) );
		add_filter( 'wpseo_twitter_description', array( __CLASS__, 'fill_description' ) );
		add_action( 'wpseo_add_opengraph_images', array( __CLASS__, 'yoast_og_image' ) );
		add_filter( 'wpseo_robots', array( __CLASS__, 'robots_string' ) );
		add_filter( 'wpseo_sitemap_urlimages', array( __CLASS__, 'sitemap_images' ), 10, 2 );
		add_filter( 'wpseo_sitemap_exclude_post_type', array( __CLASS__, 'sitemap_exclude' ), 10, 2 );
		add_action( 'wpseo_register_extra_replacements', array( __CLASS__, 'yoast_variables' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'yoast_analysis' ), 20 );

		// Rank Math.
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'rank_math_json' ), 20, 2 );
		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'fill_description' ) );
		add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'robots_array' ) );
		add_filter( 'rank_math/sitemap/urlimages', array( __CLASS__, 'sitemap_images' ), 10, 2 );

		// WordPress core sitemaps.
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'core_sitemap_types' ) );
	}

	/**
	 * Which SEO plugin handles titles, descriptions and share tags, if any.
	 *
	 * @return string yoast|rankmath|seopress|aioseo|''
	 */
	public static function plugin() {
		$p = '';
		if ( defined( 'WPSEO_VERSION' ) ) {
			$p = 'yoast';
		} elseif ( class_exists( 'RankMath' ) ) {
			$p = 'rankmath';
		} elseif ( defined( 'SEOPRESS_VERSION' ) ) {
			$p = 'seopress';
		} elseif ( defined( 'AIOSEO_VERSION' ) ) {
			$p = 'aioseo';
		}
		/**
		 * Filter the detected SEO plugin. Return '' to make Makaazi print its own tags.
		 *
		 * @param string $p Plugin key.
		 */
		return (string) apply_filters( 'makaazi_seo_plugin', $p );
	}

	/**
	 * Print our own tags when no SEO plugin is active.
	 */
	public static function setup() {
		if ( is_admin() ) {
			return;
		}
		$p = self::plugin();
		if ( '' === $p ) {
			add_action( 'wp_head', array( __CLASS__, 'head' ), 2 );
		}
		if ( ! in_array( $p, array( 'yoast', 'rankmath' ), true ) ) {
			// SEOPress and AIOSEO keep their own tags; we only add the listing schema.
			add_action( 'wp_head', array( __CLASS__, 'print_schema' ), 30 );
		}
		if ( '' === $p || 'seopress' === $p || 'aioseo' === $p ) {
			add_filter( 'wp_robots', array( __CLASS__, 'core_robots' ) );
		}
	}

	/* ---------------------------------------------------------------------
	 * Descriptions
	 * ------------------------------------------------------------------- */

	/**
	 * Automatic description for a listing: "3-bed apartment to rent in Westlands, Nairobi. KES 180,000/month. …".
	 *
	 * @param int $id Property ID.
	 * @return string
	 */
	public static function description( $id ) {
		$type   = makaazi_term( $id, 'property_type' );
		$status = makaazi_status( $id );
		$beds   = (int) makaazi_meta( $id, 'bedrooms' );
		$what   = $type ? strtolower( $type->name ) : __( 'property', 'makaazi' );
		/* translators: 1: bedrooms, 2: property type */
		$head = $beds ? sprintf( __( '%1$d-bedroom %2$s', 'makaazi' ), $beds, $what ) : ucfirst( $what );
		$verb = array(
			'sale' => __( 'for sale', 'makaazi' ),
			'rent' => __( 'to rent', 'makaazi' ),
			'stay' => __( 'for short stays', 'makaazi' ),
		);
		$s    = $head . ' ' . ( isset( $verb[ $status ] ) ? $verb[ $status ] : '' );
		$loc  = makaazi_location_text( $id );
		if ( $loc ) {
			/* translators: %s: location name */
			$s .= ' ' . sprintf( __( 'in %s', 'makaazi' ), $loc );
		}
		$s    .= '. ' . wp_strip_all_tags( makaazi_price( $id ) ) . '.';
		$excerpt = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_strip_all_tags( get_post_field( 'post_content', $id ) );
		$s    .= ' ' . $excerpt;
		$s     = trim( preg_replace( '/\s+/', ' ', $s ) );
		return mb_strlen( $s ) > 158 ? rtrim( mb_substr( $s, 0, 155 ), " ,.;:" ) . '…' : $s;
	}

	/**
	 * Use our description when the SEO plugin has none.
	 *
	 * @param string $desc Description.
	 * @return string
	 */
	public static function fill_description( $desc ) {
		if ( '' === trim( (string) $desc ) && is_singular( 'property' ) ) {
			return self::description( get_queried_object_id() );
		}
		if ( '' === trim( (string) $desc ) && is_singular( 'mk_agent' ) ) {
			return wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', get_queried_object_id() ) ), 28, '…' );
		}
		return $desc;
	}

	/**
	 * Cover photo URL for share previews.
	 *
	 * @param int $id Post ID.
	 * @return string
	 */
	public static function image( $id ) {
		$ids = 'property' === get_post_type( $id ) ? makaazi_gallery_ids( $id ) : array( get_post_thumbnail_id( $id ) );
		$img = $ids ? wp_get_attachment_image_src( (int) $ids[0], 'large' ) : false;
		return $img ? $img[0] : '';
	}

	/* ---------------------------------------------------------------------
	 * Own tags (no SEO plugin)
	 * ------------------------------------------------------------------- */

	/**
	 * Description, Open Graph and Twitter tags.
	 */
	public static function head() {
		$id    = get_queried_object_id();
		$title = wp_get_document_title();
		$desc  = '';
		$url   = '';
		$img   = '';
		$type  = 'website';
		if ( is_singular( array( 'property', 'mk_agent' ) ) ) {
			$desc = self::fill_description( '' );
			$url  = get_permalink( $id );
			$img  = self::image( $id );
			$type = 'property' === get_post_type( $id ) ? 'website' : 'profile';
		} elseif ( is_post_type_archive( 'property' ) || is_tax( get_object_taxonomies( 'property' ) ) ) {
			$desc = is_tax() ? wp_strip_all_tags( term_description() ) : '';
			$desc = $desc ? $desc : get_bloginfo( 'description' );
			$url  = is_tax() ? get_term_link( get_queried_object() ) : makaazi_archive_url();
		} elseif ( is_front_page() ) {
			$desc = get_bloginfo( 'description' );
			$url  = home_url( '/' );
		} else {
			return;
		}
		if ( is_wp_error( $url ) ) {
			$url = '';
		}
		$tags = array(
			array( 'name', 'description', $desc ),
			array( 'property', 'og:type', $type ),
			array( 'property', 'og:site_name', get_bloginfo( 'name' ) ),
			array( 'property', 'og:locale', get_locale() ),
			array( 'property', 'og:title', $title ),
			array( 'property', 'og:description', $desc ),
			array( 'property', 'og:url', $url ),
			array( 'property', 'og:image', $img ),
			array( 'name', 'twitter:card', $img ? 'summary_large_image' : 'summary' ),
			array( 'name', 'twitter:title', $title ),
			array( 'name', 'twitter:description', $desc ),
			array( 'name', 'twitter:image', $img ),
		);
		echo "\n<!-- Makaazi SEO -->\n";
		foreach ( $tags as $t ) {
			if ( '' !== (string) $t[2] ) {
				printf( '<meta %s="%s" content="%s">' . "\n", esc_attr( $t[0] ), esc_attr( $t[1] ), esc_attr( $t[2] ) );
			}
		}
		if ( ( is_post_type_archive( 'property' ) || is_tax( get_object_taxonomies( 'property' ) ) ) && $url && ! self::filtered() ) {
			// Keep a single clean filter such as ?status=rent: it is its own landing page.
			foreach ( array( 'status', 'location', 'type', 'use' ) as $k ) {
				if ( isset( $_GET[ $k ] ) && is_string( $_GET[ $k ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$url = add_query_arg( $k, sanitize_title( wp_unslash( $_GET[ $k ] ) ), $url ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}
			$pg = isset( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
			if ( $pg > 1 ) {
				$url = add_query_arg( 'pg', $pg, $url );
			}
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		}
	}

	/**
	 * Print JSON-LD.
	 */
	public static function print_schema() {
		$nodes = self::nodes();
		if ( ! $nodes ) {
			return;
		}
		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => $nodes,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/* ---------------------------------------------------------------------
	 * Schema.org
	 * ------------------------------------------------------------------- */

	/**
	 * Schema nodes for the current page.
	 *
	 * @param bool $breadcrumbs Include a BreadcrumbList (off when an SEO plugin adds its own).
	 * @return array[]
	 */
	public static function nodes( $breadcrumbs = true ) {
		$nodes = array();
		if ( is_singular( 'property' ) ) {
			$id      = get_queried_object_id();
			$nodes[] = self::listing( $id );
			if ( $breadcrumbs ) {
				$nodes[] = self::breadcrumbs( $id );
			}
		} elseif ( is_singular( 'mk_agent' ) && makaazi_agents_on() ) {
			$nodes[] = self::agent( get_queried_object_id() );
		} elseif ( is_post_type_archive( 'property' ) || is_tax( get_object_taxonomies( 'property' ) ) ) {
			$list = self::item_list();
			if ( $list ) {
				$nodes[] = $list;
			}
		}
		/**
		 * Filter the Schema.org nodes Makaazi adds to the page.
		 *
		 * @param array[] $nodes Nodes.
		 */
		return array_values( array_filter( (array) apply_filters( 'makaazi_schema', $nodes ) ) );
	}

	/**
	 * RealEstateListing for a property.
	 *
	 * @param int $id Property ID.
	 * @return array
	 */
	public static function listing( $id ) {
		$url    = get_permalink( $id );
		$status = makaazi_status( $id );
		$avail  = makaazi_meta( $id, 'availability', 'available' );
		$type   = makaazi_term( $id, 'property_type' );
		$images = array();
		foreach ( array_slice( makaazi_gallery_ids( $id ), 0, 10 ) as $att ) {
			$src = wp_get_attachment_image_src( (int) $att, 'large' );
			if ( $src ) {
				$images[] = $src[0];
			}
		}
		// The place itself. Coordinates are only given when the exact pin is public.
		$place_type = 'Place';
		if ( $type ) {
			$map        = array(
				'apartment'  => 'Apartment',
				'maisonette' => 'House',
				'townhouse'  => 'House',
				'villa'      => 'SingleFamilyResidence',
				'bungalow'   => 'SingleFamilyResidence',
				'land'       => 'Landform',
			);
			$place_type = isset( $map[ $type->slug ] ) ? $map[ $type->slug ] : 'Place';
		}
		if ( 'stay' === $status && 'Place' === $place_type ) {
			$place_type = 'Accommodation';
		}
		$place = array(
			'@type'   => $place_type,
			'@id'     => $url . '#place',
			'name'    => get_the_title( $id ),
			'address' => self::address( $id ),
		);
		$beds  = makaazi_meta( $id, 'bedrooms' );
		$baths = makaazi_meta( $id, 'bathrooms' );
		$area  = makaazi_meta( $id, 'area_sqm' );
		if ( '' !== $beds && 'Landform' !== $place_type ) {
			$place['numberOfBedrooms'] = (int) $beds;
			$place['numberOfRooms']    = (int) $beds;
		}
		if ( '' !== $baths && 'Landform' !== $place_type ) {
			$place['numberOfBathroomsTotal'] = (float) $baths;
		}
		if ( '' !== $area && (float) $area > 0 ) {
			$place['floorSize'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => (float) $area,
				'unitCode' => 'MTK',
			);
		}
		$pub = makaazi_public_location( $id );
		if ( $pub && ! empty( $pub[3] ) ) {
			$place['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => $pub[0],
				'longitude' => $pub[1],
			);
		}
		$features = get_the_terms( $id, 'property_feature' );
		if ( $features && ! is_wp_error( $features ) ) {
			$place['amenityFeature'] = array_map(
				function ( $t ) {
					return array(
						'@type' => 'LocationFeatureSpecification',
						'name'  => $t->name,
						'value' => true,
					);
				},
				array_values( $features )
			);
		}

		$node = array(
			'@type'       => 'RealEstateListing',
			'@id'         => $url . '#listing',
			'url'         => $url,
			'name'        => get_the_title( $id ),
			'description' => self::description( $id ),
			'datePosted'  => get_post_time( 'c', true, $id ),
			'dateModified' => get_post_modified_time( 'c', true, $id ),
			'about'       => $place,
		);
		if ( $images ) {
			$node['image'] = $images;
		}
		$ref = makaazi_meta( $id, 'ref' );
		if ( $ref ) {
			$node['identifier'] = $ref;
		}
		$price = makaazi_meta( $id, 'price' );
		if ( '' !== $price && ! makaazi_meta( $id, 'price_on_request' ) ) {
			$offer = array(
				'@type'           => 'Offer',
				'price'           => (float) $price,
				'priceCurrency'   => makaazi_settings( 'currency' ),
				'businessFunction' => 'sale' === $status ? 'http://purl.org/goodrelations/v1#Sell' : 'http://purl.org/goodrelations/v1#LeaseOut',
				'availability'    => in_array( $avail, array( 'sold', 'let' ), true ) ? 'https://schema.org/SoldOut' : ( 'under_offer' === $avail ? 'https://schema.org/LimitedAvailability' : 'https://schema.org/InStock' ),
				'url'             => $url,
			);
			$period = makaazi_meta( $id, 'price_period', 'total' );
			$units  = array(
				'month' => 'MON',
				'year'  => 'ANN',
				'night' => 'DAY',
			);
			if ( isset( $units[ $period ] ) ) {
				$offer['priceSpecification'] = array(
					'@type'         => 'UnitPriceSpecification',
					'price'         => (float) $price,
					'priceCurrency' => makaazi_settings( 'currency' ),
					'unitCode'      => $units[ $period ],
				);
			}
			$aid = Makaazi_Agents::of_property( $id );
			if ( $aid && makaazi_agents_on() ) {
				$offer['offeredBy'] = array(
					'@type' => 'RealEstateAgent',
					'@id'   => get_permalink( $aid ) . '#agent',
					'name'  => get_the_title( $aid ),
					'url'   => get_permalink( $aid ),
				);
			} else {
				$offer['offeredBy'] = array(
					'@type' => 'RealEstateAgent',
					'name'  => makaazi_settings( 'contact_name' ) ? makaazi_settings( 'contact_name' ) : get_bloginfo( 'name' ),
					'url'   => home_url( '/' ),
				);
			}
			$node['offers'] = $offer;
		}
		/**
		 * Filter the RealEstateListing node for a property.
		 *
		 * @param array $node Node.
		 * @param int   $id   Property ID.
		 */
		return apply_filters( 'makaazi_schema_listing', $node, $id );
	}

	/**
	 * PostalAddress from the location terms.
	 *
	 * @param int $id Property ID.
	 * @return array
	 */
	public static function address( $id ) {
		$a    = array(
			'@type'          => 'PostalAddress',
			'addressCountry' => (string) apply_filters( 'makaazi_schema_country', 'KE' ),
		);
		$term = makaazi_location_term( $id );
		if ( $term ) {
			$a['addressLocality'] = $term->name;
			if ( $term->parent ) {
				$parent = get_term( $term->parent, 'property_location' );
				if ( $parent && ! is_wp_error( $parent ) ) {
					$a['addressRegion'] = $parent->name;
				}
			}
		}
		$label = makaazi_meta( $id, 'location_label' );
		if ( $label ) {
			$a['streetAddress'] = $label;
		}
		return $a;
	}

	/**
	 * RealEstateAgent for an agent page.
	 *
	 * @param int $aid Agent ID.
	 * @return array
	 */
	public static function agent( $aid ) {
		$d    = Makaazi_Agents::data( $aid );
		$node = array(
			'@type' => 'RealEstateAgent',
			'@id'   => get_permalink( $aid ) . '#agent',
			'name'  => get_the_title( $aid ),
			'url'   => get_permalink( $aid ),
		);
		$img = self::image( $aid );
		if ( $img ) {
			$node['image'] = $img;
		}
		if ( ! empty( $d['phone'] ) ) {
			$node['telephone'] = $d['phone'];
		}
		if ( ! empty( $d['email'] ) ) {
			$node['email'] = $d['email'];
		}
		if ( ! empty( $d['role'] ) ) {
			$node['jobTitle'] = $d['role'];
		}
		$same = array_values( array_filter( array( isset( $d['facebook'] ) ? $d['facebook'] : '', isset( $d['instagram'] ) ? $d['instagram'] : '', isset( $d['linkedin'] ) ? $d['linkedin'] : '' ) ) );
		if ( $same ) {
			$node['sameAs'] = $same;
		}
		return $node;
	}

	/**
	 * BreadcrumbList: Home › Properties › Area › Listing.
	 *
	 * @param int $id Property ID.
	 * @return array
	 */
	public static function breadcrumbs( $id ) {
		$items = array(
			array( get_bloginfo( 'name' ), home_url( '/' ) ),
			array( __( 'Properties', 'makaazi' ), makaazi_archive_url() ),
		);
		$term  = makaazi_location_term( $id );
		if ( $term ) {
			$items[] = array( $term->name, makaazi_archive_url( array( 'location' => $term->slug ) ) );
		}
		$items[] = array( get_the_title( $id ), get_permalink( $id ) );
		$list    = array();
		foreach ( $items as $i => $it ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $it[0],
				'item'     => $it[1],
			);
		}
		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => get_permalink( $id ) . '#breadcrumb',
			'itemListElement' => $list,
		);
	}

	/**
	 * ItemList of the listings on an archive page.
	 *
	 * @return array|null
	 */
	public static function item_list() {
		$preset = array();
		$obj    = get_queried_object();
		if ( is_tax() && $obj instanceof WP_Term ) {
			$map = array(
				'property_status'   => 'status',
				'property_location' => 'location',
				'property_type'     => 'type',
				'property_use'      => 'use',
			);
			if ( isset( $map[ $obj->taxonomy ] ) ) {
				$preset[ $map[ $obj->taxonomy ] ] = $obj->slug;
			}
		}
		$f   = Makaazi_Query::parse( wp_unslash( $_GET ), $preset ); // phpcs:ignore WordPress.Security.NonceVerification
		$ids = get_posts( Makaazi_Query::args( $f, (int) makaazi_settings( 'per_page' ), array( 'fields' => 'ids', 'no_found_rows' => true ) ) );
		$items = array();
		foreach ( $ids as $i => $pid ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'url'      => get_permalink( $pid ),
			);
		}
		return $items ? array(
			'@type'           => 'ItemList',
			'itemListElement' => $items,
		) : null;
	}

	/* ---------------------------------------------------------------------
	 * Robots and titles
	 * ------------------------------------------------------------------- */

	/**
	 * Is this a filtered search (query string filters beyond paging)?
	 *
	 * @return bool
	 */
	public static function filtered() {
		if ( ! ( is_post_type_archive( 'property' ) || is_tax( get_object_taxonomies( 'property' ) ) ) ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		$keys = array_diff( array_keys( $_GET ), array( 'paged', 'pg', 'view', 'utm_source', 'utm_medium', 'utm_campaign' ) );
		// One clean filter (e.g. ?status=rent) is a useful landing page; combinations are not.
		$keys = array_values( $keys );
		if ( 1 === count( $keys ) && in_array( $keys[0], array( 'status', 'location', 'type', 'use' ), true ) && ! is_array( $_GET[ $keys[0] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}
		return (bool) $keys;
	}

	/**
	 * Core robots: noindex filtered search combinations.
	 *
	 * @param array $robots Directives.
	 * @return array
	 */
	public static function core_robots( $robots ) {
		if ( self::filtered() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}

	/**
	 * Yoast robots string.
	 *
	 * @param string $robots Robots.
	 * @return string
	 */
	public static function robots_string( $robots ) {
		return self::filtered() ? 'noindex, follow' : $robots;
	}

	/**
	 * Rank Math robots array.
	 *
	 * @param array $robots Robots.
	 * @return array
	 */
	public static function robots_array( $robots ) {
		if ( self::filtered() ) {
			$robots['index']  = 'noindex';
			$robots['follow'] = 'follow';
		}
		return $robots;
	}

	/**
	 * "Apartments to rent in Westlands" titles for the Properties page with one filter.
	 *
	 * @param array $parts Title parts.
	 * @return array
	 */
	public static function archive_title( $parts ) {
		if ( ! is_post_type_archive( 'property' ) || '' !== self::plugin() ) {
			return $parts;
		}
		$f     = Makaazi_Query::parse( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
		$title = Makaazi_Render::results_title( 0, $f );
		$title = trim( preg_replace( '/^\S+\s+\S+/u', '', $title ) ); // Drop "0 properties".
		$parts['title'] = $title ? ucfirst( __( 'Properties', 'makaazi' ) . ' ' . $title ) : __( 'Properties', 'makaazi' );
		return $parts;
	}

	/* ---------------------------------------------------------------------
	 * Yoast SEO
	 * ------------------------------------------------------------------- */

	/**
	 * Add our nodes to Yoast's schema graph and link the listing to Yoast's WebPage.
	 *
	 * @param array $graph   Graph.
	 * @param mixed $context Yoast meta tags context.
	 * @return array
	 */
	public static function yoast_graph( $graph, $context = null ) {
		$nodes = self::nodes( false );
		if ( ! $nodes ) {
			return $graph;
		}
		foreach ( $nodes as $n ) {
			if ( 'RealEstateListing' === $n['@type'] ) {
				foreach ( $graph as $i => $g ) {
					$types = isset( $g['@type'] ) ? (array) $g['@type'] : array();
					if ( in_array( 'WebPage', $types, true ) && isset( $g['@id'] ) ) {
						$n['mainEntityOfPage'] = array( '@id' => $g['@id'] );
						$graph[ $i ]['mainEntity'] = array( '@id' => $n['@id'] );
					}
				}
			}
			$graph[] = $n;
		}
		return $graph;
	}

	/**
	 * Share image for listings without a featured image set by Yoast.
	 *
	 * @param object $images Yoast image container.
	 */
	public static function yoast_og_image( $images ) {
		if ( ! is_singular( 'property' ) || ! is_object( $images ) || ! method_exists( $images, 'add_image_by_url' ) ) {
			return;
		}
		$url = self::image( get_queried_object_id() );
		if ( $url ) {
			$images->add_image_by_url( $url );
		}
	}

	/**
	 * Gallery photos in XML sitemaps (Yoast and Rank Math).
	 *
	 * @param array $images Images.
	 * @param int   $post_id Post ID.
	 * @return array
	 */
	public static function sitemap_images( $images, $post_id ) {
		if ( 'property' !== get_post_type( $post_id ) ) {
			return $images;
		}
		$have = wp_list_pluck( (array) $images, 'src' );
		foreach ( array_slice( makaazi_gallery_ids( $post_id ), 0, 20 ) as $att ) {
			$src = wp_get_attachment_url( (int) $att );
			if ( $src && ! in_array( $src, $have, true ) ) {
				$images[] = array(
					'src'   => $src,
					'title' => get_the_title( $post_id ),
					'alt'   => get_the_title( $post_id ),
				);
			}
		}
		return $images;
	}

	/**
	 * Keep agents out of the Yoast sitemap while Agents is switched off; never list enquiries or sliders.
	 *
	 * @param bool   $exclude Exclude.
	 * @param string $type    Post type.
	 * @return bool
	 */
	public static function sitemap_exclude( $exclude, $type ) {
		if ( in_array( $type, array( 'mk_enquiry', 'mk_slider' ), true ) ) {
			return true;
		}
		if ( 'mk_agent' === $type && ! makaazi_agents_on() ) {
			return true;
		}
		return $exclude;
	}

	/**
	 * Same for WordPress core sitemaps.
	 *
	 * @param array $types Post types.
	 * @return array
	 */
	public static function core_sitemap_types( $types ) {
		unset( $types['mk_enquiry'], $types['mk_slider'] );
		if ( ! makaazi_agents_on() ) {
			unset( $types['mk_agent'] );
		}
		return $types;
	}

	/**
	 * Snippet variables for Yoast titles and descriptions, e.g. %%mk_price%%.
	 */
	public static function yoast_variables() {
		if ( ! function_exists( 'wpseo_register_var_replacement' ) ) {
			return;
		}
		$vars = array(
			'mk_price'    => array( __( 'Property price, e.g. KES 180,000 /month', 'makaazi' ), function ( $id ) {
				return wp_strip_all_tags( makaazi_price( $id ) );
			} ),
			'mk_location' => array( __( 'Property location, e.g. Westlands, Nairobi', 'makaazi' ), 'makaazi_location_text' ),
			'mk_status'   => array( __( 'For Sale, For Rent or Short Stay', 'makaazi' ), function ( $id ) {
				return makaazi_status_label( makaazi_status( $id ) );
			} ),
			'mk_type'     => array( __( 'Property type', 'makaazi' ), function ( $id ) {
				$t = makaazi_term( $id, 'property_type' );
				return $t ? $t->name : '';
			} ),
			'mk_beds'     => array( __( 'Bedrooms', 'makaazi' ), function ( $id ) {
				return (string) makaazi_meta( $id, 'bedrooms' );
			} ),
			'mk_ref'      => array( __( 'Property reference', 'makaazi' ), function ( $id ) {
				return (string) makaazi_meta( $id, 'ref' );
			} ),
		);
		foreach ( $vars as $name => $v ) {
			$cb = $v[1];
			wpseo_register_var_replacement(
				'%%' . $name . '%%',
				function ( $var, $args ) use ( $cb ) {
					unset( $var );
					$id = is_object( $args ) && isset( $args->ID ) ? (int) $args->ID : get_queried_object_id();
					return 'property' === get_post_type( $id ) ? (string) call_user_func( $cb, $id ) : '';
				},
				'advanced',
				$v[0]
			);
		}
	}

	/**
	 * Let Yoast's content analysis see the Property Details (price, specs, location, features).
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function yoast_analysis( $hook ) {
		if ( ! defined( 'WPSEO_VERSION' ) || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'property' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_script( 'makaazi-yoast', MAKAAZI_URL . 'assets/js/yoast.js', array(), MAKAAZI_VERSION . '.' . (int) filemtime( MAKAAZI_DIR . 'assets/js/yoast.js' ), true );
	}

	/* ---------------------------------------------------------------------
	 * Rank Math
	 * ------------------------------------------------------------------- */

	/**
	 * Add our nodes to Rank Math's JSON-LD.
	 *
	 * @param array $data   Data.
	 * @param mixed $jsonld Rank Math JsonLD object.
	 * @return array
	 */
	public static function rank_math_json( $data, $jsonld = null ) {
		unset( $jsonld );
		foreach ( self::nodes( false ) as $i => $n ) {
			$data[ 'makaazi_' . $i ] = $n;
		}
		return $data;
	}
}
