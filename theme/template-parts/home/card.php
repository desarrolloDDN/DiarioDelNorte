<?php
/**
 * Tarjeta de portada: imagen arriba, titular, fecha relativa. Opcional un
 * distintivo de categoría sobre la imagen (`$args['badge'] = true`).
 *
 * @param array{badge?:bool,size?:string} $args
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_badge = ! empty( $args['badge'] );
$ddn_size  = isset( $args['size'] ) ? (string) $args['size'] : 'ddn-card';
$ddn_cat   = Format::primary_category();
?>
<article <?php post_class( 'h-card' ); ?>>
	<div class="h-card__media">
		<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( $ddn_size, array( 'loading' => 'lazy' ) );
			} else {
				echo '<span class="h-card__placeholder" aria-hidden="true"></span>';
			}
			?>
		</a>
		<?php if ( $ddn_badge && $ddn_cat ) : ?>
			<span class="h-card__badge"><?php echo esc_html( $ddn_cat->name ); ?></span>
		<?php endif; ?>
	</div>
	<h3 class="h-card__title"><a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
	<span class="meta h-card__meta"><?php echo esc_html( Format::time_ago() ); ?></span>
</article>
