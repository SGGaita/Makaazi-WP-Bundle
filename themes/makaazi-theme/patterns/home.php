<?php
/**
 * Title: Makaazi home page
 * Slug: makaazi-theme/home
 * Categories: makaazi
 * Post Types: page
 * Description: Hero slider with search, featured properties, homes for sale, browse by location, homes to rent and a call to action.
 *
 * @package MakaaziTheme
 */

$mk_cta_url = home_url( '/contact/' );
if ( function_exists( 'makaazi_owners_on' ) && makaazi_owners_on() && makaazi_dashboard_url() ) {
	$mk_cta_url = is_user_logged_in() ? makaazi_dashboard_url( array( 'tab' => 'submit' ) ) : makaazi_dashboard_url( array( 'mode' => 'register' ) );
}
?>
<!-- wp:shortcode -->
[makaazi_slider slug="home-hero"]
<!-- /wp:shortcode -->

<!-- wp:group {"align":"full","className":"mk-section","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull mk-section"><!-- wp:shortcode -->
[makaazi_properties source="featured" count="4" columns="4" eyebrow="Hand-picked" title="Featured properties" orderby="newest"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"mk-section","backgroundColor":"surface","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull mk-section has-surface-background-color has-background"><!-- wp:shortcode -->
[makaazi_properties source="sale" count="3" columns="3" eyebrow="Buy" title="Homes for sale" use="residential"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"mk-section","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull mk-section"><!-- wp:shortcode -->
[makaazi_locations count="6" columns="3" eyebrow="Neighbourhoods" title="Browse by location"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"mk-section","backgroundColor":"surface","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull mk-section has-surface-background-color has-background"><!-- wp:shortcode -->
[makaazi_properties source="rent" count="8" columns="4" layout="carousel" eyebrow="Rent" title="Homes to rent"]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"mk-cta","backgroundColor":"primary","textColor":"base","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull mk-cta has-base-color has-primary-background-color has-text-color has-background"><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:group {"className":"mk-cta__text","layout":{"type":"default"}} -->
<div class="wp-block-group mk-cta__text"><!-- wp:heading {"textColor":"base"} -->
<h2 class="wp-block-heading has-base-color has-text-color"><?php esc_html_e( 'Own a property? List it with Makaazi.', 'makaazi-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Send us the details and photos. Our team checks every listing before it goes live.', 'makaazi-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"base"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-accent-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url( $mk_cta_url ); ?>"><?php esc_html_e( 'List your property', 'makaazi-theme' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
