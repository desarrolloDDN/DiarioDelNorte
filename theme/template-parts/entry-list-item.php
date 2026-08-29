<?php
/**
 * Fila compacta de listado: miniatura + kicker + titular + fecha.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

use DiarioDelNorte\Support\Format;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_cat = Format::primary_category();
?>
<article <?php post_class( 'entry-row' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="entry-row__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'ddn-thumb', array( 'loading' => 'lazy' ) ); ?>
		</a>
	<?php endif; ?>
	<div class="entry-row__body">
		<h3 class="entry-row__title">
			<a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
		<span class="meta">
			<?php
			if ( $ddn_cat ) {
				echo esc_html( $ddn_cat->name ) . ' · ';
			}
			echo esc_html( Format::time_ago() );
			?>
		</span>
	</div>
</article>
