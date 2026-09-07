<?php
/**
 * «Sonando ahora» de una emisora. Consulta el panel del stream desde el
 * servidor (los paneles Shoutcast/Icecast no permiten CORS, por eso lo
 * hace WordPress) y cachea el resultado unos segundos para no golpear al
 * proveedor en cada visita.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Radio;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RadioMeta {

	private const TTL = 20;

	/**
	 * @param array{stream:string,meta_url:string} $station
	 * @return array{title:string,listeners:int|null}
	 */
	public function now_playing( array $station ): array {
		$stream = $station['stream'];
		if ( '' === $stream ) {
			return array(
				'title'     => '',
				'listeners' => null,
			);
		}

		$key    = 'ddn_radio_np_' . md5( $stream );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			/** @var array{title:string,listeners:int|null} $cached */
			return $cached;
		}

		$result = array(
			'title'     => '',
			'listeners' => null,
		);

		foreach ( $this->candidate_urls( $stream, $station['meta_url'] ) as $url ) {
			$parsed = $this->fetch( $url );
			if ( null !== $parsed ) {
				$result = $parsed;
				break;
			}
		}

		set_transient( $key, $result, self::TTL );

		return $result;
	}

	/**
	 * URLs a probar, en orden: la que fije el administrador, y las
	 * habituales de Shoutcast v2 e Icecast derivadas del stream.
	 *
	 * @return list<string>
	 */
	private function candidate_urls( string $stream, string $custom ): array {
		$parts = wp_parse_url( $stream );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '' !== $custom ? array( $custom ) : array();
		}

		$base = $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
		$dir  = rtrim( (string) dirname( (string) ( $parts['path'] ?? '/' ) ), '/' );

		$urls = array();
		if ( '' !== $custom ) {
			$urls[] = $custom;
		}
		$urls[] = $base . $dir . '/stats?sid=1';       // Shoutcast v2 / SonicPanel.
		$urls[] = $base . $dir . '/status-json.xsl';   // Icecast.

		return array_values( array_unique( $urls ) );
	}

	/**
	 * @return array{title:string,listeners:int|null}|null
	 */
	private function fetch( string $url ): ?array {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 4,
				'user-agent' => 'DDN-Suite Radio',
				'headers'    => array( 'Accept' => 'application/json, text/xml;q=0.9, */*;q=0.5' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = trim( (string) wp_remote_retrieve_body( $response ) );
		if ( '' === $body ) {
			return null;
		}

		return $this->parse_shoutcast_xml( $body ) ?? $this->parse_icecast_json( $body );
	}

	/**
	 * @return array{title:string,listeners:int|null}|null
	 */
	private function parse_shoutcast_xml( string $body ): ?array {
		if ( ! str_contains( $body, '<SHOUTCASTSERVER' ) ) {
			return null;
		}

		$song  = $this->tag( $body, 'SONGTITLE' );
		$count = $this->tag( $body, 'CURRENTLISTENERS' );

		// Solo el título de la canción: si el panel no lo envía, el
		// reproductor deja «En vivo».
		return array(
			'title'     => sanitize_text_field( $song ),
			'listeners' => is_numeric( $count ) ? (int) $count : null,
		);
	}

	/**
	 * @return array{title:string,listeners:int|null}|null
	 */
	private function parse_icecast_json( string $body ): ?array {
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || ! isset( $data['icestats'] ) || ! is_array( $data['icestats'] ) ) {
			return null;
		}

		$source = $data['icestats']['source'] ?? array();
		if ( isset( $source[0] ) && is_array( $source[0] ) ) {
			$source = $source[0];
		}
		if ( ! is_array( $source ) ) {
			return null;
		}

		$title = '';
		foreach ( array( 'title', 'yp_currently_playing' ) as $field ) {
			if ( ! empty( $source[ $field ] ) ) {
				$title = (string) $source[ $field ];
				break;
			}
		}

		return array(
			'title'     => sanitize_text_field( $title ),
			'listeners' => isset( $source['listeners'] ) && is_numeric( $source['listeners'] ) ? (int) $source['listeners'] : null,
		);
	}

	private function tag( string $xml, string $name ): string {
		return preg_match( '#<' . $name . '>(.*?)</' . $name . '>#si', $xml, $m ) === 1
			? html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5 )
			: '';
	}
}
