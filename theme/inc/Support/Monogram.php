<?php
/**
 * Avatar de iniciales (SVG en data-URI) para autores sin foto propia.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Monogram {

	/** Data-URI de un SVG cuadrado con las iniciales del nombre. */
	public static function data_uri( string $name ): string {
		$initials = self::initials( $name );

		$svg = sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
			. '<rect width="100" height="100" fill="%1$s"/>'
			. '<text x="50" y="52" dy=".35em" text-anchor="middle" fill="%2$s" '
			. 'font-family="Iowan Old Style, Georgia, serif" font-size="44" font-weight="700">%3$s</text>'
			. '</svg>',
			'#17130f',
			'#ffffff',
			$initials
		);

		return 'data:image/svg+xml,' . rawurlencode( $svg );
	}

	private static function initials( string $name ): string {
		$name  = trim( wp_strip_all_tags( $name ) );
		$words = preg_split( '/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! $words ) {
			return '·';
		}

		$first = mb_substr( $words[0], 0, 1 );
		$last  = count( $words ) > 1 ? mb_substr( $words[ count( $words ) - 1 ], 0, 1 ) : '';

		return mb_strtoupper( $first . $last );
	}
}
