<?php
/**
 * Title: Makaazi footer
 * Slug: makaazi-theme/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package MakaaziTheme
 */

$mk_props = home_url( '/properties/' );
?>
<!-- wp:group {"className":"mk-site-footer__in","layout":{"type":"constrained"}} -->
<div class="wp-block-group mk-site-footer__in"><!-- wp:columns {"align":"wide","className":"mk-footer-cols"} -->
<div class="wp-block-columns alignwide mk-footer-cols"><!-- wp:column {"width":"34%"} -->
<div class="wp-block-column" style="flex-basis:34%"><!-- wp:site-logo {"width":168,"className":"is-light","shouldSyncIcon":false} /-->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Homes, offices and land across Kenya, listed and shown by people who know each neighbourhood.', 'makaazi-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"className":"mk-footer-h"} -->
<h2 class="wp-block-heading mk-footer-h"><?php esc_html_e( 'Explore', 'makaazi-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"mk-footer-links"} -->
<ul class="wp-block-list mk-footer-links"><!-- wp:list-item -->
<li><a href="<?php echo esc_url( add_query_arg( 'status', 'sale', $mk_props ) ); ?>"><?php esc_html_e( 'Properties for sale', 'makaazi-theme' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( add_query_arg( 'status', 'rent', $mk_props ) ); ?>"><?php esc_html_e( 'Properties to rent', 'makaazi-theme' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( add_query_arg( 'use', 'commercial', $mk_props ) ); ?>"><?php esc_html_e( 'Commercial space', 'makaazi-theme' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( add_query_arg( 'type', 'land', $mk_props ) ); ?>"><?php esc_html_e( 'Land', 'makaazi-theme' ); ?></a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"className":"mk-footer-h"} -->
<h2 class="wp-block-heading mk-footer-h"><?php esc_html_e( 'Popular areas', 'makaazi-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[makaazi_locations layout="list" count="5"]
<!-- /wp:shortcode --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"className":"mk-footer-h"} -->
<h2 class="wp-block-heading mk-footer-h"><?php esc_html_e( 'Contact', 'makaazi-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Westlands Business Park, Nairobi', 'makaazi-theme' ); ?><br>+254 700 123 456<br>hello@makaazihomes.co.ke</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","className":"mk-footer-base","layout":{"type":"flex","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide mk-footer-base"><!-- wp:paragraph -->
<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> · 
<?php
/* translators: %s: Ascension Dynamics link */
printf( esc_html__( 'Developed by %s', 'makaazi-theme' ), '<a href="https://ascensiondynamics.co/" target="_blank" rel="noopener">Ascension Dynamics</a>' );
?>
</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Prices in Kenya shillings. Map locations are approximate.', 'makaazi-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
