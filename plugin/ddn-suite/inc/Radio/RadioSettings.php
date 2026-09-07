<?php
/**
 * Ajustes del reproductor de radio flotante: on/off, arranque, emisora
 * por defecto, nombre en la cabecera cuando nada suena, y lista de
 * emisoras (nombre, marca para la cabecera, stream, logo y URL opcional
 * de metadatos «sonando ahora»). Todo en una sola opción.
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

	private const DEFAULT_IDLE_TITLE = 'Sistema Cardenal';

	/**
	 * Valores por defecto conocidos según la URL del stream: se usan como
	 * reserva cuando el administrador no ha rellenado la marca o el logo.
	 *
	 * @var array<string,array{brand:string,logo:string}>
	 */
	private const KNOWN = array(
		'https://usest-sp1.golivestream.net/8006/stream' => array(
			'brand' => 'Cardenal Stereo',
			'logo'  => 'assets/radio/cardenal-stereo.png',
		),
		'https://usest-sp1.golivestream.net/8016/stream' => array(
			'brand' => 'Sistema Cardenal',
			'logo'  => 'assets/radio/sistema-cardenal.png',
		),
	);

	/**
	 * @return array{
	 *     enabled:bool,
	 *     start_minimized:bool,
	 *     default_station:int,
	 *     idle_title:string,
	 *     stations:array<int,array{name:string,brand:string,stream:string,logo_id:int,logo_url:string,meta_url:string}>
	 * }
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
			$known = self::KNOWN[ $stream ] ?? array(
				'brand' => '',
				'logo'  => '',
			);

			$brand = isset( $station['brand'] ) ? sanitize_text_field( (string) $station['brand'] ) : '';
			if ( '' === $brand ) {
				$brand = '' !== $known['brand'] ? $known['brand'] : $name;
			}

			$logo_id  = isset( $station['logo_id'] ) ? max( 0, (int) $station['logo_id'] ) : 0;
			$logo_url = $logo_id > 0 ? (string) wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
			if ( '' === $logo_url && '' !== $known['logo'] ) {
				$logo_url = DDN_SUITE_URL . $known['logo'];
			}

			$stations[] = array(
				'name'     => $name,
				'brand'    => $brand,
				'stream'   => $stream,
				'logo_id'  => $logo_id,
				'logo_url' => $logo_url,
				'meta_url' => isset( $station['meta_url'] ) ? esc_url_raw( (string) $station['meta_url'] ) : '',
			);
		}

		$default = isset( $raw['default_station'] ) ? (int) $raw['default_station'] : -1;
		if ( $default < 0 || $default >= count( $stations ) ) {
			$default = -1;
		}

		$idle = isset( $raw['idle_title'] ) ? sanitize_text_field( (string) $raw['idle_title'] ) : '';

		return array(
			'enabled'         => ! empty( $raw['enabled'] ),
			'start_minimized' => ! empty( $raw['start_minimized'] ),
			'default_station' => $default,
			'idle_title'      => '' !== $idle ? $idle : self::DEFAULT_IDLE_TITLE,
			'stations'        => $stations,
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
				'name'     => $name,
				'brand'    => isset( $station['brand'] ) ? sanitize_text_field( (string) $station['brand'] ) : '',
				'stream'   => $stream,
				'logo_id'  => isset( $station['logo_id'] ) ? max( 0, (int) $station['logo_id'] ) : 0,
				'meta_url' => isset( $station['meta_url'] ) ? esc_url_raw( trim( (string) $station['meta_url'] ) ) : '',
			);
		}

		$default = isset( $input['default_station'] ) ? (int) $input['default_station'] : -1;
		if ( $default < 0 || $default >= count( $stations ) ) {
			$default = -1;
		}

		$idle = isset( $input['idle_title'] ) ? sanitize_text_field( (string) $input['idle_title'] ) : '';

		update_option(
			self::OPTION,
			array(
				'enabled'         => ! empty( $input['enabled'] ),
				'start_minimized' => ! empty( $input['start_minimized'] ),
				'default_station' => $default,
				'idle_title'      => '' !== $idle ? $idle : self::DEFAULT_IDLE_TITLE,
				'stations'        => $stations,
			)
		);
	}

	/**
	 * @return array{
	 *     enabled:bool,
	 *     start_minimized:bool,
	 *     default_station:int,
	 *     idle_title:string,
	 *     stations:array<int,array{name:string,brand:string,stream:string,logo_id:int,logo_url:string,meta_url:string}>
	 * }
	 */
	private function defaults(): array {
		return array(
			'enabled'         => false,
			'start_minimized' => false,
			'default_station' => -1,
			'idle_title'      => self::DEFAULT_IDLE_TITLE,
			'stations'        => array(
				array(
					'name'     => 'Riohacha',
					'brand'    => 'Cardenal Stereo',
					'stream'   => 'https://usest-sp1.golivestream.net/8006/stream',
					'logo_id'  => 0,
					'logo_url' => DDN_SUITE_URL . 'assets/radio/cardenal-stereo.png',
					'meta_url' => '',
				),
				array(
					'name'     => 'Valledupar',
					'brand'    => 'Sistema Cardenal',
					'stream'   => 'https://usest-sp1.golivestream.net/8016/stream',
					'logo_id'  => 0,
					'logo_url' => DDN_SUITE_URL . 'assets/radio/sistema-cardenal.png',
					'meta_url' => '',
				),
			),
		);
	}
}
