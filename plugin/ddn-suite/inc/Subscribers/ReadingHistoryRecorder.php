<?php
/**
 * Registra en el historial del suscriptor cada nota que ve mientras
 * tiene sesión iniciada. Excluye al personal de redacción
 * (con `edit_posts`): esto es «lo que leyó el lector», no tráfico de
 * quien está trabajando en el sitio.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReadingHistoryRecorder {

	public function __construct( private readonly ReadingHistoryRepository $history ) {}

	public function register(): void {
		add_action( 'wp', array( $this, 'maybe_record' ) );
	}

	public function maybe_record(): void {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_preview() ) {
			return;
		}
		if ( ! is_singular( 'post' ) || ! is_user_logged_in() ) {
			return;
		}
		if ( current_user_can( 'edit_posts' ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		$this->history->record( get_current_user_id(), $post_id );
	}
}
