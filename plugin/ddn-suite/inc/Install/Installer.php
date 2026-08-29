<?php
/**
 * Creación y versionado del esquema propio del plugin.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Install;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {

	private const OPTION     = 'ddn_suite_db_version';
	private const DB_VERSION = '1';

	/** Hook de activación del plugin. */
	public static function activate(): void {
		self::migrate();
		// La regla de reescritura de /ddn-anuncio/clic/{id} ya se registró
		// en el hook `init` de esta misma petición (ver ClickController).
		flush_rewrite_rules();
	}

	/** Se ejecuta también en `init` por si el plugin se actualizó vía zip. */
	public static function maybe_upgrade(): void {
		if ( get_option( self::OPTION ) !== self::DB_VERSION ) {
			self::migrate();
		}
	}

	private static function migrate(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset   = Db::charset_collate();
		$campaigns = Db::table( Db::CAMPAIGNS );
		$events    = Db::table( Db::EVENTS );

		dbDelta(
			"CREATE TABLE {$campaigns} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				advertiser VARCHAR(191) NOT NULL DEFAULT '',
				zone VARCHAR(40) NOT NULL,
				type VARCHAR(20) NOT NULL DEFAULT 'image',
				active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
				priority SMALLINT NOT NULL DEFAULT 10,
				creative LONGTEXT NOT NULL,
				target_url TEXT NOT NULL,
				starts_at DATETIME NULL DEFAULT NULL,
				ends_at DATETIME NULL DEFAULT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY zone_active (zone, active),
				KEY priority (priority)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$events} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				campaign_id BIGINT UNSIGNED NOT NULL,
				kind VARCHAR(12) NOT NULL,
				event_day DATE NOT NULL,
				hits INT UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY campaign_kind_day (campaign_id, kind, event_day)
			) {$charset};"
		);

		update_option( self::OPTION, self::DB_VERSION, false );
	}
}
