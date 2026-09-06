<?php
/**
 * Tipo de campaña / cómo se sirve la creatividad.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

enum CampaignType: string {

	case Adsense   = 'adsense';   // Google AdSense: client + slot.
	case Gam       = 'gam';       // Google Ad Manager: creative = etiqueta GPT.
	case Html      = 'html';      // creative = HTML propio (banner de casa).
	case Image     = 'image';     // creative = URL de la imagen; target_url = destino.
	case Video     = 'video';     // creative = URL del MP4; target_url = destino.
	case Sponsored = 'sponsored'; // contenido patrocinado: creative = HTML de la ficha.

	public function label(): string {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: un método de enum SÍ tiene contexto de objeto (PHP 8.1+).
		return match ( $this ) {
			self::Adsense   => __( 'AdSense', 'ddn-suite' ),
			self::Gam       => __( 'Google Ad Manager', 'ddn-suite' ),
			self::Html      => __( 'HTML propio', 'ddn-suite' ),
			self::Image     => __( 'Imagen enlazada', 'ddn-suite' ),
			self::Video     => __( 'Vídeo', 'ddn-suite' ),
			self::Sponsored => __( 'Contenido patrocinado', 'ddn-suite' ),
		};
	}

	/** ¿Google sirve el anuncio directamente (no hay vista previa local)? */
	public function is_network(): bool {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: un método de enum SÍ tiene contexto de objeto (PHP 8.1+).
		return match ( $this ) {
			self::Adsense, self::Gam => true,
			default                  => false,
		};
	}

	/** @return list<string> Valores en el orden del selector. */
	public static function values(): array {
		return array_map( static fn ( self $c ): string => $c->value, self::cases() );
	}
}
