<?php
/**
 * Nota (entrada) individual.
 *
 * Cabecera, foto y firma a ancho amplio; el cuerpo de lectura en columna
 * estrecha centrada. «Le puede interesar» vuelve a ancho amplio.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Content\InlineRelated;
use DiarioDelNorte\Content\PhotoCredit;
use DiarioDelNorte\Support\Ads;
use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'entrada' );

get_template_part( 'template-parts/latest-ticker' );

while ( have_posts() ) :
	the_post();
	$ddn_cat = Format::primary_category();
	?>
	<article <?php post_class( 'article' ); ?>>

		<header class="article__header">
			<?php if ( $ddn_cat ) : ?>
				<a class="kicker" href="<?php echo esc_url( get_category_link( $ddn_cat ) ); ?>"><?php echo esc_html( $ddn_cat->name ); ?></a>
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
			?>
		</div>

		<?php Ads::zone( 'in-article-top' ); ?>

		<div class="article__body">
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
		</div>

		<?php
		if ( $ddn_cat ) :
			$ddn_related = new WP_Query(
				array(
					'category__in'        => array( $ddn_cat->term_id ),
					'post__not_in'        => array_merge( array( get_the_ID() ), InlineRelated::used_ids() ),
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
