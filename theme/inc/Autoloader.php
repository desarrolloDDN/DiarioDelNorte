<?php
/**
 * Autoloader PSR-4 mínimo para el namespace del tema. Evita depender de
 * Composer dentro del tema (el .zip instalable no lleva vendor/).
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

namespace DiarioDelNorte;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra el autoload de `DiarioDelNorte\...` contra la carpeta `inc/`.
 */
final class Autoloader {

	private const PREFIX = 'DiarioDelNorte\\';

	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	public static function load( string $fqcn ): void {
		if ( ! str_starts_with( $fqcn, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $fqcn, strlen( self::PREFIX ) );
		$path     = DDN_THEME_DIR . 'inc/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
}
