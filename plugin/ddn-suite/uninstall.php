<?php
/**
 * Limpieza al desinstalar DDN Suite.
 *
 * IMPORTANTE: por defecto NO se borran las campañas ni las estadísticas.
 * Son datos del negocio (contratos de anunciantes, informes) y algunos
 * gestores de plugins «actualizan» borrando y reinstalando el plugin
 * —lo que ejecutaría este archivo—. Manteniendo los datos, una
 * actualización nunca puede llevarse las campañas por delante.
 *
 * Para vaciarlo todo a propósito: antes de desinstalar, ejecuta
 * `update_option( 'ddn_suite_purge', '1' );` (o con WP-CLI:
 * `wp option update ddn_suite_purge 1`).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Se puede recrear solo; no es dato de negocio.
wp_clear_scheduled_hook( 'ddn_suite_prune_pageviews' );

if ( '1' !== (string) get_option( 'ddn_suite_purge' ) ) {
	return;
}

global $wpdb;

foreach ( array( 'ddn_ad_campaigns', 'ddn_ad_events', 'ddn_pageviews', 'ddn_radio_plays' ) as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}

delete_option( 'ddn_suite_db_version' );
delete_option( 'ddn_suite_radio' );
delete_option( 'ddn_suite_purge' );

flush_rewrite_rules();
