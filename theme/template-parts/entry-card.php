<?php
/**
 * Tarjeta de entrada: imagen + kicker + titular + fecha relativa.
 * Usada en las cuadrículas de sección y archivos.
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
<article <?php post_class( 'entry-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="entry-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'ddn-card', array( 'loading' => 'lazy' ) ); ?>
		</a>
	<?php endif; ?>

	<?php if ( $ddn_cat ) : ?>
		<a class="kicker kicker--ink" href="<?php echo esc_url( get_category_link( $ddn_cat ) ); ?>"><?php echo esc_html( $ddn_cat->name ); ?></a>
	<?php endif; ?>

	<h3 class="entry-card__title">
		<a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h3>

	<span class="meta"><?php echo esc_html( Format::time_ago() ); ?></span>
</article>
