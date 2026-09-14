<?php
/**
 * Decide si una visita a wp-admin debe rebotarse a la web. Lógica pura
 * (sin WordPress): RestrictAdminAccess la usa con los valores reales de
 * la petición; se puede probar con valores de prueba.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminAccessRule {

	/**
	 * Los perfiles de suscriptor son solo para la web: no publican ni
	 * borran nada, así que no tienen nada que hacer en wp-admin. Nunca
	 * rebota admin-post.php ni admin-ajax.php: son los mecanismos que
	 * usan el propio registro, login, «Mi cuenta» (incluido eliminar
	 * cuenta) y el login social — todos pasan por ahí.
	 */
	public static function should_block( bool $logged_in, bool $can_edit_posts, string $pagenow ): bool {
		if ( ! $logged_in || $can_edit_posts ) {
			return false;
		}

		return ! in_array( $pagenow, array( 'admin-post.php', 'admin-ajax.php' ), true );
	}
}
