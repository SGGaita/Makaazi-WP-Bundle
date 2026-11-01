<?php
/**
 * Title: Makaazi header
 * Slug: makaazi-theme/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * @package MakaaziTheme
 */

?>
<!-- wp:group {"className":"mk-site-header__in","layout":{"type":"constrained"}} -->
<div class="wp-block-group mk-site-header__in"><!-- wp:group {"align":"wide","className":"mk-site-header__bar","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide mk-site-header__bar"><!-- wp:group {"className":"mk-site-header__brand","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group mk-site-header__brand"><!-- wp:site-logo {"width":168,"shouldSyncIcon":false} /-->

<!-- wp:navigation {"overlayMenu":"mobile","className":"mk-nav","layout":{"type":"flex","justifyContent":"left"}} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"mk-site-header__actions","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group mk-site-header__actions"><!-- wp:shortcode -->
[makaazi_unit_toggle]
<!-- /wp:shortcode -->

<!-- wp:buttons {"className":"mk-header-cta"} -->
<div class="wp-block-buttons mk-header-cta"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/properties/' ) ); ?>"><?php esc_html_e( 'Browse properties', 'makaazi-theme' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
