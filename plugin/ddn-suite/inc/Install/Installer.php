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
	private const DB_VERSION = '5';
	private const FLUSH_FLAG = 'ddn_suite_flush_rewrite';

	/** Hook de activación del plugin. */
	public static function activate(): void {
		self::migrate();
		// La regla de reescritura de /ddn-anuncio/clic/{id} ya se registró
		// en el hook `init` de esta misma petición (ver ClickController).
		flush_rewrite_rules();
		delete_option( self::FLUSH_FLAG );
	}

	/** Se ejecuta también en `init` por si el plugin se actualizó vía zip. */
	public static function maybe_upgrade(): void {
		if ( get_option( self::OPTION ) !== self::DB_VERSION ) {
			self::migrate();
			// El CPT `ddn_edition` (URL /edicion-impresa/) se registra en
			// `init`; hay que refrescar las reglas de reescritura. Se marca
			// con una opción y se reintenta en cada carga hasta lograrlo,
			// por si la primera petición está cacheada o falla.
			update_option( self::FLUSH_FLAG, '1', false );
		}

		if ( '1' === get_option( self::FLUSH_FLAG ) ) {
			add_action( 'wp_loaded', array( self::class, 'flush' ), 99 );
		}
	}

	/** Refresca las reglas de reescritura una vez el CPT ya está registrado. */
	public static function flush(): void {
		flush_rewrite_rules();
		delete_option( self::FLUSH_FLAG );
	}

	private static function migrate(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset   = Db::charset_collate();
		$campaigns = Db::table( Db::CAMPAIGNS );
		$events    = Db::table( Db::EVENTS );
		$pageviews = Db::table( Db::PAGEVIEWS );

		dbDelta(
			"CREATE TABLE {$campaigns} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				advertiser VARCHAR(191) NOT NULL DEFAULT '',
				zone VARCHAR(40) NOT NULL,
				type VARCHAR(20) NOT NULL DEFAULT 'image',
				active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
				priority SMALLINT NOT NULL DEFAULT 10,
				weight SMALLINT UNSIGNED NOT NULL DEFAULT 1,
				category_slugs TEXT NOT NULL DEFAULT '',
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

		dbDelta(
			"CREATE TABLE {$pageviews} (
				post_id BIGINT UNSIGNED NOT NULL,
				bucket DATETIME NOT NULL,
				hits INT UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY  (post_id, bucket),
				KEY bucket (bucket)
			) {$charset};"
		);

		if ( ! wp_next_scheduled( 'ddn_suite_prune_pageviews' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'ddn_suite_prune_pageviews' );
		}

		update_option( self::OPTION, self::DB_VERSION, false );
	}
}
