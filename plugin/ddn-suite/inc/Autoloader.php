<?php
/**
 * Autoloader PSR-4 para el namespace del plugin.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader {

	private const PREFIX = 'DiarioDelNorte\\Suite\\';

	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	public static function load( string $fqcn ): void {
		if ( ! str_starts_with( $fqcn, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $fqcn, strlen( self::PREFIX ) );
		$path     = DDN_SUITE_DIR . 'inc/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
}
