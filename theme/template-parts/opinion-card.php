<?php
/**
 * Tarjeta de columnista: foto cuadrada (con el detalle de comillas que la
 * «sostiene»), nombre, columna y botón «Leer columna».
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_author_id = (int) get_the_author_meta( 'ID' );
?>
<article <?php post_class( 'opinion-card' ); ?>>
	<div class="opinion-card__photo">
		<?php echo get_avatar( $ddn_author_id, 192 ); ?>
		<span class="opinion-card__quote" aria-hidden="true">&rdquo;</span>
	</div>
	<b class="opinion-card__name"><?php echo esc_html( get_the_author() ); ?></b>
	<h3 class="opinion-card__excerpt"><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
	<a class="btn opinion-card__btn" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Leer columna', 'diario-del-norte' ); ?></a>
</article>
