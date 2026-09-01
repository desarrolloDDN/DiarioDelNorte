<?php
/**
 * Portada de sección (archivo de categoría). Una sola consulta principal
 * (36 entradas/página, vía Theme::category_posts_per_page) se reparte en
 * varios bloques en la primera página; a partir de la segunda es una
 * cuadrícula corrida de «Más noticias». Mismo diseño para todas las
 * categorías, presentes y futuras.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

global $wp_query;

$ddn_term = get_queried_object();
$ddn_name = $ddn_term instanceof WP_Term ? $ddn_term->name : single_cat_title( '', false );
$ddn_desc = $ddn_term instanceof WP_Term ? category_description( $ddn_term ) : '';

/** @var WP_Post[] $ddn_posts Entradas de esta página, en orden. */
$ddn_posts  = $wp_query->posts;
$ddn_cursor = 0;

/**
 * Devuelve las siguientes $n entradas del lote y avanza el cursor.
 *
 * @return WP_Post[]
 */
$ddn_take = static function ( int $n ) use ( &$ddn_posts, &$ddn_cursor ): array {
	$slice       = array_slice( $ddn_posts, $ddn_cursor, $n );
	$ddn_cursor += count( $slice );

	return $slice;
};

/**
 * Pinta una plantilla parcial para cada entrada de un lote.
 *
 * @param WP_Post[]             $slice
 * @param array<string,mixed>   $part_args
 */
$ddn_loop = static function ( array $slice, string $part, array $part_args = array() ): void {
	global $post;
	foreach ( $slice as $ddn_p ) {
		$post = $ddn_p; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
		get_template_part( 'template-parts/category/' . $part, null, $part_args );
	}
	wp_reset_postdata();
};

$ddn_paged = is_paged();
?>
<div class="wrap cat">

	<header class="cat-head">
		<h1 class="cat-head__title"><?php echo esc_html( $ddn_name ); ?></h1>
		<?php if ( '' !== trim( (string) $ddn_desc ) ) : ?>
			<p class="cat-head__desc"><?php echo esc_html( wp_strip_all_tags( (string) $ddn_desc ) ); ?></p>
		<?php endif; ?>
	</header>

	<?php if ( empty( $ddn_posts ) ) : ?>
		<p class="notice-empty"><?php esc_html_e( 'No hay entradas en esta sección todavía.', 'diario-del-norte' ); ?></p>
	<?php else : ?>

		<?php if ( ! $ddn_paged ) : ?>

			<?php
			// --- Bloque 1: nota principal + lista lateral ---------------
			$ddn_lead = $ddn_take( 1 );
			$ddn_list = $ddn_take( 6 );
			if ( $ddn_lead ) :
				?>
				<section class="cat-block cat-lead">
					<div class="cat-lead__main"><?php $ddn_loop( $ddn_lead, 'feature' ); ?></div>
					<?php if ( $ddn_list ) : ?>
						<div class="cat-lead__side"><?php $ddn_loop( $ddn_list, 'row' ); ?></div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php
			// --- Bloque 2: cuadrícula de 4 ------------------------------
			$ddn_g = $ddn_take( 4 );
			if ( $ddn_g ) :
				?>
				<section class="cat-block cat-grid cat-grid--4"><?php $ddn_loop( $ddn_g, 'card' ); ?></section>
			<?php endif; ?>

			<?php
			// --- Bloque 3: dos tarjetas con titular sobre la imagen -----
			$ddn_g = $ddn_take( 2 );
			if ( $ddn_g ) :
				?>
				<section class="cat-block cat-grid cat-grid--2"><?php $ddn_loop( $ddn_g, 'overlay', array( 'size' => 'ddn-lead' ) ); ?></section>
			<?php endif; ?>

			<?php
			// --- Bloque 4: cuadrícula de 5 -----------------------------
			$ddn_g = $ddn_take( 5 );
			if ( $ddn_g ) :
				?>
				<section class="cat-block cat-grid cat-grid--5"><?php $ddn_loop( $ddn_g, 'card' ); ?></section>
			<?php endif; ?>

			<?php
			// --- Más leídas de la sección -----------------------------
			$ddn_read_ids = array_values( array_filter( array_map( 'intval', (array) apply_filters( 'ddn/most_read', array(), 40 ) ) ) );
			$ddn_cat_id   = $ddn_term instanceof WP_Term ? (int) $ddn_term->term_id : 0;
			$ddn_read     = null;
			if ( $ddn_cat_id > 0 && $ddn_read_ids ) {
				$ddn_read = new WP_Query(
					array(
						'post__in'            => $ddn_read_ids,
						'orderby'             => 'post__in',
						'category__in'        => array( $ddn_cat_id ),
						'posts_per_page'      => 5,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
			}
			if ( ( ! $ddn_read instanceof WP_Query || ! $ddn_read->have_posts() ) && $ddn_cat_id > 0 ) {
				$ddn_read = new WP_Query(
					array(
						'category__in'        => array( $ddn_cat_id ),
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
			if ( $ddn_read instanceof WP_Query && $ddn_read->have_posts() ) :
				?>
				<section class="cat-block cat-mostread">
					<h2 class="cat-mostread__title"><?php esc_html_e( 'Más leídas', 'diario-del-norte' ); ?></h2>
					<ol class="cat-ranked">
						<?php
						while ( $ddn_read->have_posts() ) :
							$ddn_read->the_post();
							get_template_part( 'template-parts/category/ranked' );
						endwhile;
						wp_reset_postdata();
						?>
					</ol>
				</section>
			<?php endif; ?>

			<?php
			// --- Bloque 5: nota grande + dos tarjetas -----------------
			$ddn_lead = $ddn_take( 1 );
			$ddn_side = $ddn_take( 2 );
			if ( $ddn_lead ) :
				?>
				<section class="cat-block cat-lead cat-lead--wide">
					<div class="cat-lead__main"><?php $ddn_loop( $ddn_lead, 'overlay', array( 'size' => 'ddn-lead' ) ); ?></div>
					<?php if ( $ddn_side ) : ?>
						<div class="cat-lead__side cat-lead__side--cards"><?php $ddn_loop( $ddn_side, 'card' ); ?></div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php
			// --- Bloque 6: cuadrícula de 4 ----------------------------
			$ddn_g = $ddn_take( 4 );
			if ( $ddn_g ) :
				?>
				<section class="cat-block cat-grid cat-grid--4"><?php $ddn_loop( $ddn_g, 'card' ); ?></section>
			<?php endif; ?>

		<?php endif; /* ! $ddn_paged */ ?>

		<?php
		// --- Más noticias de la sección: el resto del lote --------------
		$ddn_rest = $ddn_paged ? $ddn_posts : $ddn_take( count( $ddn_posts ) );
		if ( $ddn_rest ) :
			?>
			<section class="cat-block cat-more">
				<h2 class="cat-more__title">
					<?php
					/* translators: %s: nombre de la sección. */
					printf( esc_html__( 'Más noticias de %s', 'diario-del-norte' ), esc_html( $ddn_name ) );
					?>
				</h2>
				<div class="cat-grid cat-grid--3">
					<?php $ddn_loop( $ddn_rest, 'card', array( 'byline' => true ) ); ?>
				</div>
			</section>
		<?php endif; ?>

		<?php
		the_posts_pagination(
			array(
				'mid_size'           => 1,
				'screen_reader_text' => __( 'Navegación de entradas', 'diario-del-norte' ),
			)
		);
		?>

	<?php endif; ?>
</div>
<?php
get_footer();
