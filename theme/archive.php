<?php
/**
 * Archivo por categoría, etiqueta, autor o fecha. WordPress lo usa como
 * fallback de category.php / tag.php / author.php.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap layout-archive">
	<header class="archive-head">
		<?php
		if ( is_author() ) {
			echo get_avatar( get_the_author_meta( 'ID' ), 72, '', '', array( 'class' => 'archive-head__avatar' ) );
		}
		?>
		<h1 class="archive-head__title"><?php the_archive_title(); ?></h1>
		<?php the_archive_description( '<div class="archive-head__desc">', '</div>' ); ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="card-row card-row--archive">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/entry-card' );
			endwhile;
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'mid_size'           => 1,
				'screen_reader_text' => __( 'Navegación de entradas', 'diario-del-norte' ),
			)
		);
		?>
	<?php else : ?>
		<p class="notice-empty"><?php esc_html_e( 'No hay entradas en esta sección todavía.', 'diario-del-norte' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
