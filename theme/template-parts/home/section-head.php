<?php
/**
 * Cabecera de sección de la portada: título con filete rojo. Si hay
 * enlace de la sección, el propio título es el acceso a más noticias de
 * esa categoría (sin «Ver más» aparte).
 *
 * @param array{title?:string,url?:string} $args
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_title = isset( $args['title'] ) ? (string) $args['title'] : '';
$ddn_url   = isset( $args['url'] ) ? (string) $args['url'] : '';
?>
<div class="home-section__head">
	<h2 class="home-section__title">
		<?php if ( '' !== $ddn_url ) : ?>
			<a href="<?php echo esc_url( $ddn_url ); ?>"><?php echo esc_html( $ddn_title ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $ddn_title ); ?>
		<?php endif; ?>
	</h2>
</div>
