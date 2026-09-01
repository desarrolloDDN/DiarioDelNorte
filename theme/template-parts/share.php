<?php
/**
 * Botones de compartir de la nota: WhatsApp, Facebook, X y copiar enlace.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ddn_url   = get_permalink();
$ddn_title = get_the_title();
$u         = rawurlencode( $ddn_url );
$t         = rawurlencode( $ddn_title );

$ddn_links = array(
	'whatsapp' => array(
		'label' => __( 'Compartir por WhatsApp', 'diario-del-norte' ),
		'href'  => "https://api.whatsapp.com/send?text={$t}%20{$u}",
		'path'  => 'M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2Zm5.3 13.6c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-.4-.1-1-.3-1.6-.6-2.9-1.3-4.8-4.2-4.9-4.4-.2-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .7.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.4.4c-.2.2-.3.4-.1.7.2.3.9 1.4 1.9 2.3 1.3 1.1 2.3 1.5 2.6 1.6.3.1.5.1.7-.1l.9-1c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.3.1.2.1.8-.1 1.4Z',
	),
	'facebook' => array(
		'label' => __( 'Compartir en Facebook', 'diario-del-norte' ),
		'href'  => "https://www.facebook.com/sharer/sharer.php?u={$u}",
		'path'  => 'M14 8h2V5h-2c-1.9 0-3 1.4-3 3.2V10H9v3h2v8h3v-8h2.3l.7-3H14V8.5c0-.4.3-.5.7-.5H14Z',
	),
	'x'        => array(
		'label' => __( 'Compartir en X', 'diario-del-norte' ),
		'href'  => "https://twitter.com/intent/tweet?url={$u}&text={$t}",
		'path'  => 'M13.9 10.3 20 3h-1.6l-5.2 6.2L9 3H3.5l6.4 9.2L3.5 21H5l5.5-6.6L15 21h5.5l-6.6-10.7Zm-1.9 2.3-.6-.9-5-7.2h2.3l4.1 5.8.6.9 5.2 7.4h-2.3l-4.3-6Z',
	),
);
?>
<div class="article__share" data-share>
	<?php foreach ( $ddn_links as $ddn_net => $ddn_l ) : ?>
		<a class="article__share-btn" href="<?php echo esc_url( $ddn_l['href'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $ddn_l['label'] ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $ddn_l['path'] ); ?>"/></svg>
		</a>
	<?php endforeach; ?>
	<button type="button" class="article__share-btn article__share-copy" data-url="<?php echo esc_url( $ddn_url ); ?>" aria-label="<?php esc_attr_e( 'Copiar enlace', 'diario-del-norte' ); ?>">
		<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 14a3.5 3.5 0 0 0 5 0l3-3a3.5 3.5 0 0 0-5-5l-1 1M14 10a3.5 3.5 0 0 0-5 0l-3 3a3.5 3.5 0 0 0 5 5l1-1" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
	</button>
</div>
