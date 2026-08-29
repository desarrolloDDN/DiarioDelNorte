<?php
/**
 * Menú «DDN Suite» en wp-admin, con las páginas de Calendario y Publicidad.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Admin;

use DiarioDelNorte\Suite\Ads\Admin\CampaignsPage;
use DiarioDelNorte\Suite\Calendar\Admin\CalendarPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Menu {

	public function __construct(
		private readonly CalendarPage $calendar,
		private readonly CampaignsPage $campaigns,
	) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->calendar, 'enqueue' ) );
		add_action( 'admin_post_' . CampaignsPage::ACTION, array( $this->campaigns, 'handle_save' ) );
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
			'manage_options',
			CampaignsPage::SLUG,
			array( $this->campaigns, 'render' )
		);
	}
}
