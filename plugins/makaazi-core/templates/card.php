<?php
/**
 * Property card.
 *
 * Override by copying to yourtheme/makaazi/card.php.
 *
 * @package Makaazi
 *
 * @var array $card Prepared card data (see Makaazi_Render::card_data()).
 */

defined( 'ABSPATH' ) || exit;

$c = $card;
?>
<article class="mk-card<?php echo 'list' === $c['layout'] ? ' mk-card--list' : ''; ?><?php echo $c['closed'] ? ' is-closed' : ''; ?>" data-id="<?php echo (int) $c['id']; ?>">
	<div class="mk-card__media" style="--mk-ratio:<?php echo esc_attr( $c['ratio'] ); ?>">
		<?php if ( count( $c['images'] ) > 1 ) : ?>
		<div class="mk-strip" data-mk-strip tabindex="0" role="group" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: property title */ __( 'Photos of %s. Use the arrow keys to browse.', 'makaazi' ), $c['title'] ) ); ?>">
		<?php else : ?>
		<div class="mk-strip" data-mk-strip>
		<?php endif; ?>
			<?php foreach ( $c['images'] as $i => $img ) : ?>
				<a class="mk-strip__slide" href="<?php echo esc_url( $c['url'] ); ?>" tabindex="-1" aria-hidden="<?php echo $i ? 'true' : 'false'; ?>">
					<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image output. ?>
					<?php if ( $i === count( $c['images'] ) - 1 && $c['extra'] > 0 ) : ?>
						<span class="mk-strip__more">
							<?php
							/* translators: %d: number of additional photos */
							echo esc_html( sprintf( _n( '+%d photo', '+%d photos', $c['extra'], 'makaazi' ), $c['extra'] ) );
							?>
						</span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php if ( $c['show_badges'] ) : ?>
			<div class="mk-badges">
				<span class="mk-badge mk-badge--<?php echo esc_attr( $c['status'] ); ?>"><?php echo esc_html( $c['status_label'] ); ?></span>
				<?php
				if ( $c['featured'] ) :
					?>
					<span class="mk-badge mk-badge--featured"><?php esc_html_e( 'Featured', 'makaazi' ); ?></span><?php endif; ?>
				<?php
				if ( $c['is_new'] ) :
					?>
					<span class="mk-badge mk-badge--new"><?php esc_html_e( 'New', 'makaazi' ); ?></span><?php endif; ?>
				<?php
				if ( $c['closed'] ) :
					?>
					<span class="mk-badge mk-badge--closed"><?php echo esc_html( $c['closed'] ); ?></span><?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( count( $c['images'] ) > 1 ) : ?>
			<button type="button" class="mk-strip__arrow mk-strip__arrow--prev" data-mk-strip-nav="-1" tabindex="-1" aria-label="<?php esc_attr_e( 'Previous photo', 'makaazi' ); ?>"><?php echo makaazi_icon( 'left' ); // phpcs:ignore ?></button>
			<button type="button" class="mk-strip__arrow mk-strip__arrow--next" data-mk-strip-nav="1" tabindex="-1" aria-label="<?php esc_attr_e( 'Next photo', 'makaazi' ); ?>"><?php echo makaazi_icon( 'right' ); // phpcs:ignore ?></button>
			<div class="mk-strip__dots" aria-hidden="true">
				<?php
				foreach ( $c['images'] as $i => $img ) :
					?>
					<i class="<?php echo 0 === $i ? 'is-on' : ''; ?>"></i><?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( $c['favourite'] ) : ?>
			<button type="button" class="mk-fav" data-mk-fav="<?php echo (int) $c['id']; ?>" aria-pressed="false" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: property title */ __( 'Save %s to favourites', 'makaazi' ), $c['title'] ) ); ?>"><?php echo makaazi_icon( 'heart', 18 ); // phpcs:ignore ?></button>
		<?php endif; ?>
	</div>
	<div class="mk-card__body">
		<?php
		if ( $c['meta_line'] ) :
			?>
			<div class="mk-card__meta"><?php echo esc_html( $c['meta_line'] ); ?></div><?php endif; ?>
		<h<?php echo (int) $c['heading']; ?> class="mk-card__title"><a href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['title'] ); ?></a></h<?php echo (int) $c['heading']; ?>>
		<?php if ( $c['location'] ) : ?><div class="mk-card__loc"><?php echo makaazi_icon( 'pin', 14 ); // phpcs:ignore ?> <?php echo esc_html( $c['location'] ); ?></div><?php endif; ?>
		<div class="mk-card__price"><?php echo $c['price']; // phpcs:ignore -- escaped in makaazi_price(). ?></div>
		<?php if ( $c['specs'] ) : ?>
			<ul class="mk-specs">
				<?php foreach ( $c['specs'] as $s ) : ?>
					<li title="<?php echo esc_attr( $s['label'] ); ?>"><?php echo makaazi_icon( $s['icon'], 15 ); // phpcs:ignore ?><span class="screen-reader-text"><?php echo esc_html( $s['label'] ); ?>: </span><?php echo $s['html']; // phpcs:ignore -- built from escaped parts. ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php
		if ( $c['why'] ) :
			?>
			<div class="mk-card__why"><?php echo esc_html( implode( ' · ', $c['why'] ) ); ?></div><?php endif; ?>
	</div>
	<div class="mk-card__foot">
		<a class="mk-btn mk-btn--outline mk-btn--block" href="<?php echo esc_url( $c['url'] ); ?>" aria-label="<?php echo esc_attr( $c['button_text'] . ': ' . $c['title'] ); ?>"><?php echo esc_html( $c['button_text'] ); ?></a>
		<?php if ( $c['compare'] ) : ?>
			<button type="button" class="mk-cmp" data-mk-cmp="<?php echo (int) $c['id']; ?>" aria-pressed="false" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: property title */ __( 'Compare %s', 'makaazi' ), $c['title'] ) ); ?>" title="<?php esc_attr_e( 'Compare', 'makaazi' ); ?>"><?php echo makaazi_icon( 'compare', 17 ); // phpcs:ignore ?></button>
		<?php endif; ?>
	</div>
</article>
