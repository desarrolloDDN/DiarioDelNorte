<?php
/**
 * Registro de páginas vistas del lado del servidor, en cubos por hora.
 * No guarda IP ni user-agent: solo un contador por (entrada, hora).
 * Excluye al personal (usuarios con `edit_posts`), bots evidentes y
 * peticiones que no son la vista pública de una noticia.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Analytics;

use DiarioDelNorte\Suite\Support\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PageviewRecorder {

	public function register(): void {
		add_action( 'wp', array( $this, 'maybe_record' ) );
		add_action( 'ddn_suite_prune_pageviews', array( $this, 'prune' ) );
	}

	public function maybe_record(): void {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_preview() ) {
			return;
		}
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			return;
		}
		if ( $this->looks_like_bot() ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (post_id, bucket, hits) VALUES (%d, %s, 1)
				 ON DUPLICATE KEY UPDATE hits = hits + 1',
				Db::table( Db::PAGEVIEWS ),
				$post_id,
				current_time( 'Y-m-d H:00:00' )
			)
		);
	}

	/** Borra los cubos de más de 8 días (WP-Cron diario). */
	public function prune(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE bucket < %s',
				Db::table( Db::PAGEVIEWS ),
				gmdate( 'Y-m-d H:00:00', time() - 8 * DAY_IN_SECONDS )
			)
		);
	}

	private function looks_like_bot(): bool {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		if ( '' === $ua ) {
			return true;
		}

		foreach ( array( 'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'preview', 'monitor', 'lighthouse', 'headless' ) as $needle ) {
			if ( str_contains( $ua, $needle ) ) {
				return true;
			}
		}

		return false;
	}
}
