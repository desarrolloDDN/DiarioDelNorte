<?php
/**
 * Listas cerradas usadas por el registro, el perfil y el panel de admin:
 * departamentos de Colombia y tipos de documento de identidad.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Options {

	/** @return string[] */
	public static function departments(): array {
		return array(
			'Amazonas',
			'Antioquia',
			'Arauca',
			'Atlántico',
			'Bogotá D.C.',
			'Bolívar',
			'Boyacá',
			'Caldas',
			'Caquetá',
			'Casanare',
			'Cauca',
			'Cesar',
			'Chocó',
			'Córdoba',
			'Cundinamarca',
			'Guainía',
			'Guaviare',
			'Huila',
			'La Guajira',
			'Magdalena',
			'Meta',
			'Nariño',
			'Norte de Santander',
			'Putumayo',
			'Quindío',
			'Risaralda',
			'San Andrés y Providencia',
			'Santander',
			'Sucre',
			'Tolima',
			'Valle del Cauca',
			'Vaupés',
			'Vichada',
		);
	}

	/** @return array<string,string> código => etiqueta */
	public static function document_types(): array {
		return array(
			'CC'  => __( 'Cédula de ciudadanía', 'ddn-suite' ),
			'CE'  => __( 'Cédula de extranjería', 'ddn-suite' ),
			'TI'  => __( 'Tarjeta de identidad', 'ddn-suite' ),
			'PA'  => __( 'Pasaporte', 'ddn-suite' ),
			'NIT' => __( 'NIT', 'ddn-suite' ),
		);
	}

	/** @return string[] */
	public static function document_type_codes(): array {
		return array_keys( self::document_types() );
	}
}
