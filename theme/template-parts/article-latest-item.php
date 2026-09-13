<?php
/**
 * Ítem de «Lo último» al pie de la nota: foto y titular, nada más
 * (reutiliza el estilo de tarjeta de la portada de sección, sin kicker
 * de categoría ni fecha).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article <?php post_class( 'cat-card' ); ?>>
	<a class="cat-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'ddn-card', array( 'loading' => 'lazy' ) );
		} else {
			echo '<span class="cat-ph" aria-hidden="true"></span>';
		}
		?>
	</a>
	<h3 class="cat-card__title">
		<a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h3>
</article>
