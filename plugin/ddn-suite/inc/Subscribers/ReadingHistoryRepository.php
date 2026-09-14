<?php
/**
 * Historial de lectura del suscriptor (qué notas leyó y cuándo).
 * Un vuelve a leer solo actualiza la fecha, no crea una fila nueva.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReadingHistoryRepository {

	public function record( int $user_id, int $post_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (user_id, post_id, read_at) VALUES (%d, %d, %s)
				 ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)',
				Db::table( Db::READING_HISTORY ),
				$user_id,
				$post_id,
				current_time( 'mysql' )
			)
		);
	}

	/** @return int[] IDs de entradas leídas, más reciente primero. */
	public function post_ids( int $user_id, int $limit = 50 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT post_id FROM %i WHERE user_id = %d ORDER BY read_at DESC LIMIT %d',
				Db::table( Db::READING_HISTORY ),
				$user_id,
				$limit
			)
		);

		return array_map( 'intval', $rows );
	}

	public function clear( int $user_id ): void {
		global $wpdb;

		$wpdb->delete( Db::table( Db::READING_HISTORY ), array( 'user_id' => $user_id ), array( '%d' ) );
	}

	/** Quita una sola nota del historial (por privacidad: el suscriptor decide qué se olvida). */
	public function remove( int $user_id, int $post_id ): void {
		global $wpdb;

		$wpdb->delete(
			Db::table( Db::READING_HISTORY ),
			array(
				'user_id' => $user_id,
				'post_id' => $post_id,
			),
			array( '%d', '%d' )
		);
	}
}
