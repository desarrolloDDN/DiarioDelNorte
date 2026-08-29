<?php
/**
 * Contenedor mínimo del plugin: construye los servicios y los arranca.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite;

use DiarioDelNorte\Suite\Admin\Menu;
use DiarioDelNorte\Suite\Ads\Admin\CampaignsPage;
use DiarioDelNorte\Suite\Ads\AdRenderer;
use DiarioDelNorte\Suite\Ads\CampaignRepository;
use DiarioDelNorte\Suite\Ads\ClickController;
use DiarioDelNorte\Suite\Ads\StatsRepository;
use DiarioDelNorte\Suite\Ads\ZoneController;
use DiarioDelNorte\Suite\Calendar\Admin\CalendarPage;
use DiarioDelNorte\Suite\Calendar\CalendarRepository;
use DiarioDelNorte\Suite\Install\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {}

	public static function instance(): Plugin {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		add_action( 'init', array( Installer::class, 'maybe_upgrade' ) );

		$campaigns = new CampaignRepository();
		$stats     = new StatsRepository();
		$renderer  = new AdRenderer();

		( new ZoneController( $campaigns, $renderer, $stats ) )->register();
		( new ClickController( $campaigns, $stats ) )->register();

		if ( is_admin() ) {
			$calendar_page  = new CalendarPage( new CalendarRepository() );
			$campaigns_page = new CampaignsPage( $campaigns, $stats );
			( new Menu( $calendar_page, $campaigns_page ) )->register();
		}
	}
}
