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
	public function totals( string $from, string $to ): array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(v.hits), 0) AS views, COUNT(DISTINCT v.post_id) AS posts
				 FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish'
				 WHERE v.bucket >= %s AND v.bucket <= %s",
				Db::table( Db::PAGEVIEWS ),
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
	public function top_posts( string $from, string $to, int $limit ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.post_id, SUM(v.hits) AS views
				 FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish'
				 WHERE v.bucket >= %s AND v.bucket <= %s
				 GROUP BY v.post_id
				 ORDER BY views DESC, v.post_id DESC
				 LIMIT %d",
				Db::table( Db::PAGEVIEWS ),
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$limit
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
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
	 * @return array<int,array{author_id:int,name:string,views:int,posts:int}>
	 */
	public function top_authors( string $from, string $to, int $limit ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_author, SUM(v.hits) AS views, COUNT(DISTINCT v.post_id) AS posts
				 FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish'
				 WHERE v.bucket >= %s AND v.bucket <= %s
				 GROUP BY p.post_author
				 ORDER BY views DESC
				 LIMIT %d",
				Db::table( Db::PAGEVIEWS ),
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
				'views'     => (int) $row['views'],
				'posts'     => (int) $row['posts'],
			);
		}

		return $out;
	}

	/** @return array<string,int> día (Y-m-d) => lecturas; solo los días con datos. */
	public function daily( string $from, string $to ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(v.bucket) AS day, SUM(v.hits) AS views
				 FROM %i v
				 INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id AND p.post_type = 'post' AND p.post_status = 'publish'
				 WHERE v.bucket >= %s AND v.bucket <= %s
				 GROUP BY DATE(v.bucket)
				 ORDER BY day ASC",
				Db::table( Db::PAGEVIEWS ),
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
}
