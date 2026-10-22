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
	case Edictos         = 'edictos';

	public function label(): string {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: un método de enum SÍ tiene contexto de objeto (PHP 8.1+).
		return match ( $this ) {
			self::Header          => __( 'Cabecera — en toda la web, por encima de la fecha y las redes sociales', 'ddn-suite' ),
			self::Home            => __( 'Portada — bajo el menú principal (home, y en las notas tras la cinta «Lo último»)', 'ddn-suite' ),
			self::InArticleTop    => __( 'Inicio de la nota — bajo la firma del autor', 'ddn-suite' ),
			self::InArticle       => __( 'Dentro de la nota — tras el tercer párrafo', 'ddn-suite' ),
			self::InArticleBottom => __( 'Al final de la nota — al terminar el texto', 'ddn-suite' ),
			self::Edictos         => __( 'Edictos — espacio para avisos legales, al final de la nota bajo el texto', 'ddn-suite' ),
		};
	}

	/** Rótulo que acompaña al espacio en la web (los edictos no son «publicidad»). */
	public function caption(): string {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: un método de enum SÍ tiene contexto de objeto (PHP 8.1+).
		return self::Edictos === $this ? __( 'Edicto', 'ddn-suite' ) : __( 'Espacio publicitario', 'ddn-suite' );
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
