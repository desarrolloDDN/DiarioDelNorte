<?php
/**
 * Rutas REST del reproductor de radio:
 *  - GET  ddn-suite/v1/radio/nowplaying  → «sonando ahora» (proxy con caché).
 *  - POST ddn-suite/v1/radio/tick        → registra escucha (arranque / latido).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Radio;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RadioController {

	private const NS = 'ddn-suite/v1';

	public function __construct(
		private readonly RadioSettings $settings,
		private readonly RadioMeta $meta,
		private readonly RadioStats $stats,
	) {}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes(): void {
		register_rest_route(
			self::NS,
			'/radio/nowplaying',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'station' => array(
						'type'    => 'integer',
						'default' => 0,
					),
				),
				'callback'            => array( $this, 'now_playing' ),
			)
		);

		register_rest_route(
			self::NS,
			'/radio/tick',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'check_nonce' ),
				'args'                => array(
					'station' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'kind'    => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'start', 'beat' ),
					),
				),
				'callback'            => array( $this, 'tick' ),
			)
		);
	}

	/**
	 * @return true|WP_Error
	 */
	public function check_nonce( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( is_string( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		return new WP_Error( 'ddn_radio_forbidden', __( 'Petición no válida.', 'ddn-suite' ), array( 'status' => 403 ) );
	}

	public function now_playing( WP_REST_Request $request ): WP_REST_Response {
		$index    = (int) $request->get_param( 'station' );
		$stations = $this->settings->get()['stations'];

		if ( ! isset( $stations[ $index ] ) ) {
			return new WP_REST_Response(
				array(
					'title'     => '',
					'listeners' => null,
				)
			);
		}

		return new WP_REST_Response(
			$this->meta->now_playing(
				array(
					'stream'   => $stations[ $index ]['stream'],
					'meta_url' => $stations[ $index ]['meta_url'],
				)
			)
		);
	}

	public function tick( WP_REST_Request $request ): WP_REST_Response {
		$data = $this->settings->get();
		if ( ! $data['enabled'] ) {
			return new WP_REST_Response( array( 'ok' => false ) );
		}

		$index = (int) $request->get_param( 'station' );
		if ( isset( $data['stations'][ $index ] ) ) {
			$this->stats->record( $index, (string) $request->get_param( 'kind' ) );
		}

		return new WP_REST_Response( array( 'ok' => true ) );
	}
}
