<?php
/**
 * Encolado de estilos y scripts compilados por Vite. Las fuentes
 * (Majerit Headline y Libre Franklin) van autoalojadas y referenciadas
 * desde app.css vía @font-face; no hay ninguna petición
 * a un host externo.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'preload_fonts' ), 2 );
	}

	/** Precarga las fuentes críticas para el primer pintado (cuerpo y titular). */
	public function preload_fonts(): void {
		foreach ( array( 'libre-franklin.woff2', 'majerit-headline.woff2' ) as $file ) {
			printf(
				'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin>' . "\n",
				esc_url( DDN_THEME_URI . 'assets/dist/fonts/' . $file )
			);
		}
	}

	public function enqueue(): void {
		$css = DDN_THEME_DIR . 'assets/dist/app.css';
		$js  = DDN_THEME_DIR . 'assets/dist/app.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'ddn-theme', DDN_THEME_URI . 'assets/dist/app.css', array(), (string) filemtime( $css ) );
		}

		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'ddn-theme', DDN_THEME_URI . 'assets/dist/app.js', array(), (string) filemtime( $js ), true );
			wp_script_add_data( 'ddn-theme', 'type', 'module' );
		}

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
