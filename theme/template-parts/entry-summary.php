<?php
/**
 * Resumen de entrada para páginas de archivo y resultados de búsqueda.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_cat = Format::primary_category();
?>
<article <?php post_class( 'entry-summary' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="entry-summary__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'ddn-card', array( 'loading' => 'lazy' ) ); ?>
		</a>
	<?php endif; ?>

	<div class="entry-summary__body">
		<?php if ( $ddn_cat ) : ?>
			<a class="kicker" href="<?php echo esc_url( get_category_link( $ddn_cat ) ); ?>"><?php echo esc_html( $ddn_cat->name ); ?></a>
		<?php endif; ?>

		<h2 class="entry-summary__title">
			<a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<?php if ( has_excerpt() || get_the_excerpt() ) : ?>
			<p class="entry-summary__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 32, '…' ) ); ?></p>
		<?php endif; ?>

		<span class="meta">
			<?php
			echo esc_html( get_the_date( 'j M Y' ) );
			$ddn_author = get_the_author();
			if ( $ddn_author ) {
				echo ' · ' . esc_html( $ddn_author );
			}
			?>
		</span>
	</div>
</article>
