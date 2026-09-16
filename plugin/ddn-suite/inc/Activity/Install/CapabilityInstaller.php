<?php
/**
 * Añade la capacidad `ddn_manage_activity_log` al rol Administrador.
 * Capacidad propia (no se reutiliza `manage_options`) para que el panel
 * de Actividad quede atado únicamente a este rol.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Activity\Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CapabilityInstaller {

	private const OPTION  = 'ddn_activity_caps_version';
	private const VERSION = '1';
	public const CAP      = 'ddn_manage_activity_log';

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
