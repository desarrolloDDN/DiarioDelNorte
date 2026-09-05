<?php
/**
 * Una edición impresa: portada grande, fecha, nota de la redacción y
 * botones para descargar el PDF o leerlo en línea (visor embebido, sin
 * descargar). El CPT `ddn_edition` lo provee el plugin DDN Suite.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$ddn_pdf     = (string) apply_filters( 'ddn/edition_pdf_url', '', get_the_ID() );
	$ddn_archive = get_post_type_archive_link( 'ddn_edition' );
	?>
	<article <?php post_class( 'wrap edition' ); ?>>

		<header class="edition__head">
			<p class="edition__kicker"><?php esc_html_e( 'Edición impresa', 'diario-del-norte' ); ?></p>
			<h1 class="edition__title"><?php the_title(); ?></h1>
			<p class="edition__date">
				<?php echo esc_html( get_the_date( 'l j \d\e F \d\e Y' ) ); ?>
			</p>
		</header>

		<div class="edition__body">
			<div class="edition__cover">
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail(
						'large',
						array( 'alt' => get_the_title() )
					);
				} else {
					echo '<span class="edition__cover-empty">' . esc_html__( 'Portada no disponible', 'diario-del-norte' ) . '</span>';
				}
				?>
			</div>

			<div class="edition__aside">
				<?php if ( '' !== $ddn_pdf ) : ?>
					<div class="edition__actions">
						<a class="btn edition__download" href="<?php echo esc_url( $ddn_pdf ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Descargar edición en PDF', 'diario-del-norte' ); ?>
						</a>
						<button
							type="button"
							class="btn btn--ghost edition__read-toggle"
							data-edition-reader-toggle
							data-label-hide="<?php esc_attr_e( 'Cerrar lectura en línea', 'diario-del-norte' ); ?>"
							aria-controls="edition-reader"
							aria-expanded="false"
						>
							<?php esc_html_e( 'Leer en línea', 'diario-del-norte' ); ?>
						</button>
					</div>
				<?php endif; ?>

				<?php if ( get_the_content() ) : ?>
					<div class="prose edition__note"><?php the_content(); ?></div>
				<?php endif; ?>

				<?php if ( $ddn_archive ) : ?>
					<a class="edition__archive-link" href="<?php echo esc_url( $ddn_archive ); ?>">
						<?php esc_html_e( 'Ver ediciones anteriores', 'diario-del-norte' ); ?> &rarr;
					</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( '' !== $ddn_pdf ) : ?>
			<div class="edition__reader" id="edition-reader" hidden>
				<iframe
					src="<?php echo esc_url( $ddn_pdf . '#view=FitH' ); ?>"
					title="<?php echo esc_attr( sprintf( /* translators: %s: título de la edición. */ __( 'Lectura en línea: %s', 'diario-del-norte' ), get_the_title() ) ); ?>"
					loading="lazy"
				></iframe>
			</div>
		<?php endif; ?>

	</article>
	<?php
endwhile;

get_footer();
