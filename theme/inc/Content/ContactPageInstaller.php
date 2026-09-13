<?php
/**
 * Siembra la página «Contacto» (slug `contacto`, plantilla
 * page-contacto.php) si no existe, para que el botón «Contáctenos» del
 * pie tenga siempre a dónde enlazar sin que el editor tenga que crearla
 * ni asignar plantilla a mano.
 *
 * Idempotente: solo trabaja cuando cambia self::VERSION.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Content;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContactPageInstaller {

	private const OPTION  = 'ddn_contact_page_version';
	private const VERSION = '1';
	public const SLUG     = 'contacto';

	public function ensure(): void {
		if ( get_option( self::OPTION ) === self::VERSION ) {
			return;
		}

		$this->create_missing_page();

		update_option( self::OPTION, self::VERSION, false );
	}

	/** URL de la página de Contacto, o cadena vacía si aún no existe. */
	public static function url(): string {
		$page = get_page_by_path( self::SLUG );

		return $page instanceof WP_Post ? (string) get_permalink( $page ) : '';
	}

	private function create_missing_page(): void {
		if ( get_page_by_path( self::SLUG ) instanceof WP_Post ) {
			return;
		}

		$id = wp_insert_post(
			array(
				'post_title'   => __( 'Contacto', 'diario-del-norte' ),
				'post_name'    => self::SLUG,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return;
		}

		// El slug ya hace que WordPress use page-contacto.php por
		// jerarquía de plantillas; se fija además por metadato para que
		// siga aplicando aunque alguien cambie el slug más adelante.
		update_post_meta( $id, '_wp_page_template', 'page-contacto.php' );
	}
}
