<?php
/**
 * Portada del diario.
 *
 * Retícula de apertura (nota principal + secundarias + rail) y bandas de
 * sección. La nota principal es la entrada fija (sticky) más reciente; si
 * no hay ninguna fija, la última publicada.
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

// --- Nota principal -----------------------------------------------------
$ddn_sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts' ) ) );
$ddn_lead   = new WP_Query(
	array(
		'post__in'            => array() !== $ddn_sticky ? $ddn_sticky : null,
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
if ( ! $ddn_lead->have_posts() ) {
	$ddn_lead = new WP_Query(
		array(
			'posts_per_page'      => 1,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
}

// --- Secundarias ------------------------------------------------------
$ddn_lead_id = $ddn_lead->have_posts() ? (int) $ddn_lead->posts[0]->ID : 0;
if ( $ddn_lead_id ) {
	$ddn_used[] = $ddn_lead_id;
}
$ddn_secondary = new WP_Query(
	array(
		'posts_per_page'      => 5,
		'post__not_in'        => $ddn_used,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
?>
<div class="wrap">
	<div class="lead-grid">
		<?php
		if ( $ddn_lead->have_posts() ) :
			$ddn_lead->the_post();
			$ddn_cat = Format::primary_category();
			?>
			<article class="story-lead">
				<?php if ( $ddn_cat ) : ?>
					<a class="kicker" href="<?php echo esc_url( get_category_link( $ddn_cat ) ); ?>"><?php echo esc_html( $ddn_cat->name ); ?></a>
				<?php endif; ?>
				<h2><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<?php if ( get_the_excerpt() ) : ?>
					<p class="standfirst"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 40, '…' ) ); ?></p>
				<?php endif; ?>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure>
						<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
						<?php
						the_post_thumbnail(
							'ddn-lead',
							array(
								'loading'       => 'eager',
								'fetchpriority' => 'high',
							)
						);
						?>
									</a>
					</figure>
				<?php endif; ?>
			</article>
			<?php
			wp_reset_postdata();
		endif;
		?>

		<div class="stack col-divider">
			<?php
			while ( $ddn_secondary->have_posts() ) :
				$ddn_secondary->the_post();
				$ddn_used[] = get_the_ID();
				$ddn_cat    = Format::primary_category();
				?>
				<article>
					<?php if ( has_post_thumbnail() && $ddn_secondary->current_post < 2 ) : ?>
						<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'ddn-card', array( 'loading' => 'lazy' ) ); ?></a>
					<?php endif; ?>
					<?php if ( $ddn_cat ) : ?>
						<span class="kicker kicker--ink"><?php echo esc_html( $ddn_cat->name ); ?></span>
					<?php endif; ?>
					<h3><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<?php if ( get_the_excerpt() && $ddn_secondary->current_post < 3 ) : ?>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>
					<?php endif; ?>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>

		<aside class="rail col-divider">
			<section>
				<div class="rail__head rail__head--live"><?php esc_html_e( 'Última hora', 'diario-del-norte' ); ?></div>
				<ul class="live-list">
					<?php
					$ddn_live = new WP_Query(
						array(
							'posts_per_page'      => 4,
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
						$ddn_label = (string) get_theme_mod( 'ddn_print_label', '' );
						if ( $ddn_label ) {
							echo '<span class="meta">' . esc_html( $ddn_label ) . '</span>';
						}
						echo '<span class="btn btn--sm">' . esc_html__( 'Leer en PDF', 'diario-del-norte' ) . '</span>';
						?>
					</a>
				</section>
			<?php endif; ?>
		</aside>
	</div>

	<?php Ads::zone( 'home' ); ?>

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
