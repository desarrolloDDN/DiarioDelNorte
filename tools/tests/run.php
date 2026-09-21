<?php
/**
 * Arnés de pruebas mínimo, sin PHPUnit ni ninguna dependencia de
 * Composer: en este entorno de desarrollo no hay acceso a red para
 * instalarlas, y levantar el propio WordPress+MySQL para pruebas de
 * integración está fuera de alcance aquí.
 *
 * Por eso la lógica de seguridad del módulo de suscriptores se escribió
 * separada de WordPress (Subscribers\Support\*, y los métodos estáticos
 * puros de AccountController) — así se puede probar de verdad, contra el
 * código de producción real, sin arrancar nada.
 *
 * Uso: php tools/tests/run.php
 */

declare(strict_types=1);

// Los archivos de producción llevan `if (!defined('ABSPATH')) exit;` como
// defensa en profundidad; aquí solo hace falta que la constante exista.
define( 'ABSPATH', __DIR__ . '/' );

$root = dirname( __DIR__, 2 ) . '/plugin/ddn-suite/inc/Subscribers/';

require $root . 'Support/Crypto.php';
require $root . 'Support/RateLimitStore.php';
require $root . 'Support/RateLimiter.php';
require $root . 'Support/Validator.php';
require $root . 'Support/UsernameGenerator.php';
require $root . 'Support/FormDispatch.php';
require $root . 'Support/AdminAccessRule.php';
require $root . 'AccountController.php';
require dirname( __DIR__, 2 ) . '/plugin/ddn-suite/inc/Analytics/Support/DateRange.php';

// Stubs mínimos de WordPress: solo lo que Admin\XlsxWriter necesita para
// poder probar el escritor de verdad (produce un .xlsx real, no un CSV
// renombrado) sin arrancar WordPress.
if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string { // phpcs:ignore
		return $text;
	}
}
if ( ! function_exists( 'wp_tempnam' ) ) {
	function wp_tempnam( string $filename = '' ): string { // phpcs:ignore
		return (string) tempnam( sys_get_temp_dir(), 'ddn-test-' );
	}
}
if ( ! function_exists( 'wp_delete_file' ) ) {
	function wp_delete_file( string $file ): void { // phpcs:ignore
		if ( file_exists( $file ) ) {
			unlink( $file );
		}
	}
}
require $root . 'Admin/XlsxWriter.php';

$GLOBALS['ddn_test_stats'] = array(
	'pass' => 0,
	'fail' => 0,
);

function ddn_assert( bool $condition, string $label ): void {
	if ( $condition ) {
		++$GLOBALS['ddn_test_stats']['pass'];
		echo "  ok   - {$label}\n";
	} else {
		++$GLOBALS['ddn_test_stats']['fail'];
		echo "  FAIL - {$label}\n";
	}
}

function ddn_test( string $name, callable $fn ): void {
	echo "== {$name} ==\n";
	$fn();
}

foreach ( glob( __DIR__ . '/*Test.php' ) as $file ) {
	require $file;
}

$stats = $GLOBALS['ddn_test_stats'];
echo "\n{$stats['pass']} ok, {$stats['fail']} fallidas\n";

exit( $stats['fail'] > 0 ? 1 : 0 );
