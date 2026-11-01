<?php
/**
 * Shortcode reference data (used by the admin reference page).
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Documentation for every shortcode. Other Makaazi plugins add theirs via the makaazi_shortcode_docs filter.
 */
class Makaazi_Shortcode_Docs {

	/**
	 * All docs.
	 *
	 * @return array
	 */
	public static function all() {
		$docs = array(
			array(
				'tag'      => 'makaazi_properties',
				'group'    => __( 'Listings', 'makaazi' ),
				'desc'     => __( 'A grid, carousel or list of property cards. Use it for Featured, For Sale and For Rent sections.', 'makaazi' ),
				'atts'     => array(
					'source'         => array( 'all', 'all, featured, sale, rent, ids' ),
					'count'          => array( '6', __( 'Number of properties (max 48)', 'makaazi' ) ),
					'columns'        => array( '3', __( 'Per row on desktop, 1–6', 'makaazi' ) ),
					'columns_tablet' => array( '2', __( 'Per row on tablets, 1–4', 'makaazi' ) ),
					'columns_mobile' => array( '1', __( 'Per row on phones, 1–2', 'makaazi' ) ),
					'layout'         => array( 'grid', 'grid, carousel, list' ),
					'orderby'        => array( 'newest', 'newest, price_asc, price_desc, featured, largest, random' ),
					'status'         => array( '', 'sale, rent' ),
					'location'       => array( '', __( 'Location slug, e.g. westlands (includes sub-areas)', 'makaazi' ) ),
					'type'           => array( '', __( 'Type slug(s), comma-separated', 'makaazi' ) ),
					'category'       => array( '', __( 'Category slug(s), comma-separated', 'makaazi' ) ),
					'use'            => array( '', 'residential, commercial' ),
					'ids'            => array( '', __( 'Property IDs, comma-separated (with source="ids")', 'makaazi' ) ),
					'agent'          => array( '', __( 'Agent ID or slug: only that agent\'s listings', 'makaazi' ) ),
					'pagination'     => array( 'none', 'none, numbered, loadmore' ),
					'eyebrow'        => array( '', __( 'Small label above the title', 'makaazi' ) ),
					'title'          => array( '', __( 'Section title', 'makaazi' ) ),
					'subtitle'       => array( '', __( 'Line under the title', 'makaazi' ) ),
					'view_all'       => array( 'yes', __( 'yes or no: link to the matching Properties page', 'makaazi' ) ),
					'view_all_text'  => array( '', __( 'Link text, default "View all"', 'makaazi' ) ),
					'button_text'    => array( '', __( 'Card button text; default from Settings', 'makaazi' ) ),
					'image_ratio'    => array( '', '4:3, 3:2, 16:9, 1:1' ),
					'show'           => array( '', __( 'Only these card parts: badges, category, use, type, location, beds, baths, parking, area', 'makaazi' ) ),
					'hide'           => array( '', __( 'Hide these card parts', 'makaazi' ) ),
					'include_closed' => array( 'no', __( 'yes to include sold and let properties', 'makaazi' ) ),
					'class'          => array( '', __( 'Extra CSS class', 'makaazi' ) ),
				),
				'examples' => array(
					'[makaazi_properties source="featured" count="4" columns="4" eyebrow="Hand-picked" title="Featured properties"]',
					'[makaazi_properties source="rent" location="westlands" layout="carousel" count="8" button_text="See details"]',
					'[makaazi_properties source="sale" count="9" columns="3" pagination="loadmore" hide="category,use"]',
				),
			),
			array(
				'tag'      => 'makaazi_property_listing',
				'group'    => __( 'Listings', 'makaazi' ),
				'desc'     => __( 'The full Properties page: filters, active filter chips, sort, grid/list/map views and pagination. Filters update without reloading and the address can be shared.', 'makaazi' ),
				'atts'     => array(
					'per_page'     => array( '12', __( 'Per page (from Settings)', 'makaazi' ) ),
					'columns'      => array( '3', '1–4' ),
					'filters'      => array( 'sidebar', 'sidebar, top, none' ),
					'views'        => array( 'grid,list,map', __( 'Which view buttons to show', 'makaazi' ) ),
					'default_view' => array( 'grid', 'grid, list, map' ),
					'status'       => array( '', __( 'Fix the listing to sale or rent', 'makaazi' ) ),
					'location'     => array( '', __( 'Fix to a location slug', 'makaazi' ) ),
					'type'         => array( '', __( 'Fix to a type slug', 'makaazi' ) ),
					'category'     => array( '', __( 'Fix to a category slug', 'makaazi' ) ),
					'use'          => array( '', __( 'Fix to residential or commercial', 'makaazi' ) ),
					'title'        => array( '', __( 'Override the "12 properties…" heading', 'makaazi' ) ),
					'button_text'  => array( '', __( 'Card button text', 'makaazi' ) ),
				),
				'examples' => array(
					'[makaazi_property_listing]',
					'[makaazi_property_listing status="rent" filters="top" views="grid,map"]',
				),
			),
			array(
				'tag'      => 'makaazi_search',
				'group'    => __( 'Search', 'makaazi' ),
				'desc'     => __( 'A Buy/Rent search bar that opens the Properties page with the chosen filters. The hero slider includes the same bar.', 'makaazi' ),
				'atts'     => array(
					'fields'         => array( 'location,type,price', __( 'Order of fields: location, type, price, beds, keyword', 'makaazi' ) ),
					'style'          => array( 'solid', 'solid, glass, minimal' ),
					'button_text'    => array( 'Search', '' ),
					'buy_label'      => array( 'Buy', '' ),
					'rent_label'     => array( 'Rent', '' ),
					'default_status' => array( 'sale', 'sale, rent' ),
					'show_tabs'      => array( 'yes', __( 'no to hide the Buy/Rent tabs', 'makaazi' ) ),
					'location_label' => array( 'Location', '' ),
					'type_label'     => array( 'Property type', '' ),
				),
				'examples' => array(
					'[makaazi_search]',
					'[makaazi_search fields="location,type,beds,price" default_status="rent" button_text="Find a home"]',
				),
			),
			array(
				'tag'      => 'makaazi_locations',
				'group'    => __( 'Search', 'makaazi' ),
				'desc'     => __( '"Browse by location" tiles (or a plain list) linking to filtered results. Set tile images under Properties › Locations.', 'makaazi' ),
				'atts'     => array(
					'locations' => array( '', __( 'Slugs in order; empty = most popular areas', 'makaazi' ) ),
					'parent'    => array( '', __( 'Only areas inside this county slug', 'makaazi' ) ),
					'count'     => array( '6', '' ),
					'columns'   => array( '3', '1–6' ),
					'layout'    => array( 'tiles', 'tiles, list' ),
					'status'    => array( '', __( 'Link to sale or rent results', 'makaazi' ) ),
					'eyebrow'   => array( '', '' ),
					'title'     => array( '', '' ),
				),
				'examples' => array( '[makaazi_locations count="6" eyebrow="Neighbourhoods" title="Browse by location"]' ),
			),
			array(
				'tag'      => 'makaazi_unit_toggle',
				'group'    => __( 'Search', 'makaazi' ),
				'desc'     => __( 'm² | ft² switch. Updates every area on the page and remembers the choice.', 'makaazi' ),
				'atts'     => array(),
				'examples' => array( '[makaazi_unit_toggle]' ),
			),
			array(
				'tag'      => 'makaazi_property',
				'group'    => __( 'Property page', 'makaazi' ),
				'desc'     => __( 'The whole property page in one shortcode, for page builders and classic themes. The Makaazi theme uses the separate parts below instead.', 'makaazi' ),
				'atts'     => array( 'id' => array( '', __( 'Property ID; defaults to the current property', 'makaazi' ) ) ),
				'examples' => array( '[makaazi_property]', '[makaazi_property id="42"]' ),
			),
		);
		$parts = array(
			'makaazi_property_header'      => __( 'Breadcrumb, badges, title, location, reference, price and share button.', 'makaazi' ),
			'makaazi_property_gallery'     => __( 'Photo mosaic (swipeable strip on phones). Every photo opens the lightbox.', 'makaazi' ),
			'makaazi_property_facts'       => __( 'Bedrooms, bathrooms, parking, area, type and use.', 'makaazi' ),
			'makaazi_property_description' => __( 'The description, for page builders. Block templates use the Content block.', 'makaazi' ),
			'makaazi_property_details'     => __( 'Details table with custom attributes.', 'makaazi' ),
			'makaazi_property_features'    => __( 'Features with icons.', 'makaazi' ),
			'makaazi_property_map'         => __( 'Approximate location map (OpenStreetMap).', 'makaazi' ),
			'makaazi_property_contact'     => __( 'Contact card, WhatsApp, call and enquiry form, plus the phone action bar.', 'makaazi' ),
		);
		foreach ( $parts as $tag => $desc ) {
			$atts = array( 'id' => array( '', __( 'Property ID; defaults to the current property', 'makaazi' ) ) );
			if ( in_array( $tag, array( 'makaazi_property_details', 'makaazi_property_features', 'makaazi_property_map', 'makaazi_property_description' ), true ) ) {
				$atts['title'] = array( '', __( 'Section heading', 'makaazi' ) );
			}
			$docs[] = array(
				'tag'      => $tag,
				'group'    => __( 'Property page', 'makaazi' ),
				'desc'     => $desc,
				'atts'     => $atts,
				'examples' => array( '[' . $tag . ']' ),
			);
		}
		$docs[] = array(
			'tag'      => 'makaazi_related_properties',
			'group'    => __( 'Property page', 'makaazi' ),
			'desc'     => __( 'Similar properties scored by area, type, category, status and price.', 'makaazi' ),
			'atts'     => array(
				'id'      => array( '', __( 'Property ID', 'makaazi' ) ),
				'count'   => array( '8', '' ),
				'layout'  => array( 'carousel', 'carousel, grid, list' ),
				'columns' => array( '4', '' ),
				'eyebrow' => array( 'You may also like', '' ),
				'title'   => array( 'Similar properties', '' ),
			),
			'examples' => array( '[makaazi_related_properties count="6" layout="grid" columns="3"]' ),
		);
		return apply_filters( 'makaazi_shortcode_docs', $docs );
	}
}
