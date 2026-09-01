<?php
/**
 * Nota principal de la portada de sección: imagen con distintivo y
 * titular debajo.
 *
 * @param array{label?:string} $args
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_label = isset( $args['label'] ) && '' !== (string) $args['label']
	? (string) $args['label']
	: __( 'Destacado', 'diario-del-norte' );
?>
<article <?php post_class( 'cat-feature' ); ?>>
	<a class="cat-feature__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'ddn-lead', array( 'loading' => 'lazy' ) );
		} else {
			echo '<span class="cat-ph" aria-hidden="true"></span>';
		}
		?>
		<span class="cat-feature__badge"><?php echo esc_html( $ddn_label ); ?></span>
	</a>
	<h2 class="cat-feature__title">
		<a class="headline-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h2>
</article>
