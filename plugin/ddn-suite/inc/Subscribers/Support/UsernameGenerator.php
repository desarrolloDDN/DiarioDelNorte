<?php
/**
 * Genera un nombre de usuario a partir de un correo o nombre, resolviendo
 * colisiones con un sufijo numérico. Lógica pura: la comprobación de
 * existencia se inyecta como callback para poder probarlo sin
 * WordPress.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UsernameGenerator {

	/**
	 * @param callable(string):bool $exists Debe devolver true si ese
	 *                                       nombre de usuario ya existe.
	 */
	public static function generate( string $seed, callable $exists ): string {
		// Si el origen es un correo, se usa solo la parte antes de la @
		// (si no, un correo como base genera un usuario feo con el
		// dominio pegado, p. ej. «ana.perez.example.com»).
		$local = str_contains( $seed, '@' ) ? (string) strstr( $seed, '@', true ) : $seed;
		$base  = self::slugify( $local );
		if ( '' === $base ) {
			$base = 'usuario';
		}

		$candidate = $base;
		$suffix    = 1;
		while ( $exists( $candidate ) ) {
			++$suffix;
			$candidate = $base . $suffix;
		}

		return $candidate;
	}

	/**
	 * Minúsculas, sin acentos ni ñ, solo [a-z0-9.] — sin usar sanitize_title()
	 * de WordPress para que esta clase no dependa de él.
	 */
	public static function slugify( string $value ): string {
		$value = strtolower( trim( $value ) );
		$value = strtr(
			$value,
			array(
				'á' => 'a',
				'é' => 'e',
				'í' => 'i',
				'ó' => 'o',
				'ú' => 'u',
				'ñ' => 'n',
				'ü' => 'u',
			)
		);
		$value = (string) preg_replace( '/[^a-z0-9]+/', '.', $value );

		return trim( $value, '.' );
	}
}
