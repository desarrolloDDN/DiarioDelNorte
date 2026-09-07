<?php
/**
 * Escuchas de la radio: contador por día y emisora. Sin PII — solo
 * «arranques» (cuántas veces se pulsó play) y «segundos» acumulados
 * (latido cada 30 s mientras suena).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Radio;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RadioStats {

	/** Segundos que suma cada latido del reproductor. */
	public const BEAT_SECONDS = 30;

	public function register(): void {
		add_action( 'ddn_suite_prune_pageviews', array( $this, 'prune' ) );
	}

	public function record( int $station, string $kind ): void {
		if ( $station < 0 || $station > 999 ) {
			return;
		}

		$starts  = 'start' === $kind ? 1 : 0;
		$seconds = 'beat' === $kind ? self::BEAT_SECONDS : 0;
		if ( 0 === $starts && 0 === $seconds ) {
			return;
		}

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (play_day, station, starts, seconds) VALUES (%s, %d, %d, %d)
				 ON DUPLICATE KEY UPDATE starts = starts + %d, seconds = seconds + %d',
				Db::table( Db::RADIO_PLAYS ),
				current_time( 'Y-m-d' ),
				$station,
				$starts,
				$seconds,
				$starts,
				$seconds
			)
		);
	}

	/**
	 * Resumen por emisora de los últimos $days días.
	 *
	 * @return array<int,array{starts:int,seconds:int}>
	 */
	public function summary( int $days = 30 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT station, SUM(starts) AS starts, SUM(seconds) AS seconds
				 FROM %i WHERE play_day >= %s GROUP BY station',
				Db::table( Db::RADIO_PLAYS ),
				gmdate( 'Y-m-d', time() - max( 1, $days ) * DAY_IN_SECONDS )
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['station'] ] = array(
				'starts'  => (int) $row['starts'],
				'seconds' => (int) $row['seconds'],
			);
		}

		return $out;
	}

	/** Borra las filas de más de 120 días (WP-Cron diario). */
	public function prune(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE play_day < %s',
				Db::table( Db::RADIO_PLAYS ),
				gmdate( 'Y-m-d', time() - 120 * DAY_IN_SECONDS )
			)
		);
	}
}
