<?php
/**
 * Cabecera de sección de la portada: título con filete rojo + «Ver más».
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
	<h2 class="home-section__title"><?php echo esc_html( $ddn_title ); ?></h2>
	<?php if ( '' !== $ddn_url ) : ?>
		<a class="home-section__more" href="<?php echo esc_url( $ddn_url ); ?>"><?php esc_html_e( 'Ver más', 'diario-del-norte' ); ?> &rarr;</a>
	<?php endif; ?>
</div>
