<?php
/**
 * Tarjeta con titular sobre la imagen (degradado oscuro): kicker de
 * sección y titular en blanco.
 *
 * @param array{size?:string} $args
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_size = isset( $args['size'] ) ? (string) $args['size'] : 'ddn-card';
$ddn_cat  = Format::primary_category();
?>
<article <?php post_class( 'cat-overlay' ); ?>>
	<a class="cat-overlay__link" href="<?php the_permalink(); ?>">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( $ddn_size, array( 'loading' => 'lazy' ) );
		} else {
			echo '<span class="cat-ph" aria-hidden="true"></span>';
		}
		?>
		<span class="cat-overlay__shade" aria-hidden="true"></span>
		<span class="cat-overlay__body">
			<?php if ( $ddn_cat ) : ?>
				<span class="cat-overlay__kicker"><?php echo esc_html( $ddn_cat->name ); ?></span>
			<?php endif; ?>
			<span class="cat-overlay__title"><?php the_title(); ?></span>
		</span>
	</a>
</article>
