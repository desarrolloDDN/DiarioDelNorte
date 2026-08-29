<?php
/**
 * Punto de extensión para las zonas de publicidad. El tema solo marca
 * dónde van; el plugin «DDN Suite» decide qué se muestra enganchándose a
 * la acción `ddn/ad_zone`. Si el plugin no está activo no se imprime nada.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Ads {

	/** Zonas que el tema expone (deben coincidir con DDN Suite). */
	public const ZONES = array( 'header', 'home', 'in-article-top', 'in-article', 'in-article-bottom' );

	public static function zone( string $zone ): void {
		if ( ! in_array( $zone, self::ZONES, true ) ) {
			return;
		}

		/**
		 * DDN Suite se engancha aquí para renderizar la campaña activa.
		 *
		 * @param string $zone Identificador de la zona.
		 */
		do_action( 'ddn/ad_zone', $zone );
	}
}
