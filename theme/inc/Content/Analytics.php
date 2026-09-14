<?php
/**
 * Etiqueta de Google Analytics (gtag.js). El ID de medición se edita en
 * Personalizar → Analítica (Google); en blanco, o con un formato que no
 * es el de Google (G-XXXXXXXXXX), no se encola nada.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Analytics {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue(): void {
		if ( is_admin() ) {
			return;
		}

		$id = (string) get_theme_mod( 'ddn_ga_id', 'G-K5BYSDHR57' );
		if ( ! preg_match( '/^G-[A-Z0-9]+$/', $id ) ) {
			return;
		}

		// async y en el <head> (no en el pie): como recomienda Google. Sin
		// versión a propósito: es un script externo con su propia caché de
		// Google, no de las nuestras — un «?ver=» de WordPress ahí no pinta nada.
		wp_enqueue_script(
			'ddn-ga',
			'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $id ),
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			array(
				'strategy'  => 'async',
				'in_footer' => false,
			)
		);

		wp_add_inline_script(
			'ddn-ga',
			'window.dataLayer=window.dataLayer||[];'
			. 'function gtag(){dataLayer.push(arguments);}'
			. "gtag('js', new Date());"
			. 'gtag(' . wp_json_encode( 'config' ) . ',' . wp_json_encode( $id ) . ');'
		);
	}
}
