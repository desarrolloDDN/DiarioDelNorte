<?php
/**
 * Añade la capacidad `ddn_view_stats` al rol Administrador: el panel de
 * Estadísticas de lectura queda atado únicamente a este rol.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Analytics\Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CapabilityInstaller {

	private const OPTION  = 'ddn_stats_caps_version';
	private const VERSION = '1';
	public const CAP      = 'ddn_view_stats';

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
