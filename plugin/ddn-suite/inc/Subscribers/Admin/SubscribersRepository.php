<?php
/**
 * Consultas del panel «Suscriptores»: listado paginado con búsqueda y
 * filtros, y exportación de todo lo que cumpla el filtro activo (no solo
 * la página visible).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Admin;

use DiarioDelNorte\Suite\Subscribers\ProfileRepository;
use WP_User;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SubscribersRepository {

	public function __construct( private readonly ProfileRepository $profiles ) {}

	/**
	 * @param array{search?:string,department?:string,origin?:string,whatsapp_only?:bool} $filters
	 */
	public function query( array $filters, int $per_page = 20, int $page = 1 ): WP_User_Query {
		$args = $this->base_args( $filters );

		$args['number']  = $per_page;
		$args['offset']  = max( 0, ( $page - 1 ) * $per_page );
		$args['orderby'] = 'registered';
		$args['order']   = 'DESC';

		return new WP_User_Query( $args );
	}

	/**
	 * @param array{search?:string,department?:string,origin?:string,whatsapp_only?:bool} $filters
	 * @return WP_User[] TODOS los que cumplen el filtro (para exportar), sin paginar.
	 */
	public function all_matching( array $filters ): array {
		$args           = $this->base_args( $filters );
		$args['number'] = -1;
		$args['fields'] = 'all';

		$query = new WP_User_Query( $args );

		/** @var WP_User[] */
		return $query->get_results();
	}

	/** Solo los departamentos que de verdad tienen al menos un suscriptor. */
	public function departments_in_use(): array {
		global $wpdb;

		$role_key = $wpdb->get_blog_prefix() . 'capabilities';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- distinct de un valor de usermeta, sin equivalente en WP_User_Query.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT dep.meta_value
				 FROM {$wpdb->usermeta} dep
				 INNER JOIN {$wpdb->usermeta} role_meta
					ON role_meta.user_id = dep.user_id AND role_meta.meta_key = %s
				 WHERE dep.meta_key = 'ddn_sub_department'
				   AND dep.meta_value != ''
				   AND role_meta.meta_value LIKE %s
				 ORDER BY dep.meta_value ASC",
				$role_key,
				'%"subscriber"%'
			)
		);

		return array_values( array_filter( array_map( 'strval', (array) $rows ) ) );
	}

	public function count( array $filters ): int {
		$args                = $this->base_args( $filters );
		$args['number']      = -1;
		$args['fields']      = 'ID';
		$args['count_total'] = true;

		$query = new WP_User_Query( $args );

		return (int) $query->get_total();
	}

	/** @return array<string,mixed> Una fila lista para mostrar/exportar. */
	public function row( WP_User $user ): array {
		$profile = $this->profiles->get( (int) $user->ID );

		return array(
			'id'                  => (int) $user->ID,
			'name'                => $user->display_name,
			'email'               => $user->user_email,
			'phone'               => $profile['phone'],
			'department'          => $profile['department'],
			'city'                => $profile['city'],
			'address'             => $profile['address'],
			'doc_type'            => $profile['doc_type'],
			'doc_number'          => $profile['doc_number'],
			'consent_email'       => (bool) $profile['consent_email'],
			'consent_whatsapp'    => (bool) $profile['consent_whatsapp'],
			'terms_accepted_at'   => $profile['terms_accepted_at'],
			'privacy_accepted_at' => $profile['privacy_accepted_at'],
			'origin'              => $profile['origin'],
			'registered_at'       => $user->user_registered,
		);
	}

	/**
	 * @param array{search?:string,department?:string,origin?:string,whatsapp_only?:bool} $filters
	 * @return array<string,mixed>
	 */
	private function base_args( array $filters ): array {
		$args = array(
			'role' => 'subscriber',
		);

		$search = trim( (string) ( $filters['search'] ?? '' ) );
		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_email', 'display_name', 'user_login' );
		}

		$meta_query = array();

		$department = trim( (string) ( $filters['department'] ?? '' ) );
		if ( '' !== $department ) {
			$meta_query[] = array(
				'key'     => 'ddn_sub_department',
				'value'   => $department,
				'compare' => '=',
			);
		}

		$origin = trim( (string) ( $filters['origin'] ?? '' ) );
		if ( '' !== $origin ) {
			if ( 'direct' === $origin ) {
				// Las cuentas creadas antes de este módulo no tienen el
				// meta de origen: cuentan como «directo».
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => 'ddn_sub_origin',
						'value'   => 'direct',
						'compare' => '=',
					),
					array(
						'key'     => 'ddn_sub_origin',
						'compare' => 'NOT EXISTS',
					),
				);
			} else {
				$meta_query[] = array(
					'key'     => 'ddn_sub_origin',
					'value'   => $origin,
					'compare' => '=',
				);
			}
		}

		if ( ! empty( $filters['whatsapp_only'] ) ) {
			$meta_query[] = array(
				'key'     => 'ddn_sub_consent_whatsapp',
				'value'   => '1',
				'compare' => '=',
			);
		}

		if ( array() !== $meta_query ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtros de admin, volumen bajo.
		}

		return $args;
	}
}
