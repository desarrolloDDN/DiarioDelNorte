<?php
/**
 * Guarda y consulta la bitácora de eventos clave: quién inició/cerró
 * sesión, quién publicó/editó/borró una nota y quién dio de alta, cambió
 * el rol o borró a otro usuario. Guarda una foto del usuario y del
 * objeto (nombre, no solo el ID) para que la fila se siga leyendo bien
 * aunque ese usuario o esa nota se borren después.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Activity;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivityRepository {

	/**
	 * @param array{user_id?:int,user_login?:string,user_role?:string,object_type?:string,object_id?:int,object_label?:string,ip?:string} $data
	 */
	public function log( string $event_type, array $data = array() ): void {
		global $wpdb;

		$wpdb->insert(
			Db::table( Db::ACTIVITY_LOG ),
			array(
				'event_type'   => $event_type,
				'user_id'      => (int) ( $data['user_id'] ?? 0 ),
				'user_login'   => (string) ( $data['user_login'] ?? '' ),
				'user_role'    => (string) ( $data['user_role'] ?? '' ),
				'object_type'  => (string) ( $data['object_type'] ?? '' ),
				'object_id'    => (int) ( $data['object_id'] ?? 0 ),
				'object_label' => (string) ( $data['object_label'] ?? '' ),
				'ip'           => (string) ( $data['ip'] ?? '' ),
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * @param array{event_type?:string,user?:string,from?:string,to?:string} $filters
	 * @return array<int,array<string,mixed>>
	 */
	public function query( array $filters, int $per_page, int $page ): array {
		global $wpdb;

		[$where, $args] = $this->where( $filters );
		$offset         = max( 0, ( $page - 1 ) * $per_page );
		$call_args      = array_merge( array( Db::table( Db::ACTIVITY_LOG ) ), $args, array( $per_page, $offset ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- el número de placeholders y el de valores en $call_args siempre coincide: uno por filtro activo (ver where()) más los tres fijos.
				"SELECT * FROM %i {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where solo trae cláusulas fijas con un placeholder por filtro activo, ver where().
				$call_args
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * @param array{event_type?:string,user?:string,from?:string,to?:string} $filters
	 */
	public function count( array $filters ): int {
		global $wpdb;

		[$where, $args] = $this->where( $filters );
		$call_args      = array_merge( array( Db::table( Db::ACTIVITY_LOG ) ), $args );

		return (int) $wpdb->get_var(
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- idem query().
				"SELECT COUNT(*) FROM %i {$where}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- idem query().
				$call_args
			)
		);
	}

	/** Borra lo anterior a `$days` días (WP-Cron diario). */
	public function prune( int $days ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE created_at < %s',
				Db::table( Db::ACTIVITY_LOG ),
				gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS )
			)
		);
	}

	/** @return array<string,string> clave de evento => etiqueta legible. */
	public function event_types(): array {
		return array(
			'login'             => __( 'Inicio de sesión', 'ddn-suite' ),
			'logout'            => __( 'Cierre de sesión', 'ddn-suite' ),
			'post_published'    => __( 'Nota publicada', 'ddn-suite' ),
			'post_updated'      => __( 'Nota editada', 'ddn-suite' ),
			'post_trashed'      => __( 'Nota enviada a la papelera', 'ddn-suite' ),
			'post_deleted'      => __( 'Nota borrada definitivamente', 'ddn-suite' ),
			'user_created'      => __( 'Usuario creado', 'ddn-suite' ),
			'user_role_changed' => __( 'Rol de usuario cambiado', 'ddn-suite' ),
			'user_deleted'      => __( 'Usuario borrado', 'ddn-suite' ),
		);
	}

	/**
	 * @param array{event_type?:string,user?:string,from?:string,to?:string} $filters
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function where( array $filters ): array {
		global $wpdb;

		$clauses = array();
		$args    = array();

		if ( '' !== ( $filters['event_type'] ?? '' ) ) {
			$clauses[] = 'event_type = %s';
			$args[]    = $filters['event_type'];
		}
		if ( '' !== ( $filters['user'] ?? '' ) ) {
			$clauses[] = 'user_login LIKE %s';
			$args[]    = '%' . $wpdb->esc_like( $filters['user'] ) . '%';
		}
		if ( '' !== ( $filters['from'] ?? '' ) ) {
			$clauses[] = 'created_at >= %s';
			$args[]    = $filters['from'] . ' 00:00:00';
		}
		if ( '' !== ( $filters['to'] ?? '' ) ) {
			$clauses[] = 'created_at <= %s';
			$args[]    = $filters['to'] . ' 23:59:59';
		}

		$where = array() !== $clauses ? 'WHERE ' . implode( ' AND ', $clauses ) : '';

		return array( $where, $args );
	}
}
