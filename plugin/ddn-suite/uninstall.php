<?php
/**
 * Limpieza al desinstalar el plugin: elimina tablas y opciones propias.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

foreach ( array( 'ddn_ad_campaigns', 'ddn_ad_events' ) as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}

delete_option( 'ddn_suite_db_version' );

flush_rewrite_rules();
