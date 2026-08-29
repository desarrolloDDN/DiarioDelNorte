<?php
/**
 * Formato de la creatividad de una campaña.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

enum CampaignType: string {

	case Image   = 'image';   // creative = URL de la imagen; target_url = destino.
	case Html    = 'html';    // creative = HTML propio (banner de casa).
	case Adsense = 'adsense'; // creative = snippet de AdSense/GAM.

	public function label(): string {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: un método de enum SÍ tiene contexto de objeto (PHP 8.1+).
		return match ( $this ) {
			self::Image   => __( 'Imagen enlazada', 'ddn-suite' ),
			self::Html    => __( 'HTML propio', 'ddn-suite' ),
			self::Adsense => __( 'AdSense / red', 'ddn-suite' ),
		};
	}

	/** @return array<string,string> */
	public static function options(): array {
		$out = array();
		foreach ( self::cases() as $case ) {
			$out[ $case->value ] = $case->label();
		}

		return $out;
	}
}
