<?php
/**
 * Portada del diario.
 *
 * Sección de apertura a tres columnas (1/2 – 1/4 – 1/4):
 *   - izquierda: carrusel de 6 noticias con la etiqueta «Destacado»;
 *   - centro:    una nota de «Judiciales» (imagen, titular, extracto);
 *   - derecha:   una de «Caribe» y una de «Nación» (titular e imagen).
 * Debajo: publicidad, tira de servicio (última hora / lo más leído /
 * edición impresa) y bandas de sección.
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
							<span class="hero-slide__tag"><?php echo esc_html( $ddn_featured instanceof WP_Term ? $ddn_featured->name : __( 'Destacado', 'diario-del-norte' ) ); ?></span>
							<div class="hero-slide__body">
								<h2 class="hero-slide__title"><?php the_title(); ?></h2>
								<?php if ( get_the_excerpt() ) : ?>
									<p class="hero-slide__standfirst"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26, '…' ) ); ?></p>
								<?php endif; ?>
								<span class="hero-slide__meta">
									<?php
									if ( $ddn_cat ) {
										echo esc_html( $ddn_cat->name ) . ' · ';
									}
									echo esc_html( Format::time_ago() );
									?>
								</span>
							</div>
						</a>
					</article>
					<?php
					++$ddn_i;
				endwhile;
				wp_reset_postdata();
				?>

				<?php if ( $ddn_i > 1 ) : ?>
					<div class="hero-slider__dots">
						<?php for ( $ddn_d = 0; $ddn_d < $ddn_i; $ddn_d++ ) : ?>
							<button type="button" class="hero-slider__dot<?php echo 0 === $ddn_d ? ' is-active' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: número de la noticia. */ __( 'Ir a la noticia %d', 'diario-del-norte' ), $ddn_d + 1 ) ); ?>"></button>
						<?php endfor; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="hero-col hero-col--feature">
				<?php
				while ( $ddn_jud->have_posts() ) :
					$ddn_jud->the_post();
					?>
					<article <?php post_class( 'hero-feature' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a class="hero-feature__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'ddn-card', array( 'loading' => 'lazy' ) ); ?></a>
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
						<article <?php post_class( 'hero-side-item' ); ?>>
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

	<div class="home-rail">
		<section>
			<div class="rail__head rail__head--live"><?php esc_html_e( 'Última hora', 'diario-del-norte' ); ?></div>
			<ul class="live-list">
				<?php
				$ddn_live = new WP_Query(
					array(
						'posts_per_page'      => 5,
						'post__not_in'        => $ddn_used,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
				while ( $ddn_live->have_posts() ) :
					$ddn_live->the_post();
					$ddn_used[] = get_the_ID();
					?>
					<li>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'H:i' ) ); ?></time>
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</li>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</ul>
		</section>

		<section>
			<div class="rail__head"><?php esc_html_e( 'Lo más leído', 'diario-del-norte' ); ?></div>
			<ol class="ranked">
				<?php
				$ddn_read = new WP_Query(
					array(
						'posts_per_page'      => 5,
						'orderby'             => array(
							'comment_count' => 'DESC',
							'date'          => 'DESC',
						),
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
						'date_query'          => array( array( 'after' => '30 days ago' ) ),
					)
				);
				if ( ! $ddn_read->have_posts() ) {
					$ddn_read = new WP_Query(
						array(
							'posts_per_page'      => 5,
							'ignore_sticky_posts' => true,
							'no_found_rows'       => true,
						)
					);
				}
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

		<?php
		$ddn_cover = (int) get_theme_mod( 'ddn_print_cover', 0 );
		$ddn_pdf   = (string) get_theme_mod( 'ddn_print_pdf', '' );
		if ( $ddn_cover || $ddn_pdf ) :
			?>
			<section>
				<div class="rail__head"><?php esc_html_e( 'Edición impresa', 'diario-del-norte' ); ?></div>
				<a class="impresa" href="<?php echo esc_url( '' !== $ddn_pdf ? $ddn_pdf : '#' ); ?>">
					<?php
					if ( $ddn_cover ) {
						echo wp_get_attachment_image( $ddn_cover, 'medium', false, array( 'alt' => __( 'Portada de la edición impresa', 'diario-del-norte' ) ) );
					}
					?>
					<span class="impresa__body">
						<?php
						$ddn_label = (string) get_theme_mod( 'ddn_print_label', '' );
						if ( $ddn_label ) {
							echo '<span class="meta">' . esc_html( $ddn_label ) . '</span>';
						}
						?>
						<span class="btn btn--sm"><?php esc_html_e( 'Leer en PDF', 'diario-del-norte' ); ?></span>
					</span>
				</a>
			</section>
		<?php endif; ?>
	</div>

	<?php
	$ddn_bands = apply_filters( 'ddn/home_sections', array( 'la-guajira', 'judiciales', 'opinion' ) );
	foreach ( (array) $ddn_bands as $ddn_slug ) :
		$ddn_term = DefaultSectionsInstaller::category( (string) $ddn_slug );
		if ( ! $ddn_term ) {
			continue;
		}
		$ddn_band = new WP_Query(
			array(
				'category__in'        => array( $ddn_term->term_id ),
				'post__not_in'        => $ddn_used,
				'posts_per_page'      => 4,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		if ( ! $ddn_band->have_posts() ) {
			continue;
		}
		$ddn_is_opinion = in_array( $ddn_slug, array( 'opinion', 'editorial' ), true );
		?>
		<section class="section-band<?php echo $ddn_is_opinion ? ' section-band--tint' : ''; ?>">
			<div class="section-band__head">
				<h2><?php echo esc_html( $ddn_term->name ); ?></h2>
				<a href="<?php echo esc_url( get_category_link( $ddn_term ) ); ?>"><?php esc_html_e( 'Ver toda la sección', 'diario-del-norte' ); ?> &rarr;</a>
			</div>
			<div class="<?php echo $ddn_is_opinion ? 'opinion-row' : 'card-row'; ?>">
				<?php
				while ( $ddn_band->have_posts() ) :
					$ddn_band->the_post();
					$ddn_used[] = get_the_ID();
					get_template_part( 'template-parts/' . ( $ddn_is_opinion ? 'opinion-card' : 'entry-card' ) );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php
	endforeach;
	?>
</div>
<?php
get_footer();
