<?php
/**
 * Plugin Name:       DDN Suite
 * Plugin URI:        https://github.com/desarrolloDDN/DiarioDelNorte
 * Description:        Gestor de publicidad y calendario de noticias publicadas para Diario del Norte. Complementa al tema; el tema solo marca las zonas de anuncio.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Sistema Cardenal S.A.S.
 * Author URI:        https://diariodelnorte.net
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ddn-suite
 * Domain Path:       /languages
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DDN_SUITE_VERSION', '0.1.0' );
define( 'DDN_SUITE_FILE', __FILE__ );
define( 'DDN_SUITE_DIR', plugin_dir_path( __FILE__ ) );
define( 'DDN_SUITE_URL', plugin_dir_url( __FILE__ ) );

require_once DDN_SUITE_DIR . 'inc/Autoloader.php';
Autoloader::register();

register_activation_hook( __FILE__, array( Install\Installer::class, 'activate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain( 'ddn-suite', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		Plugin::instance()->boot();
	}
);
