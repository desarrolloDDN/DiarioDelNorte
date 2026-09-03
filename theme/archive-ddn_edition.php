<?php
/**
 * Archivo público de ediciones impresas: cuadrícula de portadas por
 * fecha, de la más reciente a la más antigua.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap layout-archive edition-archive">
	<header class="archive-head">
		<h1 class="archive-head__title"><?php esc_html_e( 'Edición impresa', 'diario-del-norte' ); ?></h1>
		<div class="archive-head__desc">
			<?php esc_html_e( 'Portadas y ediciones digitales de Diario del Norte, día por día.', 'diario-del-norte' ); ?>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="edition-grid">
			<?php
			while ( have_posts() ) :
				the_post();

				$ddn_long = get_the_date( 'l j \d\e F \d\e Y' );
				$ddn_lead = get_the_date( 'Y-m-d' ) === current_time( 'Y-m-d' )
					/* translators: %s: fecha en formato largo, p. ej. «jueves 3 de septiembre de 2026». */
					? sprintf( __( 'Esta es nuestra edición impresa de hoy %s', 'diario-del-norte' ), $ddn_long )
					/* translators: %s: fecha en formato largo. */
					: sprintf( __( 'Nuestra edición impresa del %s', 'diario-del-norte' ), $ddn_long );
				?>
				<article class="edition-card">
					<a class="edition-card__link" href="<?php the_permalink(); ?>">
						<span class="edition-card__lead"><?php echo esc_html( $ddn_lead ); ?></span>
						<span class="edition-card__cover">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'ddn-card', array( 'alt' => get_the_title() ) );
							} else {
								echo '<span class="edition-card__cover-empty"></span>';
							}
							?>
						</span>
					</a>
				</article>
				<?php
			endwhile;
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'mid_size'           => 1,
				'screen_reader_text' => __( 'Navegación de ediciones', 'diario-del-norte' ),
			)
		);
		?>
	<?php else : ?>
		<p class="notice-empty"><?php esc_html_e( 'Todavía no hay ediciones publicadas.', 'diario-del-norte' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
