<?php
/**
 * Redes sociales de Diario del Norte: catálogo de iconos, URLs por
 * defecto y una lista <ul> reutilizable (cabecera y pie).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Social {

	/**
	 * Icono SVG (path, viewBox 0 0 24 24) y etiqueta de cada red.
	 *
	 * @return array<string,array{label:string,path:string}>
	 */
	public static function catalog(): array {
		return array(
			'facebook'  => array(
				'label' => __( 'Facebook', 'diario-del-norte' ),
				'path'  => 'M13 22v-8h2.9l.5-3.4H13V8.4c0-1 .3-1.6 1.7-1.6h1.8V3.8C17 3.7 16 3.6 14.8 3.6c-2.6 0-4.4 1.6-4.4 4.5v2.5H7.4V14h3V22H13Z',
			),
			'x'         => array(
				'label' => __( 'X', 'diario-del-norte' ),
				'path'  => 'M13.9 10.3 20 3h-1.6l-5.2 6.2L9 3H3.5l6.4 9.2L3.5 21H5l5.5-6.6L15 21h5.5l-6.6-10.7Zm-1.9 2.3-.6-.9-5-7.2h2.3l4.1 5.8.6.9 5.2 7.4h-2.3l-4.3-6Z',
			),
			'instagram' => array(
				'label' => __( 'Instagram', 'diario-del-norte' ),
				'path'  => 'M8 2h8a6 6 0 0 1 6 6v8a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6V8a6 6 0 0 1 6-6Zm0 2a4 4 0 0 0-4 4v8a4 4 0 0 0 4 4h8a4 4 0 0 0 4-4V8a4 4 0 0 0-4-4H8Zm4 3a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.2-3.1a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z',
			),
			'youtube'   => array(
				'label' => __( 'YouTube', 'diario-del-norte' ),
				'path'  => 'M23 12s0-3.5-.4-5.1a3 3 0 0 0-2.1-2.1C18.8 4.4 12 4.4 12 4.4s-6.8 0-8.5.4A3 3 0 0 0 1.4 6.9C1 8.5 1 12 1 12s0 3.5.4 5.1a3 3 0 0 0 2.1 2.1c1.7.4 8.5.4 8.5.4s6.8 0 8.5-.4a3 3 0 0 0 2.1-2.1C23 15.5 23 12 23 12ZM10 15.5v-7l6 3.5-6 3.5Z',
			),
			'whatsapp'  => array(
				'label' => __( 'WhatsApp', 'diario-del-norte' ),
				'path'  => 'M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2Zm5.3 13.6c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-.4-.1-1-.3-1.6-.6-2.9-1.3-4.8-4.2-4.9-4.4-.2-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .7.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.4.4c-.2.2-.3.4-.1.7.2.3.9 1.4 1.9 2.3 1.3 1.1 2.3 1.5 2.6 1.6.3.1.5.1.7-.1l.9-1c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.3.1.2.1.8-.1 1.4Z',
			),
		);
	}

	/**
	 * URLs por defecto (se muestran hasta que la redacción ponga las
	 * suyas en el Personalizador).
	 *
	 * @return array<string,string>
	 */
	public static function defaults(): array {
		return array(
			'facebook'  => 'https://www.facebook.com/DiarioDelNorte',
			'x'         => 'https://x.com/diariodelnorte',
			'instagram' => 'https://www.instagram.com/diariodelnorte',
			'youtube'   => 'https://www.youtube.com/@diariodelnorte',
			'whatsapp'  => '',
		);
	}

	/**
	 * Redes con URL (la guardada en el Personalizador o la de reserva).
	 *
	 * @return array<string,array{label:string,path:string,url:string}>
	 */
	public static function links(): array {
		$defaults = self::defaults();
		$out      = array();

		foreach ( self::catalog() as $key => $data ) {
			$url = (string) get_theme_mod( "ddn_social_{$key}", $defaults[ $key ] ?? '' );
			if ( '' === $url ) {
				continue;
			}
			$out[ $key ] = array(
				'label' => $data['label'],
				'path'  => $data['path'],
				'url'   => $url,
			);
		}

		return $out;
	}

	/** Lista `<ul>` de iconos enlazados. Cadena vacía si no hay ninguna. */
	public static function render( string $ul_class = '' ): string {
		$links = self::links();
		if ( array() === $links ) {
			return '';
		}

		$items = '';
		foreach ( $links as $link ) {
			$items .= sprintf(
				'<li><a href="%1$s" rel="noopener" aria-label="%2$s">'
				. '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="%3$s"/></svg>'
				. '</a></li>',
				esc_url( $link['url'] ),
				esc_attr( $link['label'] ),
				esc_attr( $link['path'] )
			);
		}

		return sprintf(
			'<ul%s>%s</ul>',
			'' !== $ul_class ? ' class="' . esc_attr( $ul_class ) . '"' : '',
			$items
		);
	}
}
