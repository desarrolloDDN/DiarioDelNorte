<?php
/**
 * Nota (entrada) individual.
 *
 * Cabecera, foto y firma a ancho amplio; el cuerpo de lectura en columna
 * estrecha centrada.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Content\PhotoCredit;
use DiarioDelNorte\Content\SubscriberOnly;
use DiarioDelNorte\Support\Ads;
use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'entrada' );

get_template_part( 'template-parts/latest-ticker' );
?>
<div class="wrap"><?php Ads::zone( 'home' ); ?></div>
<?php

while ( have_posts() ) :
	the_post();
	$ddn_cat        = Format::primary_category();
	$ddn_restricted = SubscriberOnly::is_restricted( get_the_ID() );
	// El límite de notas gratis se decide en el navegador (localStorage,
	// ver MeteredAccess): el sitio tiene caché de página, así que el HTML
	// de una nota se sirve idéntico a cualquier visitante sin sesión — el
	// servidor no puede saber cuántas ha leído cada quien. Por eso aquí
	// siempre se manda el contenido completo junto con el aviso (oculto
	// por CSS), y un script mínimo en el <head> decide cuál de los dos se
	// ve, sin depender de que esta página se vuelva a generar.
	$ddn_metered = ! $ddn_restricted && ! is_user_logged_in();
	?>
	<article <?php post_class( 'article' ); ?>>

		<header class="article__header">
			<?php if ( $ddn_cat ) : ?>
				<a class="kicker" href="<?php echo esc_url( get_category_link( $ddn_cat ) ); ?>"><?php echo esc_html( $ddn_cat->name ); ?></a>
			<?php endif; ?>
			<?php if ( $ddn_restricted ) : ?>
				<?php echo SubscriberOnly::badge_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado en badge_markup(). ?>
			<?php endif; ?>

			<h1 class="article__title"><?php the_title(); ?></h1>

			<?php if ( get_the_excerpt() ) : ?>
				<p class="article__standfirst"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="article__figure">
				<?php the_post_thumbnail( 'ddn-lead' ); ?>
				<?php
				$ddn_caption = get_the_post_thumbnail_caption();
				$ddn_credit  = PhotoCredit::formatted( get_the_ID() );
				if ( $ddn_caption || '' !== $ddn_credit ) :
					?>
					<figcaption>
						<?php if ( $ddn_caption ) : ?>
							<span class="article__figure-caption"><?php echo esc_html( $ddn_caption ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $ddn_credit ) : ?>
							<span class="article__figure-credit"><?php echo esc_html( $ddn_credit ); ?></span>
						<?php endif; ?>
					</figcaption>
					<?php
				endif;
				?>
			</figure>
		<?php endif; ?>

		<div class="article__meta">
			<?php
			get_template_part( 'template-parts/article-byline' );
			get_template_part( 'template-parts/share' );
			// Contrato con el plugin DDN Suite (módulo de suscriptores):
			// botón «Guardar»/«Guardado». Sin sesión, no imprime nada.
			do_action( 'ddn/article_save_button', get_the_ID() );
			?>
		</div>

		<?php Ads::zone( 'in-article-top' ); ?>

		<div class="article__body">
			<div class="prose">
				<?php if ( $ddn_restricted && ! SubscriberOnly::reader_can_view( get_the_ID() ) ) : ?>
					<?php echo wp_kses_post( wpautop( get_the_excerpt() ) ); ?>
					<?php get_template_part( 'template-parts/subscriber-paywall', null, array( 'reason' => 'exclusive' ) ); ?>
				<?php elseif ( $ddn_metered ) : ?>
					<div class="ddn-metered-paywall">
						<?php echo wp_kses_post( wpautop( get_the_excerpt() ) ); ?>
						<?php get_template_part( 'template-parts/subscriber-paywall', null, array( 'reason' => 'metered' ) ); ?>
					</div>
					<div class="ddn-metered-content"><?php the_content(); ?></div>
				<?php else : ?>
					<?php the_content(); ?>
				<?php endif; ?>
			</div>

			<div class="<?php echo esc_attr( $ddn_metered ? 'ddn-metered-content' : '' ); ?>">
				<?php Ads::zone( 'in-article-bottom' ); ?>

				<?php if ( has_tag() ) : ?>
					<div class="tags">
						<span class="tags__label"><?php esc_html_e( 'Temas relacionados', 'diario-del-norte' ); ?></span>
						<?php
						foreach ( (array) get_the_tags() as $ddn_tag ) {
							printf( '<a href="%s">%s</a>', esc_url( get_tag_link( $ddn_tag ) ), esc_html( $ddn_tag->name ) );
						}
						?>
					</div>
				<?php endif; ?>

				<?php
				// Sin sesión: invitación a suscribirse. Si la nota es exclusiva ya
				// sale el aviso propio del muro, no se repite (el de notas gratis
				// sí puede convivir: solo se oculta junto con el resto de este
				// bloque cuando el navegador decide que corresponde).
				if ( ! is_user_logged_in() && ! $ddn_restricted ) {
					get_template_part( 'template-parts/subscribe-cta' );
				}
				?>
			</div>
		</div>

		<?php
		// --- Lo último: las más recientes, sin repetir esta. Son 4 para que el móvil
		// (2 columnas) cierre en 2 filas; la 4.ª se oculta en escritorio ---
		$ddn_latest = new WP_Query(
			array(
				'posts_per_page'      => 4,
				'post__not_in'        => array( get_the_ID() ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		if ( $ddn_latest->have_posts() ) :
			?>
			<section class="article-latest">
				<h2 class="cat-more__title"><?php esc_html_e( 'Lo último', 'diario-del-norte' ); ?></h2>
				<div class="cat-grid cat-grid--3">
					<?php
					while ( $ddn_latest->have_posts() ) :
						$ddn_latest->the_post();
						get_template_part(
							'template-parts/article-latest-item',
							null,
							array( 'extra' => $ddn_latest->current_post >= 3 )
						);
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

	</article>
	<?php
endwhile;

get_footer();
