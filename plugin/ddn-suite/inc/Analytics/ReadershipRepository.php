<?php
/**
 * Consultas de las estadísticas de lectura: notas y autores más leídos y
 * lecturas por día, sobre los cubos por hora que guarda PageviewRecorder
 * (solo lectores: sin personal ni bots). Solo cuenta notas publicadas.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Analytics;

use DiarioDelNorte\Suite\Support\Db;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReadershipRepository {

	/** @return array{views:int,posts:int} */
	public function totals( string $from, string $to, int $author_id = 0, string $role = '', bool $in_range = false ): array {
		global $wpdb;

		[$role_flag, $cap_key, $role_like]    = $this->role_filter( $role );
		[$scope_flag, $scope_from, $scope_to] = $this->scope_filter( $from, $to, $in_range );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(v.hits), 0) AS views, COUNT(DISTINCT v.post_id) AS posts
				 FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish' AND ( %d = 0 OR p.post_author = %d )
				   AND ( %s = '' OR EXISTS ( SELECT 1 FROM {$wpdb->usermeta} um WHERE um.user_id = p.post_author AND um.meta_key = %s AND um.meta_value LIKE %s ) )
				   AND ( %d = 0 OR ( p.post_date >= %s AND p.post_date <= %s ) )
				 WHERE v.bucket >= %s AND v.bucket <= %s",
				Db::table( Db::PAGEVIEWS ),
				$author_id,
				$author_id,
				$role_flag,
				$cap_key,
				$role_like,
				$scope_flag,
				$scope_from,
				$scope_to,
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);

		return array(
			'views' => is_array( $row ) ? (int) $row['views'] : 0,
			'posts' => is_array( $row ) ? (int) $row['posts'] : 0,
		);
	}

	/**
	 * @return array<int,array{post_id:int,title:string,author:string,category:string,published:string,views:int,edit_url:string,url:string}>
	 */
	public function top_posts( string $from, string $to, int $limit, int $author_id = 0, string $role = '', bool $in_range = false ): array {
		global $wpdb;

		[$role_flag, $cap_key, $role_like]    = $this->role_filter( $role );
		[$scope_flag, $scope_from, $scope_to] = $this->scope_filter( $from, $to, $in_range );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.post_id, SUM(v.hits) AS views
				 FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish' AND ( %d = 0 OR p.post_author = %d )
				   AND ( %s = '' OR EXISTS ( SELECT 1 FROM {$wpdb->usermeta} um WHERE um.user_id = p.post_author AND um.meta_key = %s AND um.meta_value LIKE %s ) )
				   AND ( %d = 0 OR ( p.post_date >= %s AND p.post_date <= %s ) )
				 WHERE v.bucket >= %s AND v.bucket <= %s
				 GROUP BY v.post_id
				 ORDER BY views DESC, v.post_id DESC
				 LIMIT %d",
				Db::table( Db::PAGEVIEWS ),
				$author_id,
				$author_id,
				$role_flag,
				$cap_key,
				$role_like,
				$scope_flag,
				$scope_from,
				$scope_to,
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$limit
			),
			ARRAY_A
		);

		return $this->rows_to_posts( (array) $rows );
	}

	/**
	 * TODAS las notas publicadas de un autor, de más a menos leídas en el
	 * rango (las que no tuvieron lecturas salen al final, con 0).
	 *
	 * @return array{rows:array<int,array{post_id:int,title:string,author:string,category:string,published:string,views:int,edit_url:string,url:string}>,total:int}
	 */
	public function author_posts( string $from, string $to, int $author_id, int $per_page, int $page, bool $in_range = false ): array {
		global $wpdb;

		[$scope_flag, $scope_from, $scope_to] = $this->scope_filter( $from, $to, $in_range );

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_author = %d
				 AND ( %d = 0 OR ( post_date >= %s AND post_date <= %s ) )",
				$author_id,
				$scope_flag,
				$scope_from,
				$scope_to
			)
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID AS post_id, COALESCE(SUM(v.hits), 0) AS views
				 FROM {$wpdb->posts} p
				 LEFT JOIN %i v ON v.post_id = p.ID AND v.bucket >= %s AND v.bucket <= %s
				 WHERE p.post_type = 'post' AND p.post_status = 'publish' AND p.post_author = %d
				   AND ( %d = 0 OR ( p.post_date >= %s AND p.post_date <= %s ) )
				 GROUP BY p.ID
				 ORDER BY views DESC, p.post_date DESC
				 LIMIT %d OFFSET %d",
				Db::table( Db::PAGEVIEWS ),
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$author_id,
				$scope_flag,
				$scope_from,
				$scope_to,
				$per_page,
				max( 0, ( $page - 1 ) * $per_page )
			),
			ARRAY_A
		);

		return array(
			'rows'  => $this->rows_to_posts( (array) $rows ),
			'total' => $total,
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $rows filas con post_id y views.
	 * @return array<int,array{post_id:int,title:string,author:string,category:string,published:string,views:int,edit_url:string,url:string}>
	 */
	private function rows_to_posts( array $rows ): array {
		$out = array();
		foreach ( $rows as $row ) {
			$post_id = (int) $row['post_id'];
			$post    = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$cats   = get_the_category( $post_id );
			$author = get_userdata( (int) $post->post_author );

			$out[] = array(
				'post_id'   => $post_id,
				'title'     => get_the_title( $post ),
				'author'    => $author instanceof WP_User ? $author->display_name : __( '(usuario borrado)', 'ddn-suite' ),
				'category'  => array() !== $cats ? $cats[0]->name : '',
				'published' => (string) get_the_date( '', $post ),
				'views'     => (int) $row['views'],
				'edit_url'  => (string) get_edit_post_link( $post_id, 'raw' ),
				'url'       => (string) get_permalink( $post_id ),
			);
		}

		return $out;
	}

	/**
	 * @return array<int,array{author_id:int,name:string,role:string,views:int,posts:int}>
	 */
	public function top_authors( string $from, string $to, int $limit, string $role = '', bool $in_range = false ): array {
		global $wpdb;

		[$role_flag, $cap_key, $role_like]    = $this->role_filter( $role );
		[$scope_flag, $scope_from, $scope_to] = $this->scope_filter( $from, $to, $in_range );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_author, SUM(v.hits) AS views, COUNT(DISTINCT v.post_id) AS posts
				 FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish' AND ( %d = 0 OR p.post_author = %d )
				   AND ( %s = '' OR EXISTS ( SELECT 1 FROM {$wpdb->usermeta} um WHERE um.user_id = p.post_author AND um.meta_key = %s AND um.meta_value LIKE %s ) )
				   AND ( %d = 0 OR ( p.post_date >= %s AND p.post_date <= %s ) )
				 WHERE v.bucket >= %s AND v.bucket <= %s
				 GROUP BY p.post_author
				 ORDER BY views DESC
				 LIMIT %d",
				Db::table( Db::PAGEVIEWS ),
				0,
				0,
				$role_flag,
				$cap_key,
				$role_like,
				$scope_flag,
				$scope_from,
				$scope_to,
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$limit
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
				'role'      => $author instanceof WP_User ? $this->role_label( $author ) : '',
				'views'     => (int) $row['views'],
				'posts'     => (int) $row['posts'],
			);
		}

		return $out;
	}

	/** Notas publicadas en el rango (por un autor, o por todos si `$author_id` es 0). */
	public function published_count( string $from, string $to, int $author_id = 0 ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				 WHERE post_type = 'post' AND post_status = 'publish'
				   AND post_date >= %s AND post_date <= %s
				   AND ( %d = 0 OR post_author = %d )",
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$author_id,
				$author_id
			)
		);
	}

	/** @return array<int,string> id de autor => nombre, de quienes tienen notas publicadas. */
	public function authors_with_posts( string $role = '' ): array {
		$users = get_users(
			array(
				'role'                => $role,
				'has_published_posts' => array( 'post' ),
				'orderby'             => 'display_name',
				'order'               => 'ASC',
				'fields'              => array( 'ID', 'display_name' ),
			)
		);

		$out = array();
		foreach ( $users as $user ) {
			$out[ (int) $user->ID ] = (string) $user->display_name;
		}

		return $out;
	}

	/** @return array<string,int> día (Y-m-d) => lecturas; solo los días con datos. */
	public function daily( string $from, string $to, int $author_id = 0, string $role = '', bool $in_range = false ): array {
		global $wpdb;

		[$role_flag, $cap_key, $role_like]    = $this->role_filter( $role );
		[$scope_flag, $scope_from, $scope_to] = $this->scope_filter( $from, $to, $in_range );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(v.bucket) AS day, SUM(v.hits) AS views
				 FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish' AND ( %d = 0 OR p.post_author = %d )
				   AND ( %s = '' OR EXISTS ( SELECT 1 FROM {$wpdb->usermeta} um WHERE um.user_id = p.post_author AND um.meta_key = %s AND um.meta_value LIKE %s ) )
				   AND ( %d = 0 OR ( p.post_date >= %s AND p.post_date <= %s ) )
				 WHERE v.bucket >= %s AND v.bucket <= %s
				 GROUP BY DATE(v.bucket)
				 ORDER BY day ASC",
				Db::table( Db::PAGEVIEWS ),
				$author_id,
				$author_id,
				$role_flag,
				$cap_key,
				$role_like,
				$scope_flag,
				$scope_from,
				$scope_to,
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row['day'] ] = (int) $row['views'];
		}

		return $out;
	}

	/** @return array<string,string> slug de rol => nombre legible (sin suscriptores, que no escriben). */
	public function roles(): array {
		$out = array();
		foreach ( wp_roles()->get_names() as $slug => $name ) {
			if ( 'subscriber' !== $slug ) {
				$out[ (string) $slug ] = translate_user_role( (string) $name );
			}
		}

		return $out;
	}

	/**
	 * @return array{0:string,1:string,2:string} marcador de «hay filtro», clave de la
	 *         capacidad en usermeta y patrón LIKE del rol (los tres van a la consulta).
	 */
	private function role_filter( string $role ): array {
		global $wpdb;

		if ( '' === $role ) {
			return array( '', '', '' );
		}

		return array( $role, $wpdb->get_blog_prefix() . 'capabilities', '%' . $wpdb->esc_like( '"' . $role . '"' ) . '%' );
	}

	private function role_label( WP_User $user ): string {
		$names = $this->roles();
		$slug  = array() !== $user->roles ? (string) $user->roles[0] : '';

		return $names[ $slug ] ?? $slug;
	}

	/**
	 * @return array{0:int,1:string,2:string} marcador de «solo notas publicadas
	 *         en el rango» y los límites de fecha de publicación.
	 */
	private function scope_filter( string $from, string $to, bool $in_range ): array {
		return array( $in_range ? 1 : 0, $from . ' 00:00:00', $to . ' 23:59:59' );
	}
}
