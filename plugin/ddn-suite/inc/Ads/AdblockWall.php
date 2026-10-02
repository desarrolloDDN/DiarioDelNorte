<?php
/**
 * Aviso a pantalla completa para quien navega con un bloqueador de
 * anuncios: pide desactivarlo para seguir leyendo. Se activa desde
 * DDN Suite → Publicidad. La detección es en el navegador (el sitio se
 * sirve desde caché: el servidor no sabe qué extensiones tiene cada
 * lector) con un elemento «cebo» que los bloqueadores ocultan; no la ve
 * el personal de redacción ni los robots de búsqueda.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdblockWall {

	public const OPTION = 'ddn_suite_adblock_wall';

	public static function enabled(): bool {
		return '1' === get_option( self::OPTION, '' );
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue(): void {
		if ( ! self::enabled() || is_admin() ) {
			return;
		}
		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			return;
		}

		$css = DDN_SUITE_DIR . 'assets/ads/adblock-wall.css';
		$js  = DDN_SUITE_DIR . 'assets/ads/adblock-wall.js';

		wp_enqueue_style(
			'ddn-suite-adblock-wall',
			DDN_SUITE_URL . 'assets/ads/adblock-wall.css',
			array(),
			file_exists( $css ) ? (string) filemtime( $css ) : DDN_SUITE_VERSION
		);
		wp_enqueue_script(
			'ddn-suite-adblock-wall',
			DDN_SUITE_URL . 'assets/ads/adblock-wall.js',
			array(),
			file_exists( $js ) ? (string) filemtime( $js ) : DDN_SUITE_VERSION,
			true
		);
		wp_localize_script(
			'ddn-suite-adblock-wall',
			'ddnAdblock',
			array(
				'title'  => __( 'Desactiva tu bloqueador de anuncios', 'ddn-suite' ),
				'text'   => __( 'Diario del Norte se sostiene con la publicidad que ves en estas páginas. Para seguir leyendo, desactiva tu bloqueador de anuncios en este sitio y recarga la página.', 'ddn-suite' ),
				'button' => __( 'Ya lo desactivé, recargar', 'ddn-suite' ),
			)
		);
	}
}
