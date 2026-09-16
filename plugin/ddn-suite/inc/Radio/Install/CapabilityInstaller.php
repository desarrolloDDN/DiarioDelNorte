<?php
/**
 * Añade la capacidad `ddn_manage_radio` al rol Administrador. Capacidad
 * propia (no se reutiliza `manage_options`) para que el módulo de Radio
 * quede atado únicamente a este rol, sin depender de qué otras
 * capacidades pueda tener un perfil por otro plugin.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Radio\Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CapabilityInstaller {

	private const OPTION  = 'ddn_radio_caps_version';
	private const VERSION = '1';
	public const CAP      = 'ddn_manage_radio';

	public function ensure(): void {
		if ( get_option( self::OPTION ) === self::VERSION ) {
			return;
		}

		$role = get_role( 'administrator' );
		if ( $role instanceof \WP_Role ) {
			$role->add_cap( self::CAP );
		}

		update_option( self::OPTION, self::VERSION, false );
	}
}
