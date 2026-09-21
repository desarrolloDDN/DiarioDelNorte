<?php
/**
 * Contenedor mínimo del plugin: construye los servicios y los arranca.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite;

use DiarioDelNorte\Suite\Activity\Admin\ActivityPage;
use DiarioDelNorte\Suite\Activity\ActivityRecorder;
use DiarioDelNorte\Suite\Activity\ActivityRepository;
use DiarioDelNorte\Suite\Activity\AuthorReportRepository;
use DiarioDelNorte\Suite\Activity\Install\CapabilityInstaller as ActivityCapabilityInstaller;
use DiarioDelNorte\Suite\Admin\Menu;
use DiarioDelNorte\Suite\Analytics\Admin\ReadershipPage;
use DiarioDelNorte\Suite\Analytics\Install\CapabilityInstaller as StatsCapabilityInstaller;
use DiarioDelNorte\Suite\Analytics\PageviewRecorder;
use DiarioDelNorte\Suite\Analytics\PageviewRepository;
use DiarioDelNorte\Suite\Analytics\ReadershipRepository;
use DiarioDelNorte\Suite\Ads\Admin\CampaignsPage;
use DiarioDelNorte\Suite\Ads\AdRenderer;
use DiarioDelNorte\Suite\Ads\CampaignRepository;
use DiarioDelNorte\Suite\Ads\CampaignSelector;
use DiarioDelNorte\Suite\Ads\ClickController;
use DiarioDelNorte\Suite\Ads\Install\CapabilityInstaller as AdsCapabilityInstaller;
use DiarioDelNorte\Suite\Ads\StatsRepository;
use DiarioDelNorte\Suite\Ads\ZoneController;
use DiarioDelNorte\Suite\Calendar\Admin\CalendarPage;
use DiarioDelNorte\Suite\Calendar\CalendarRepository;
use DiarioDelNorte\Suite\Install\Installer;
use DiarioDelNorte\Suite\PrintEdition\EditionPostType;
use DiarioDelNorte\Suite\PrintEdition\EditionRepository;
use DiarioDelNorte\Suite\Radio\Admin\RadioPage;
use DiarioDelNorte\Suite\Radio\Install\CapabilityInstaller as RadioCapabilityInstaller;
use DiarioDelNorte\Suite\Radio\RadioController;
use DiarioDelNorte\Suite\Radio\RadioMeta;
use DiarioDelNorte\Suite\Radio\RadioPlayer;
use DiarioDelNorte\Suite\Radio\RadioSettings;
use DiarioDelNorte\Suite\Radio\RadioStats;
use DiarioDelNorte\Suite\Subscribers\Subscribers;
use DiarioDelNorte\Suite\Updater\GitHubUpdater;

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

		( new GitHubUpdater( DDN_SUITE_VERSION ) )->register();

		// Los módulos de Publicidad, Radio, Actividad y Estadísticas solo se abren al rol Administrador.
		( new AdsCapabilityInstaller() )->ensure();
		( new RadioCapabilityInstaller() )->ensure();
		( new ActivityCapabilityInstaller() )->ensure();
		( new StatsCapabilityInstaller() )->ensure();

		$activity_repo = new ActivityRepository();
		( new ActivityRecorder( $activity_repo ) )->register();

		$campaigns = new CampaignRepository();
		$stats     = new StatsRepository();
		$renderer  = new AdRenderer();
		$selector  = new CampaignSelector();

		( new ZoneController( $campaigns, $selector, $renderer, $stats ) )->register();
		( new ClickController( $campaigns, $stats ) )->register();

		( new PageviewRecorder() )->register();
		( new PageviewRepository() )->register();

		( new EditionPostType() )->register();
		( new EditionRepository() )->register();

		( new Subscribers() )->boot();

		$radio_settings = new RadioSettings();
		$radio_stats    = new RadioStats();
		$radio_stats->register();
		( new RadioPlayer( $radio_settings ) )->register();
		( new RadioController( $radio_settings, new RadioMeta(), $radio_stats ) )->register();

		if ( is_admin() ) {
			$calendar_page  = new CalendarPage( new CalendarRepository() );
			$campaigns_page = new CampaignsPage( $campaigns, $stats );
			$radio_page     = new RadioPage( $radio_settings, $radio_stats );
			$activity_page  = new ActivityPage( $activity_repo, new AuthorReportRepository() );
			$stats_page     = new ReadershipPage( new ReadershipRepository() );
			( new Menu( $calendar_page, $campaigns_page, $radio_page, $activity_page, $stats_page ) )->register();
		}
	}
}
