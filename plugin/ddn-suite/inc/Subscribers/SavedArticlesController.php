<?php
/**
 * REST: alternar «Guardar» en una nota (botón en single.php y en la
 * lista de Mi cuenta). Mismo esquema de nonce que Radio\RadioController.
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

final class SavedArticlesController {

	private const NS = 'ddn-suite/v1';

	public function __construct( private readonly SavedArticlesRepository $saved ) {}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes(): void {
		register_rest_route(
			self::NS,
			'/saved/toggle',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'check_request' ),
				'args'                => array(
					'post_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
				'callback'            => array( $this, 'toggle' ),
			)
		);
	}

	/**
	 * @return true|WP_Error
	 */
	public function check_request( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'ddn_saved_forbidden', __( 'Debes iniciar sesión.', 'ddn-suite' ), array( 'status' => 401 ) );
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( is_string( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		return new WP_Error( 'ddn_saved_forbidden', __( 'Petición no válida.', 'ddn-suite' ), array( 'status' => 403 ) );
	}

	public function toggle( WP_REST_Request $request ): WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );

		if ( $post_id <= 0 || 'post' !== get_post_type( $post_id ) ) {
			return new WP_REST_Response( array( 'saved' => false ), 400 );
		}

		$saved = $this->saved->toggle( get_current_user_id(), $post_id );

		return new WP_REST_Response( array( 'saved' => $saved ) );
	}
}
