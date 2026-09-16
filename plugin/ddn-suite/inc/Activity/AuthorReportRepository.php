<?php
/**
 * Reporte de publicaciones por autor: cuántas notas publicó cada quien
 * en un rango de fechas. Se lee directo de `wp_posts` — el autor de una
 * nota ya vive ahí, no hace falta duplicarlo en la bitácora.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Activity;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuthorReportRepository {

	/**
	 * @return array<int,array{author_id:int,name:string,total:int,first_at:string,last_at:string}>
	 */
	public function summary( string $from, string $to ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_author, COUNT(*) AS total, MIN(post_date) AS first_at, MAX(post_date) AS last_at
				 FROM {$wpdb->posts}
				 WHERE post_type = 'post' AND post_status = 'publish'
				   AND post_date >= %s AND post_date <= %s
				 GROUP BY post_author
				 ORDER BY total DESC",
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$author_id = (int) $row['post_author'];
			$author    = get_userdata( $author_id );

			$out[] = array(
				'author_id' => $author_id,
				'name'      => $author instanceof WP_User ? $author->display_name : __( '(usuario borrado)', 'ddn-suite' ),
				'total'     => (int) $row['total'],
				'first_at'  => (string) $row['first_at'],
				'last_at'   => (string) $row['last_at'],
			);
		}

		return $out;
	}
}
