<?php
/**
 * Lectura de las páginas vistas: entradas más leídas en una ventana de
 * tiempo (solo notas publicadas en los últimos 7 días). Se expone al tema por el filtro `ddn/most_read`.
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

	/** «Más leídas» del sitio solo incluye notas publicadas en estos últimos días. */
	private const MAX_AGE_DAYS = 7;

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
				"SELECT v.post_id FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish' AND p.post_date_gmt >= %s
				 WHERE v.bucket >= %s
				 GROUP BY v.post_id
				 ORDER BY SUM(v.hits) DESC
				 LIMIT %d",
				Db::table( Db::PAGEVIEWS ),
				gmdate( 'Y-m-d H:i:s', time() - self::MAX_AGE_DAYS * DAY_IN_SECONDS ),
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
