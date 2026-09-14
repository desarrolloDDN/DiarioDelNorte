<?php
/**
 * Artículos guardados por el suscriptor («Guardar» en la nota, listado
 * en Mi cuenta). Tabla propia, no usermeta: es una relación (quién
 * guardó qué y cuándo), no una preferencia de una sola vez.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SavedArticlesRepository {

	public function is_saved( int $user_id, int $post_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM %i WHERE user_id = %d AND post_id = %d',
				Db::table( Db::SAVED_ARTICLES ),
				$user_id,
				$post_id
			)
		);

		return null !== $found;
	}

	/** Alterna guardado/no guardado y devuelve el estado resultante. */
	public function toggle( int $user_id, int $post_id ): bool {
		if ( $this->is_saved( $user_id, $post_id ) ) {
			$this->remove( $user_id, $post_id );
			return false;
		}

		$this->save( $user_id, $post_id );
		return true;
	}

	public function save( int $user_id, int $post_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (user_id, post_id, saved_at) VALUES (%d, %d, %s)
				 ON DUPLICATE KEY UPDATE saved_at = VALUES(saved_at)',
				Db::table( Db::SAVED_ARTICLES ),
				$user_id,
				$post_id,
				current_time( 'mysql' )
			)
		);
	}

	public function remove( int $user_id, int $post_id ): void {
		global $wpdb;

		$wpdb->delete(
			Db::table( Db::SAVED_ARTICLES ),
			array(
				'user_id' => $user_id,
				'post_id' => $post_id,
			),
			array( '%d', '%d' )
		);
	}

	/** @return int[] IDs de entradas guardadas, más reciente primero. */
	public function post_ids( int $user_id, int $limit = 50 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT post_id FROM %i WHERE user_id = %d ORDER BY saved_at DESC LIMIT %d',
				Db::table( Db::SAVED_ARTICLES ),
				$user_id,
				$limit
			)
		);

		return array_map( 'intval', $rows );
	}
}
