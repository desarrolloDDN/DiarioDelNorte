<?php
/**
 * REST: quitar una sola nota del historial de lectura, sin recargar la
 * página (botón «Quitar» en cada fila de Mi cuenta). Mismo esquema de
 * nonce que SavedArticlesController.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReadingHistoryController {

	private const NS = 'ddn-suite/v1';

	public function __construct( private readonly ReadingHistoryRepository $history ) {}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes(): void {
		register_rest_route(
			self::NS,
			'/history/remove',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'check_request' ),
				'args'                => array(
					'post_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
				'callback'            => array( $this, 'remove' ),
			)
		);
	}

	/**
	 * @return true|WP_Error
	 */
	public function check_request( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'ddn_history_forbidden', __( 'Debes iniciar sesión.', 'ddn-suite' ), array( 'status' => 401 ) );
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( is_string( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		return new WP_Error( 'ddn_history_forbidden', __( 'Petición no válida.', 'ddn-suite' ), array( 'status' => 403 ) );
	}

	public function remove( WP_REST_Request $request ): WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		if ( $post_id <= 0 ) {
			return new WP_REST_Response( array( 'removed' => false ), 400 );
		}

		// Siempre sobre el usuario de la sesión actual — nunca un ID que
		// venga en la petición: así nadie puede borrar el historial de otro.
		$this->history->remove( get_current_user_id(), $post_id );

		return new WP_REST_Response( array( 'removed' => true ) );
	}
}
