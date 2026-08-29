<?php
/**
 * Encolado de estilos y scripts compilados por Vite, más la fuente
 * autoalojada «Sunlight Dreams» y Libre Franklin (Google Fonts).
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
		add_action( 'wp_head', array( $this, 'preconnect' ), 1 );
	}

	public function preconnect(): void {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}

	public function enqueue(): void {
		// Libre Franklin: cuerpo, interfaz y metadatos. «Sunlight Dreams»
		// (titulares) NO va aquí: la sirve el @font-face de app.css desde
		// assets/fonts/.
		wp_enqueue_style(
			'ddn-fonts',
			'https://fonts.googleapis.com/css2?family=Libre+Franklin:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- recurso externo.
		);

		$css = DDN_THEME_DIR . 'assets/dist/app.css';
		$js  = DDN_THEME_DIR . 'assets/dist/app.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'ddn-theme', DDN_THEME_URI . 'assets/dist/app.css', array( 'ddn-fonts' ), (string) filemtime( $css ) );
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
