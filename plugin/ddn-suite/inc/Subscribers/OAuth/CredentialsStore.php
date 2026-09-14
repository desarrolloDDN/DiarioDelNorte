<?php
/**
 * Client ID/secret de cada proveedor OAuth, cifrados en reposo (el
 * secret nunca se guarda en texto plano en `wp_options`).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\OAuth;

use DiarioDelNorte\Suite\Subscribers\Support\Crypto;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CredentialsStore {

	public function __construct( private readonly string $secret ) {}

	/** @return array{client_id:string,client_secret:string} */
	public function get( string $provider ): array {
		return array(
			'client_id'     => (string) get_option( "ddn_oauth_{$provider}_client_id", '' ),
			'client_secret' => Crypto::decrypt( (string) get_option( "ddn_oauth_{$provider}_client_secret", '' ), $this->secret ),
		);
	}

	public function configured( string $provider ): bool {
		$creds = $this->get( $provider );

		return '' !== $creds['client_id'] && '' !== $creds['client_secret'];
	}

	/**
	 * Si `$client_secret` viene vacío, se conserva el que ya había
	 * guardado (así el admin no tiene que reescribirlo cada vez que
	 * actualiza solo el Client ID).
	 */
	public function save( string $provider, string $client_id, string $client_secret ): void {
		update_option( "ddn_oauth_{$provider}_client_id", sanitize_text_field( $client_id ), false );

		if ( '' === $client_secret ) {
			return;
		}

		update_option( "ddn_oauth_{$provider}_client_secret", Crypto::encrypt( sanitize_text_field( $client_secret ), $this->secret ), false );
	}
}
