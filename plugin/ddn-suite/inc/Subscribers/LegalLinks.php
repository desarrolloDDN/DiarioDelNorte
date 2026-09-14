<?php
/**
 * Enlaces a las páginas legales del tema (mismos slugs que
 * theme/footer.php: 'terminos-y-condiciones', 'politica-de-tratamiento-de-datos').
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegalLinks {

	/** @return array{terms:string,privacy:string} */
	public static function links(): array {
		return array(
			'terms'   => home_url( '/terminos-y-condiciones/' ),
			'privacy' => home_url( '/politica-de-tratamiento-de-datos/' ),
		);
	}
}
