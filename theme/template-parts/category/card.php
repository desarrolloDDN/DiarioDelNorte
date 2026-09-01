<?php
/**
 * Tarjeta de cuadrícula: imagen arriba, kicker de sección en rojo y
 * titular. Con `$args['byline'] = true` añade autor y fecha (bloque
 * «Más noticias»).
 *
 * @param array{byline?:bool,size?:string} $args
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_byline = ! empty( $args['byline'] );
$ddn_size   = isset( $args['size'] ) ? (string) $args['size'] : 'ddn-card';
$ddn_cat    = Format::primary_category();
?>
<article <?php post_class( 'cat-card' ); ?>>
	<a class="cat-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( $ddn_size, array( 'loading' => 'lazy' ) );
		} else {
			echo '<span class="cat-ph" aria-hidden="true"></span>';
		}
		?>
	</a>
	<?php if ( $ddn_cat ) : ?>
		<a class="cat-card__kicker" href="<?php echo esc_url( get_category_link( $ddn_cat ) ); ?>"><?php echo esc_html( $ddn_cat->name ); ?></a>
	<?php endif; ?>
	<h3 class="cat-card__title">
		<a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h3>
	<?php if ( $ddn_byline ) : ?>
		<p class="cat-card__byline">
			<?php
			$ddn_author = get_the_author();
			echo esc_html( $ddn_author ? $ddn_author . ' · ' : '' );
			echo esc_html( get_the_date( 'j \d\e F \d\e Y' ) );
			?>
		</p>
	<?php endif; ?>
</article>
