<?php
/**
 * Menú «DDN Suite» en wp-admin, con las páginas de Calendario y Publicidad.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Admin;

use DiarioDelNorte\Suite\Activity\Admin\ActivityPage;
use DiarioDelNorte\Suite\Activity\Install\CapabilityInstaller as ActivityCapabilityInstaller;
use DiarioDelNorte\Suite\Analytics\Admin\ReadershipPage;
use DiarioDelNorte\Suite\Analytics\Install\CapabilityInstaller as StatsCapabilityInstaller;
use DiarioDelNorte\Suite\Ads\Admin\CampaignsPage;
use DiarioDelNorte\Suite\Ads\Install\CapabilityInstaller as AdsCapabilityInstaller;
use DiarioDelNorte\Suite\Calendar\Admin\CalendarPage;
use DiarioDelNorte\Suite\Radio\Admin\RadioPage;
use DiarioDelNorte\Suite\Radio\Install\CapabilityInstaller as RadioCapabilityInstaller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Menu {

	public function __construct(
		private readonly CalendarPage $calendar,
		private readonly CampaignsPage $campaigns,
		private readonly RadioPage $radio,
		private readonly ActivityPage $activity,
		private readonly ReadershipPage $readership,
	) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->calendar, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this->campaigns, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this->radio, 'enqueue' ) );
		$this->campaigns->register_hooks();
		$this->radio->register_hooks();
	}

	public function menu(): void {
		add_menu_page(
			__( 'DDN Suite', 'ddn-suite' ),
			__( 'DDN Suite', 'ddn-suite' ),
			'edit_others_posts',
			CalendarPage::SLUG,
			array( $this->calendar, 'render' ),
			'dashicons-megaphone',
			26
		);

		add_submenu_page(
			CalendarPage::SLUG,
			__( 'Calendario editorial', 'ddn-suite' ),
			__( 'Calendario', 'ddn-suite' ),
			'edit_others_posts',
			CalendarPage::SLUG,
			array( $this->calendar, 'render' )
		);

		add_submenu_page(
			CalendarPage::SLUG,
			__( 'Publicidad', 'ddn-suite' ),
			__( 'Publicidad', 'ddn-suite' ),
			AdsCapabilityInstaller::CAP,
			CampaignsPage::SLUG,
			array( $this->campaigns, 'render' )
		);

		add_submenu_page(
			CalendarPage::SLUG,
			__( 'Radio en vivo', 'ddn-suite' ),
			__( 'Radio', 'ddn-suite' ),
			RadioCapabilityInstaller::CAP,
			RadioPage::SLUG,
			array( $this->radio, 'render' )
		);

		add_submenu_page(
			CalendarPage::SLUG,
			__( 'Actividad', 'ddn-suite' ),
			__( 'Actividad', 'ddn-suite' ),
			ActivityCapabilityInstaller::CAP,
			ActivityPage::SLUG,
			array( $this->activity, 'render' )
		);

		add_submenu_page(
			CalendarPage::SLUG,
			__( 'Estadísticas de lectura', 'ddn-suite' ),
			__( 'Estadísticas', 'ddn-suite' ),
			StatsCapabilityInstaller::CAP,
			ReadershipPage::SLUG,
			array( $this->readership, 'render' )
		);
	}
}
