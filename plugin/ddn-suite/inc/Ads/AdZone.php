<?php
/**
 * Zonas de anuncio. Deben coincidir con DiarioDelNorte\Support\Ads::ZONES
 * del tema (contrato por cadena literal, sin acoplamiento de código).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

enum AdZone: string {

	case Header          = 'header';
	case Home            = 'home';
	case InArticleTop    = 'in-article-top';
	case InArticle       = 'in-article';
	case InArticleBottom = 'in-article-bottom';

	public function label(): string {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: un método de enum SÍ tiene contexto de objeto (PHP 8.1+).
		return match ( $this ) {
			self::Header          => __( 'Cabecera (bajo el logotipo)', 'ddn-suite' ),
			self::Home            => __( 'Portada (tras la retícula de apertura)', 'ddn-suite' ),
			self::InArticleTop    => __( 'Inicio del artículo', 'ddn-suite' ),
			self::InArticle       => __( 'Dentro del artículo (tras el 3.er párrafo)', 'ddn-suite' ),
			self::InArticleBottom => __( 'Final del artículo', 'ddn-suite' ),
		};
	}

	/** @return array<string,string> value => label */
	public static function options(): array {
		$out = array();
		foreach ( self::cases() as $case ) {
			$out[ $case->value ] = $case->label();
		}

		return $out;
	}
}
