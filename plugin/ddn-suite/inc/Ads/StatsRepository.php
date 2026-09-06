<?php
/**
 * Registro y lectura de impresiones y clics por día.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Ads;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StatsRepository {

	public function record( int $campaign_id, string $kind ): void {
		if ( $campaign_id <= 0 || ! in_array( $kind, array( 'impression', 'click' ), true ) ) {
			return;
		}

		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (campaign_id, kind, event_day, hits) VALUES (%d, %s, %s, 1)
				 ON DUPLICATE KEY UPDATE hits = hits + 1',
				Db::table( Db::EVENTS ),
				$campaign_id,
				$kind,
				current_time( 'Y-m-d' )
			)
		);
	}

	/**
	 * @return array<int,array{impression:int,click:int}> totales por campaña.
	 */
	public function totals(): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT campaign_id, kind, SUM(hits) AS total FROM %i GROUP BY campaign_id, kind', Db::table( Db::EVENTS ) ),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$cid                                  = (int) $row['campaign_id'];
			$out[ $cid ]                        ??= array(
				'impression' => 0,
				'click'      => 0,
			);
			$out[ $cid ][ (string) $row['kind'] ] = (int) $row['total'];
		}

		return $out;
	}

	/**
	 * Impresiones y clics por día de una campaña, de la más reciente a la
	 * más antigua. Para el informe del anunciante.
	 *
	 * @return array<string,array{impression:int,click:int}> día => totales.
	 */
	public function daily( int $campaign_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT event_day, kind, SUM(hits) AS total FROM %i WHERE campaign_id = %d GROUP BY event_day, kind ORDER BY event_day DESC',
				Db::table( Db::EVENTS ),
				$campaign_id
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$day                                  = (string) $row['event_day'];
			$out[ $day ]                        ??= array(
				'impression' => 0,
				'click'      => 0,
			);
			$out[ $day ][ (string) $row['kind'] ] = (int) $row['total'];
		}

		return $out;
	}
}
