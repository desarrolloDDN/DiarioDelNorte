<?php
/**
 * Elemento de «Más leídas»: número de orden, miniatura y titular.
 * El número lo pinta el contador CSS de la lista contenedora.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<li class="cat-ranked__item">
	<a class="cat-ranked__link" href="<?php the_permalink(); ?>">
		<span class="cat-ranked__media">
			<?php
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'ddn-thumb', array( 'loading' => 'lazy' ) );
			} else {
				echo '<span class="cat-ph" aria-hidden="true"></span>';
			}
			?>
		</span>
		<span class="cat-ranked__title"><?php the_title(); ?></span>
	</a>
</li>
