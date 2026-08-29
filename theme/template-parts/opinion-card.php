<?php
/**
 * Tarjeta de columna de opinión: foto del autor + nombre + cargo +
 * titular de la columna.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Users\AuthorProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_author_id = (int) get_the_author_meta( 'ID' );
$ddn_role      = AuthorProfile::role( $ddn_author_id );
?>
<article <?php post_class( 'opinion-card' ); ?>>
	<div class="opinion-card__by">
		<?php echo get_avatar( $ddn_author_id, 88 ); ?>
		<div>
			<b><?php echo esc_html( get_the_author() ); ?></b>
			<span><?php echo esc_html( '' !== $ddn_role ? $ddn_role : __( 'Columnista', 'diario-del-norte' ) ); ?></span>
		</div>
	</div>
	<h3><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
</article>
