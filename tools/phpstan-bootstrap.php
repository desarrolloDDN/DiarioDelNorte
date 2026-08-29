<?php
/**
 * Bootstrap para el análisis estático: define las constantes que WordPress
 * fijaría en tiempo de ejecución y que PHPStan no puede inferir.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'DDN_THEME_VERSION' ) || define( 'DDN_THEME_VERSION', '0.0.0' );
defined( 'DDN_THEME_DIR' ) || define( 'DDN_THEME_DIR', __DIR__ . '/' );
defined( 'DDN_THEME_URI' ) || define( 'DDN_THEME_URI', 'https://example.test/wp-content/themes/diario-del-norte/' );
defined( 'DDN_SUITE_VERSION' ) || define( 'DDN_SUITE_VERSION', '0.0.0' );
defined( 'DDN_SUITE_FILE' ) || define( 'DDN_SUITE_FILE', __DIR__ . '/ddn-suite.php' );
defined( 'DDN_SUITE_DIR' ) || define( 'DDN_SUITE_DIR', __DIR__ . '/' );
defined( 'DDN_SUITE_URL' ) || define( 'DDN_SUITE_URL', 'https://example.test/wp-content/plugins/ddn-suite/' );
