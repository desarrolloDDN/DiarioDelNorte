<?php
/**
 * Envoltura fina sobre $wpdb: nombres de tabla del plugin y helpers de
 * consulta con sentencias preparadas.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Db {

	public const CAMPAIGNS = 'ddn_ad_campaigns';
	public const EVENTS    = 'ddn_ad_events';

	public static function table( string $name ): string {
		global $wpdb;

		return $wpdb->prefix . $name;
	}

	public static function charset_collate(): string {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}
}
