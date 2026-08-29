<?php
/**
 * Lectura de las páginas vistas: entradas más leídas en una ventana de
 * tiempo. Se expone al tema por el filtro `ddn/most_read`.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Analytics;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PageviewRepository {

	public function register(): void {
		add_filter( 'ddn/most_read', array( $this, 'most_read' ), 10, 2 );
	}

	/**
	 * @param int[] $ids   Valor previo (se ignora).
	 * @param int   $limit Cuántas entradas devolver.
	 * @return int[] IDs de entradas publicadas, de más a menos leídas.
	 */
	public function most_read( array $ids, int $limit ): array {
		return $this->top( 24, max( 1, $limit ) );
	}

	/**
	 * @return int[]
	 */
	public function top( int $hours, int $limit ): array {
		global $wpdb;

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT post_id FROM %i
				 WHERE bucket >= %s
				 GROUP BY post_id
				 ORDER BY SUM(hits) DESC
				 LIMIT %d',
				Db::table( Db::PAGEVIEWS ),
				gmdate( 'Y-m-d H:00:00', time() - $hours * HOUR_IN_SECONDS - HOUR_IN_SECONDS ),
				$limit * 3
			)
		);

		$out = array();
		foreach ( (array) $rows as $id ) {
			if ( 'publish' === get_post_status( (int) $id ) ) {
				$out[] = (int) $id;
			}
			if ( count( $out ) >= $limit ) {
				break;
			}
		}

		return $out;
	}
}
