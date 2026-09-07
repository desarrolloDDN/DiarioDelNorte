<?php
/**
 * Ajustes del reproductor de radio flotante: on/off y lista de emisoras
 * (nombre, URL del stream y logo). Se guardan en una sola opción.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Radio;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RadioSettings {

	public const OPTION = 'ddn_suite_radio';

	/**
	 * @return array{enabled:bool,stations:array<int,array{name:string,stream:string,logo_id:int,logo_url:string}>}
	 */
	public function get(): array {
		$raw = get_option( self::OPTION );
		if ( ! is_array( $raw ) ) {
			return $this->defaults();
		}

		$stations = array();
		foreach ( (array) ( $raw['stations'] ?? array() ) as $station ) {
			if ( ! is_array( $station ) ) {
				continue;
			}
			$name   = isset( $station['name'] ) ? sanitize_text_field( (string) $station['name'] ) : '';
			$stream = isset( $station['stream'] ) ? esc_url_raw( (string) $station['stream'] ) : '';
			if ( '' === $name || '' === $stream ) {
				continue;
			}
			$logo_id  = isset( $station['logo_id'] ) ? max( 0, (int) $station['logo_id'] ) : 0;
			$logo_url = $logo_id > 0 ? (string) wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';

			$stations[] = array(
				'name'     => $name,
				'stream'   => $stream,
				'logo_id'  => $logo_id,
				'logo_url' => $logo_url,
			);
		}

		return array(
			'enabled'  => ! empty( $raw['enabled'] ),
			'stations' => $stations,
		);
	}

	/**
	 * Guarda desde el formulario de administración.
	 *
	 * @param array<string,mixed> $input
	 */
	public function save( array $input ): void {
		$stations = array();
		foreach ( (array) ( $input['stations'] ?? array() ) as $station ) {
			if ( ! is_array( $station ) ) {
				continue;
			}
			$name   = isset( $station['name'] ) ? sanitize_text_field( (string) $station['name'] ) : '';
			$stream = isset( $station['stream'] ) ? esc_url_raw( trim( (string) $station['stream'] ) ) : '';
			if ( '' === $name || '' === $stream ) {
				continue;
			}
			$stations[] = array(
				'name'    => $name,
				'stream'  => $stream,
				'logo_id' => isset( $station['logo_id'] ) ? max( 0, (int) $station['logo_id'] ) : 0,
			);
		}

		update_option(
			self::OPTION,
			array(
				'enabled'  => ! empty( $input['enabled'] ),
				'stations' => $stations,
			)
		);
	}

	/**
	 * @return array{enabled:bool,stations:array<int,array{name:string,stream:string,logo_id:int,logo_url:string}>}
	 */
	private function defaults(): array {
		return array(
			'enabled'  => false,
			'stations' => array(
				array(
					'name'     => 'Riohacha',
					'stream'   => 'https://usest-sp1.golivestream.net/8006/stream',
					'logo_id'  => 0,
					'logo_url' => '',
				),
				array(
					'name'     => 'Valledupar',
					'stream'   => 'https://usest-sp1.golivestream.net/8016/stream',
					'logo_id'  => 0,
					'logo_url' => '',
				),
			),
		);
	}
}
