<?php
/**
 * Plantilla de reserva. WordPress la usa cuando ninguna plantilla más
 * específica aplica.
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
	<?php if ( have_posts() ) : ?>
		<div class="entry-list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/entry-summary' );
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
		<p class="notice-empty"><?php esc_html_e( 'Todavía no hay publicaciones.', 'diario-del-norte' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
