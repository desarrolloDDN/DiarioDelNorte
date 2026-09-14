<?php
/**
 * Siembra las páginas de cuentas de suscriptor —Registro, Ingresar, Mi
 * cuenta— si no existen, cada una con la plantilla del tema que le
 * corresponde por slug. Mismo patrón idempotente que
 * Content\ContactPageInstaller del tema.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Install;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PageInstaller {

	private const OPTION  = 'ddn_subscribers_pages_version';
	private const VERSION = '1';

	public const SLUG_REGISTER = 'registro';
	public const SLUG_LOGIN    = 'ingresar';
	public const SLUG_ACCOUNT  = 'mi-cuenta';

	/** slug => array{title:string,template:string} */
	private const PAGES = array(
		self::SLUG_REGISTER => array(
			'title'    => 'Regístrate',
			'template' => 'page-registro.php',
		),
		self::SLUG_LOGIN    => array(
			'title'    => 'Ingresar',
			'template' => 'page-ingresar.php',
		),
		self::SLUG_ACCOUNT  => array(
			'title'    => 'Mi cuenta',
			'template' => 'page-mi-cuenta.php',
		),
	);

	public function ensure(): void {
		if ( get_option( self::OPTION ) === self::VERSION ) {
			return;
		}

		foreach ( self::PAGES as $slug => $page ) {
			$this->create_if_missing( $slug, $page['title'], $page['template'] );
		}

		update_option( self::OPTION, self::VERSION, false );
	}

	public static function url( string $slug ): string {
		$page = get_page_by_path( $slug );

		return $page instanceof WP_Post ? (string) get_permalink( $page ) : '';
	}

	private function create_if_missing( string $slug, string $title, string $template ): void {
		if ( get_page_by_path( $slug ) instanceof WP_Post ) {
			return;
		}

		$id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return;
		}

		update_post_meta( $id, '_wp_page_template', $template );
	}
}
