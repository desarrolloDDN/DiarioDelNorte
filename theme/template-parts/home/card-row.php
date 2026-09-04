<?php
/**
 * Fila de portada: miniatura + titular + fecha. Con `$args['label'] = true`
 * antepone la categoría en rojo (bloque «Más noticias»); con
 * `$args['full_date'] = true` la fecha es la de publicación completa en
 * vez de la antigüedad relativa corta («26 ago»).
 *
 * @param array{label?:bool,full_date?:bool} $args
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_label     = ! empty( $args['label'] );
$ddn_full_date = ! empty( $args['full_date'] );
$ddn_cat       = Format::primary_category();
?>
<article <?php post_class( 'h-row' ); ?>>
	<a class="h-row__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'ddn-thumb', array( 'loading' => 'lazy' ) );
		} else {
			echo '<span class="h-card__placeholder" aria-hidden="true"></span>';
		}
		?>
	</a>
	<div class="h-row__body">
		<?php if ( $ddn_label && $ddn_cat ) : ?>
			<span class="h-row__label"><?php echo esc_html( $ddn_cat->name ); ?></span>
		<?php endif; ?>
		<h3 class="h-row__title"><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<span class="meta"><?php echo esc_html( $ddn_full_date ? Format::published_label() : Format::time_ago() ); ?></span>
	</div>
</article>
