<?php
/**
 * Resultados de búsqueda.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap layout-stream">
	<header class="archive-head">
		<h1 class="archive-head__title">
			<?php
			/* translators: %s: términos buscados. */
			printf( esc_html__( 'Resultados para «%s»', 'diario-del-norte' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
			?>
		</h1>
		<?php get_search_form(); ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="entry-list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/entry-summary' );
			endwhile;
			?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
	<?php else : ?>
		<p class="notice-empty"><?php esc_html_e( 'No se encontró nada. Prueba con otras palabras.', 'diario-del-norte' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
