<?php
/**
 * Página 404.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap layout-page layout-404">
	<h1 class="page-head__title"><?php esc_html_e( 'Página no encontrada', 'diario-del-norte' ); ?></h1>
	<p><?php esc_html_e( 'El enlace puede estar roto o la nota fue movida. Prueba a buscar lo que necesitas.', 'diario-del-norte' ); ?></p>
	<?php get_search_form(); ?>

	<h2 class="section-title"><?php esc_html_e( 'Lo más reciente', 'diario-del-norte' ); ?></h2>
	<div class="card-row">
		<?php
		$ddn_recent = new WP_Query(
			array(
				'posts_per_page'      => 4,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		while ( $ddn_recent->have_posts() ) :
			$ddn_recent->the_post();
			get_template_part( 'template-parts/entry-card' );
		endwhile;
		wp_reset_postdata();
		?>
	</div>
</div>
<?php
get_footer();
