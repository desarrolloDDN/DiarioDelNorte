<?php
/**
 * Fila compacta de la portada de sección: miniatura, kicker de sección
 * en rojo y titular.
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
<article <?php post_class( 'cat-row' ); ?>>
	<a class="cat-row__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'ddn-thumb', array( 'loading' => 'lazy' ) );
		} else {
			echo '<span class="cat-ph" aria-hidden="true"></span>';
		}
		?>
	</a>
	<div class="cat-row__body">
		<?php if ( $ddn_cat ) : ?>
			<a class="cat-row__kicker" href="<?php echo esc_url( get_category_link( $ddn_cat ) ); ?>"><?php echo esc_html( $ddn_cat->name ); ?></a>
		<?php endif; ?>
		<h3 class="cat-row__title">
			<a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
	</div>
</article>
