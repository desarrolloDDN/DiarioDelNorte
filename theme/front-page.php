<?php
/**
 * Portada del diario.
 *
 * 1. Apertura a tres columnas (3/5 – 1/5 – 1/5): carrusel de «Destacado»
 *    (imagen a sangre + degradado + tira de miniaturas), una nota de
 *    Judiciales, y una de Caribe + una de Nación.
 * 2. Publicidad (zona `home`).
 * 3. Cuerpo a dos columnas:
 *    - principal: La Guajira, Judiciales (carrusel), Opinión (carrusel) y
 *      «Más noticias» (todo lo que no salió antes);
 *    - lateral: Editorial, Edición impresa, Lo más leído (24 h, vía
 *      `ddn/most_read` del plugin) y boletín.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Sections\DefaultSectionsInstaller;
use DiarioDelNorte\Support\Ads;
use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

/** @var int[] $ddn_used IDs ya mostrados, para no repetir entre bloques. */
$ddn_used = array();

/**
 * Consulta de N entradas de una categoría (o del sitio, si la categoría
 * no existe o está vacía), excluyendo lo ya mostrado.
 *
 * @param WP_Term|null $term
 * @param int[]        $exclude
 */
$ddn_pick = static function ( ?WP_Term $term, int $count, array $exclude ): WP_Query {
	$args = array(
		'posts_per_page'      => $count,
		'post__not_in'        => $exclude,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $term instanceof WP_Term ) {
		$args['category__in'] = array( $term->term_id );
	}
	$query = new WP_Query( $args );
	if ( ! $query->have_posts() && isset( $args['category__in'] ) ) {
		unset( $args['category__in'] );
		$query = new WP_Query( $args );
	}

	return $query;
};

// --- Carrusel de destacados -------------------------------------------
$ddn_featured = get_term_by( 'slug', 'destacado', 'post_tag' );
if ( ! $ddn_featured instanceof WP_Term ) {
	$ddn_featured = get_term_by( 'name', 'Destacado', 'post_tag' );
}
$ddn_hero = new WP_Query(
	array(
		'tag__in'             => $ddn_featured instanceof WP_Term ? array( $ddn_featured->term_id ) : null,
		'posts_per_page'      => 6,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
if ( ! $ddn_hero->have_posts() ) {
	$ddn_hero = new WP_Query(
		array(
			'posts_per_page'      => 6,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
}
foreach ( $ddn_hero->posts as $ddn_post ) {
	$ddn_used[] = (int) $ddn_post->ID;
}

// --- Columnas central y derecha --------------------------------------
$ddn_jud_term = DefaultSectionsInstaller::category( 'judiciales' );
$ddn_jud      = $ddn_pick( $ddn_jud_term, 1, $ddn_used );
foreach ( $ddn_jud->posts as $ddn_post ) {
	$ddn_used[] = (int) $ddn_post->ID;
}

/** @var WP_Query[] $ddn_side Una nota de Caribe y una de Nación. */
$ddn_side = array();
foreach ( array( 'caribe', 'nacion' ) as $ddn_slug ) {
	$ddn_q = $ddn_pick( DefaultSectionsInstaller::category( $ddn_slug ), 1, $ddn_used );
	foreach ( $ddn_q->posts as $ddn_post ) {
		$ddn_used[] = (int) $ddn_post->ID;
	}
	$ddn_side[] = $ddn_q;
}
?>
<div class="wrap">

	<section class="home-hero">
		<div class="home-hero__grid">

			<div class="hero-slider" data-hero-slider aria-roledescription="<?php esc_attr_e( 'carrusel', 'diario-del-norte' ); ?>" aria-label="<?php esc_attr_e( 'Noticias destacadas', 'diario-del-norte' ); ?>">
				<div class="hero-slider__stage">
					<?php
					$ddn_i = 0;
					while ( $ddn_hero->have_posts() ) :
						$ddn_hero->the_post();
						$ddn_cat = Format::primary_category();
						?>
						<article class="hero-slide<?php echo 0 === $ddn_i ? ' is-active' : ''; ?>"<?php echo 0 === $ddn_i ? '' : ' aria-hidden="true"'; ?>>
							<a class="hero-slide__link" href="<?php the_permalink(); ?>">
								<?php
								the_post_thumbnail(
									'ddn-lead',
									array(
										'class'   => 'hero-slide__img',
										'loading' => 0 === $ddn_i ? 'eager' : 'lazy',
									)
								);
								?>
								<span class="hero-slide__shade" aria-hidden="true"></span>
								<span class="hero-slide__body">
									<?php if ( $ddn_cat ) : ?>
										<span class="hero-slide__tag"><?php echo esc_html( $ddn_cat->name ); ?></span>
									<?php endif; ?>
									<h2 class="hero-slide__title"><?php the_title(); ?></h2>
									<span class="hero-slide__meta"><?php echo esc_html( Format::published_label() ); ?></span>
								</span>
							</a>
						</article>
						<?php
						++$ddn_i;
					endwhile;
					?>
				</div>

				<?php if ( $ddn_i > 1 ) : ?>
					<div class="hero-slider__thumbs">
						<?php
						$ddn_hero->rewind_posts();
						$ddn_t = 0;
						while ( $ddn_hero->have_posts() ) :
							$ddn_hero->the_post();
							?>
							<button
								type="button"
								class="hero-slider__thumb<?php echo 0 === $ddn_t ? ' is-active' : ''; ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: título de la noticia. */ __( 'Ver: %s', 'diario-del-norte' ), get_the_title() ) ); ?>"
							>
								<?php
								the_post_thumbnail(
									'ddn-thumb',
									array(
										'loading' => 'lazy',
										'alt'     => '',
									)
								);
								?>
							</button>
							<?php
							++$ddn_t;
						endwhile;
						?>
					</div>
				<?php endif; ?>
				<?php wp_reset_postdata(); ?>
			</div>

			<div class="hero-col hero-col--feature">
				<?php
				while ( $ddn_jud->have_posts() ) :
					$ddn_jud->the_post();
					?>
					<?php $ddn_fcat = Format::primary_category(); ?>
					<article <?php post_class( 'hero-feature' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a class="hero-feature__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'ddn-card', array( 'loading' => 'lazy' ) ); ?></a>
						<?php endif; ?>
						<?php if ( $ddn_fcat ) : ?>
							<a class="hero-kicker" href="<?php echo esc_url( (string) get_category_link( $ddn_fcat ) ); ?>"><?php echo esc_html( $ddn_fcat->name ); ?></a>
						<?php endif; ?>
						<h3 class="hero-feature__title"><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<?php if ( get_the_excerpt() ) : ?>
							<p class="hero-feature__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 34, '…' ) ); ?></p>
						<?php endif; ?>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>

			<div class="hero-col hero-col--side">
				<?php
				foreach ( $ddn_side as $ddn_side_query ) :
					while ( $ddn_side_query->have_posts() ) :
						$ddn_side_query->the_post();
						?>
						<?php $ddn_scat = Format::primary_category(); ?>
						<article <?php post_class( 'hero-side-item' ); ?>>
							<?php if ( $ddn_scat ) : ?>
								<a class="hero-kicker" href="<?php echo esc_url( (string) get_category_link( $ddn_scat ) ); ?>"><?php echo esc_html( $ddn_scat->name ); ?></a>
							<?php endif; ?>
							<h3 class="hero-side-item__title"><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<?php if ( has_post_thumbnail() ) : ?>
								<a class="hero-side-item__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'ddn-card', array( 'loading' => 'lazy' ) ); ?></a>
							<?php endif; ?>
						</article>
						<?php
					endwhile;
					wp_reset_postdata();
				endforeach;
				?>
			</div>

		</div>
	</section>

	<?php Ads::zone( 'home' ); ?>

	<div class="home-layout">
		<div class="home-main">

			<?php
			// === La Guajira: 3 destacadas + 4 en lista =====================
			$ddn_gj   = DefaultSectionsInstaller::category( 'la-guajira' );
			$ddn_gj_q = $ddn_pick( $ddn_gj, 7, $ddn_used );
			if ( $ddn_gj_q->have_posts() ) :
				?>
				<section class="home-section">
					<?php
					get_template_part(
						'template-parts/home/section-head',
						null,
						array(
							'title' => $ddn_gj instanceof WP_Term ? $ddn_gj->name : __( 'La Guajira', 'diario-del-norte' ),
							'url'   => $ddn_gj instanceof WP_Term ? get_category_link( $ddn_gj ) : '',
						)
					);
					?>
					<div class="home-lead-row">
						<?php
						$ddn_n = 0;
						while ( $ddn_gj_q->have_posts() && $ddn_n < 3 ) :
							$ddn_gj_q->the_post();
							$ddn_used[] = get_the_ID();
							get_template_part( 'template-parts/home/card' );
							++$ddn_n;
						endwhile;
						?>
					</div>
					<?php if ( $ddn_gj_q->have_posts() ) : ?>
						<div class="home-list-2col">
							<?php
							while ( $ddn_gj_q->have_posts() ) :
								$ddn_gj_q->the_post();
								$ddn_used[] = get_the_ID();
								get_template_part( 'template-parts/home/card-row' );
							endwhile;
							?>
						</div>
					<?php endif; ?>
					<?php wp_reset_postdata(); ?>
				</section>
			<?php endif; ?>

			<?php
			// === Judiciales: carrusel de tarjetas =========================
			$ddn_jd   = DefaultSectionsInstaller::category( 'judiciales' );
			$ddn_jd_q = $ddn_pick( $ddn_jd, 8, $ddn_used );
			if ( $ddn_jd_q->have_posts() ) :
				?>
				<section class="home-section">
					<?php
					get_template_part(
						'template-parts/home/section-head',
						null,
						array(
							'title' => $ddn_jd instanceof WP_Term ? $ddn_jd->name : __( 'Judiciales', 'diario-del-norte' ),
							'url'   => $ddn_jd instanceof WP_Term ? get_category_link( $ddn_jd ) : '',
						)
					);
					?>
					<div class="card-slider" data-card-slider>
						<div class="card-slider__track">
							<?php
							while ( $ddn_jd_q->have_posts() ) :
								$ddn_jd_q->the_post();
								$ddn_used[] = get_the_ID();
								get_template_part( 'template-parts/home/card', null, array( 'badge' => true ) );
							endwhile;
							wp_reset_postdata();
							?>
						</div>
						<button type="button" class="card-slider__nav card-slider__nav--prev" aria-label="<?php esc_attr_e( 'Anterior', 'diario-del-norte' ); ?>" hidden>&lsaquo;</button>
						<button type="button" class="card-slider__nav card-slider__nav--next" aria-label="<?php esc_attr_e( 'Siguiente', 'diario-del-norte' ); ?>">&rsaquo;</button>
					</div>
				</section>
			<?php endif; ?>

			<?php
			// === Opinión: carrusel de columnistas ========================
			$ddn_op   = DefaultSectionsInstaller::category( 'opinion' );
			$ddn_op_q = $ddn_op instanceof WP_Term
				? new WP_Query(
					array(
						'category__in'        => array( $ddn_op->term_id ),
						'post__not_in'        => $ddn_used,
						'posts_per_page'      => 8,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				)
				: null;
			if ( $ddn_op_q instanceof WP_Query && $ddn_op_q->have_posts() ) :
				?>
				<section class="home-section">
					<?php
					get_template_part(
						'template-parts/home/section-head',
						null,
						array(
							'title' => $ddn_op->name,
							'url'   => get_category_link( $ddn_op ),
						)
					);
					?>
					<div class="card-slider card-slider--opinion" data-card-slider>
						<div class="card-slider__track">
							<?php
							while ( $ddn_op_q->have_posts() ) :
								$ddn_op_q->the_post();
								$ddn_used[] = get_the_ID();
								get_template_part( 'template-parts/opinion-card' );
							endwhile;
							wp_reset_postdata();
							?>
						</div>
						<button type="button" class="card-slider__nav card-slider__nav--prev" aria-label="<?php esc_attr_e( 'Anterior', 'diario-del-norte' ); ?>" hidden>&lsaquo;</button>
						<button type="button" class="card-slider__nav card-slider__nav--next" aria-label="<?php esc_attr_e( 'Siguiente', 'diario-del-norte' ); ?>">&rsaquo;</button>
					</div>
				</section>
			<?php endif; ?>

			<?php
			// === Más noticias: todo lo que no salió antes ================
			$ddn_mn = new WP_Query(
				array(
					'post__not_in'        => $ddn_used,
					'posts_per_page'      => 8,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			);
			if ( $ddn_mn->have_posts() ) :
				?>
				<section class="home-section">
					<?php
					get_template_part(
						'template-parts/home/section-head',
						null,
						array(
							'title' => __( 'Más noticias', 'diario-del-norte' ),
							'url'   => '',
						)
					);
					?>
					<div class="home-list-4col">
						<?php
						while ( $ddn_mn->have_posts() ) :
							$ddn_mn->the_post();
							get_template_part( 'template-parts/home/card-row', null, array( 'label' => true ) );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>

		</div><!-- .home-main -->

		<aside class="home-aside">

			<?php
			// --- Editorial ---
			$ddn_ed = DefaultSectionsInstaller::category( 'editorial' );
			if ( $ddn_ed instanceof WP_Term ) :
				$ddn_ed_q = new WP_Query(
					array(
						'category__in'        => array( $ddn_ed->term_id ),
						'posts_per_page'      => 1,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
				if ( $ddn_ed_q->have_posts() ) :
					$ddn_ed_q->the_post();
					?>
					<section class="aside-block aside-editorial">
						<div class="aside-block__head"><?php echo esc_html( $ddn_ed->name ); ?></div>
						<h3 class="aside-editorial__title"><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<?php if ( get_the_excerpt() ) : ?>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 32, '…' ) ); ?></p>
						<?php endif; ?>
						<a class="aside-editorial__link" href="<?php echo esc_url( get_category_link( $ddn_ed ) ); ?>"><?php esc_html_e( 'Más editoriales', 'diario-del-norte' ); ?> &rarr;</a>
					</section>
					<?php
					wp_reset_postdata();
				endif;
			endif;
			?>

			<?php
			// --- Edición impresa ---
			// El plugin DDN Suite provee la edición del día vía este filtro;
			// si no está activo, se usan los campos del Personalizador.
			$ddn_edition = apply_filters( 'ddn/print_edition', null );
			if ( is_array( $ddn_edition ) ) {
				$ddn_cover  = (int) ( $ddn_edition['cover_id'] ?? 0 );
				$ddn_ed_url = (string) ( $ddn_edition['permalink'] ?? '' );
			} else {
				$ddn_cover  = (int) get_theme_mod( 'ddn_print_cover', 0 );
				$ddn_ed_url = (string) get_post_type_archive_link( 'ddn_edition' );
			}
			// Sin plugin no hay entrada de la edición; se enlaza al PDF del
			// Personalizador solo como último recurso.
			if ( '' === $ddn_ed_url ) {
				$ddn_ed_url = (string) get_theme_mod( 'ddn_print_pdf', '' );
			}
			?>
			<section class="aside-block aside-print">
				<div class="aside-block__head"><?php esc_html_e( 'Edición impresa', 'diario-del-norte' ); ?></div>
				<?php
				if ( $ddn_cover ) {
					$ddn_cover_img = wp_get_attachment_image( $ddn_cover, 'medium_large', false, array( 'alt' => __( 'Portada de la edición impresa de hoy', 'diario-del-norte' ) ) );
					if ( '' !== $ddn_ed_url ) {
						printf( '<a class="aside-print__cover" href="%s">%s</a>', esc_url( $ddn_ed_url ), $ddn_cover_img ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image ya escapa.
					} else {
						printf( '<div class="aside-print__cover">%s</div>', $ddn_cover_img ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ídem.
					}
				} else {
					?>
					<div class="aside-print__cover aside-print__cover--empty"><span><?php esc_html_e( 'Portada no disponible', 'diario-del-norte' ); ?></span></div>
					<?php
				}
				?>
				<?php if ( '' !== $ddn_ed_url ) : ?>
					<a class="btn aside-print__btn" href="<?php echo esc_url( $ddn_ed_url ); ?>"><?php esc_html_e( 'Ver Edición Impresa', 'diario-del-norte' ); ?></a>
				<?php endif; ?>
			</section>

			<?php
			// --- Lo más leído (últimas 24 h; el plugin DDN Suite lo provee) ---
			$ddn_read_ids = array_values( array_filter( array_map( 'intval', (array) apply_filters( 'ddn/most_read', array(), 5 ) ) ) );
			if ( array() !== $ddn_read_ids ) {
				$ddn_read = new WP_Query(
					array(
						'post__in'            => $ddn_read_ids,
						'orderby'             => 'post__in',
						'posts_per_page'      => 5,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
			} else {
				$ddn_read = new WP_Query(
					array(
						'posts_per_page'      => 5,
						'orderby'             => array(
							'comment_count' => 'DESC',
							'date'          => 'DESC',
						),
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
			}
			if ( $ddn_read->have_posts() ) :
				?>
				<section class="aside-block aside-read">
					<div class="aside-block__head"><?php esc_html_e( 'Lo más leído', 'diario-del-norte' ); ?></div>
					<ol class="ranked ranked--lg">
						<?php
						while ( $ddn_read->have_posts() ) :
							$ddn_read->the_post();
							?>
							<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					</ol>
				</section>
			<?php endif; ?>

			<?php
			// --- Boletín ---
			$ddn_news_action = (string) apply_filters( 'ddn/newsletter_action', '' );
			if ( '' !== $ddn_news_action ) :
				?>
				<section class="aside-block aside-news">
					<div class="aside-block__head"><?php esc_html_e( 'Boletín', 'diario-del-norte' ); ?></div>
					<p class="aside-news__pitch"><?php esc_html_e( 'Recibe lo más importante de La Guajira y el Caribe en tu correo.', 'diario-del-norte' ); ?></p>
					<form class="aside-news__form" method="post" action="<?php echo esc_url( $ddn_news_action ); ?>">
						<label class="screen-reader-text" for="ddn-news-email"><?php esc_html_e( 'Tu correo electrónico', 'diario-del-norte' ); ?></label>
						<input type="email" id="ddn-news-email" name="email" required placeholder="<?php esc_attr_e( 'Tu correo electrónico', 'diario-del-norte' ); ?>">
						<button type="submit" class="btn"><?php esc_html_e( 'Suscribirme', 'diario-del-norte' ); ?></button>
					</form>
				</section>
			<?php endif; ?>

		</aside><!-- .home-aside -->
	</div><!-- .home-layout -->
</div>
<?php
get_footer();
