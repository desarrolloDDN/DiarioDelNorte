<?php
/**
 * Bootstrap del tema Diario del Norte.
 *
 * Define las constantes del tema, registra el autoloader y arranca
 * DiarioDelNorte\Theme. No contiene lógica: toda vive en `inc/`.
 *
 * @package DiarioDelNorte
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DDN_THEME_VERSION', '0.1.0' );
define( 'DDN_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'DDN_THEME_URI', trailingslashit( get_template_directory_uri() ) );

require_once DDN_THEME_DIR . 'inc/Autoloader.php';
DiarioDelNorte\Autoloader::register();

DiarioDelNorte\Theme::instance()->boot();
