<?php
/**
 * Nota (entrada) individual.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Ads;
use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$ddn_cat     = Format::primary_category();
	$ddn_updated = get_the_modified_time( 'U' ) - get_the_time( 'U' ) > HOUR_IN_SECONDS;
	?>
	<article <?php post_class( 'article' ); ?>>
		<div class="article__breadcrumb dateline">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Portada', 'diario-del-norte' ); ?></a>
			<?php if ( $ddn_cat ) : ?>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( get_category_link( $ddn_cat ) ); ?>"><?php echo esc_html( $ddn_cat->name ); ?></a>
			<?php endif; ?>
		</div>

		<header class="article__header">
			<?php if ( $ddn_cat ) : ?>
				<span class="kicker"><?php echo esc_html( $ddn_cat->name ); ?></span>
			<?php endif; ?>

			<h1 class="article__title"><?php the_title(); ?></h1>

			<?php if ( get_the_excerpt() ) : ?>
				<p class="article__standfirst"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>

			<div class="byline">
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 76 ); ?>
				<div class="byline__who">
					<b>
						<?php
						/* translators: %s: nombre del autor. */
						printf( esc_html__( 'Por %s', 'diario-del-norte' ), esc_html( get_the_author() ) );
						?>
					</b>
					<span><?php echo esc_html( get_the_author_meta( 'description' ) ? wp_trim_words( get_the_author_meta( 'description' ), 6, '' ) : __( 'Redacción', 'diario-del-norte' ) ); ?></span>
				</div>
				<div class="byline__when">
					<?php
					echo esc_html( get_the_date( 'j M Y · H:i' ) );
					if ( $ddn_updated ) {
						echo '<br>' . esc_html__( 'Actualizado', 'diario-del-norte' ) . ' ' . esc_html( get_the_modified_date( 'H:i' ) );
					}
					$ddn_min = Format::reading_minutes();
					/* translators: %d: minutos de lectura. */
					echo '<br>' . esc_html( sprintf( _n( '%d min de lectura', '%d min de lectura', $ddn_min, 'diario-del-norte' ), $ddn_min ) );
					?>
				</div>
			</div>
		</header>

		<?php Ads::zone( 'in-article-top' ); ?>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="article__figure">
				<?php the_post_thumbnail( 'ddn-lead' ); ?>
				<?php
				$ddn_caption = get_the_post_thumbnail_caption();
				if ( $ddn_caption ) {
					echo '<figcaption>' . esc_html( $ddn_caption ) . '</figcaption>';
				}
				?>
			</figure>
		<?php endif; ?>

		<div class="prose">
			<?php the_content(); ?>
		</div>

		<?php Ads::zone( 'in-article-bottom' ); ?>

		<?php if ( has_tag() ) : ?>
			<div class="tags">
				<?php
				foreach ( (array) get_the_tags() as $ddn_tag ) {
					printf( '<a href="%s">%s</a>', esc_url( get_tag_link( $ddn_tag ) ), esc_html( $ddn_tag->name ) );
				}
				?>
			</div>
		<?php endif; ?>

		<?php if ( get_the_author_meta( 'description' ) ) : ?>
			<aside class="author-box">
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 128 ); ?>
				<div>
					<b><?php echo esc_html( get_the_author() ); ?></b>
					<p><?php echo esc_html( get_the_author_meta( 'description' ) ); ?></p>
				</div>
			</aside>
		<?php endif; ?>

		<?php
		if ( $ddn_cat ) :
			$ddn_related = new WP_Query(
				array(
					'category__in'        => array( $ddn_cat->term_id ),
					'post__not_in'        => array( get_the_ID() ),
					'posts_per_page'      => 3,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			);
			if ( $ddn_related->have_posts() ) :
				?>
				<section class="related">
					<h2><?php esc_html_e( 'Le puede interesar', 'diario-del-norte' ); ?></h2>
					<div class="related__row">
						<?php
						while ( $ddn_related->have_posts() ) :
							$ddn_related->the_post();
							get_template_part( 'template-parts/entry-card' );
						endwhile;
						?>
					</div>
				</section>
				<?php
			endif;
			wp_reset_postdata();
		endif;
		?>
	</article>
	<?php
endwhile;

get_footer();
