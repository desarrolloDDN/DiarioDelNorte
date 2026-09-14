<?php
/**
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GoogleProvider implements ProviderInterface {

	public function __construct( private readonly CredentialsStore $credentials ) {}

	public function id(): string {
		return 'google';
	}

	public function label(): string {
		return __( 'Google', 'ddn-suite' );
	}

	public function icon(): string {
		return '<svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true" focusable="false">'
			. '<path fill="#EA4335" d="M24 9.5c3.4 0 6.4 1.2 8.8 3.5l6.6-6.6C35.3 2.5 30 0 24 0 14.6 0 6.5 5.4 2.5 13.2l7.7 6C12.1 13.1 17.6 9.5 24 9.5z"/>'
			. '<path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.5 3-2.2 5.5-4.7 7.2l7.3 5.7C43.7 37.9 46.5 31.8 46.5 24.5z"/>'
			. '<path fill="#FBBC05" d="M10.2 19.2A14.5 14.5 0 0 0 9.5 24c0 1.7.3 3.3.8 4.8l-7.7 6A24 24 0 0 1 0 24c0-3.9.9-7.6 2.5-10.8l7.7 6z"/>'
			. '<path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.3-5.7c-2 1.4-4.7 2.2-8.6 2.2-6.4 0-11.9-3.6-13.8-8.7l-7.7 6C6.5 42.6 14.6 48 24 48z"/>'
			. '</svg>';
	}

	public function authorize_url( string $state, string $redirect_uri ): string {
		$creds = $this->credentials->get( $this->id() );

		return add_query_arg(
			array(
				'client_id'     => rawurlencode( $creds['client_id'] ),
				'redirect_uri'  => rawurlencode( $redirect_uri ),
				'response_type' => 'code',
				'scope'         => rawurlencode( 'openid email profile' ),
				'state'         => rawurlencode( $state ),
				'access_type'   => 'online',
				'prompt'        => 'select_account',
			),
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
	}

	public function fetch_profile( string $code, string $redirect_uri ): ?array {
		$creds = $this->credentials->get( $this->id() );
		if ( '' === $creds['client_id'] || '' === $creds['client_secret'] ) {
			return null;
		}

		$token_res = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 15,
				'body'    => array(
					'code'          => $code,
					'client_id'     => $creds['client_id'],
					'client_secret' => $creds['client_secret'],
					'redirect_uri'  => $redirect_uri,
					'grant_type'    => 'authorization_code',
				),
			)
		);
		if ( is_wp_error( $token_res ) ) {
			return null;
		}

		$token_body   = json_decode( (string) wp_remote_retrieve_body( $token_res ), true );
		$access_token = is_array( $token_body ) ? (string) ( $token_body['access_token'] ?? '' ) : '';
		if ( '' === $access_token ) {
			return null;
		}

		$profile_res = wp_remote_get(
			'https://www.googleapis.com/oauth2/v3/userinfo',
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
			)
		);
		if ( is_wp_error( $profile_res ) ) {
			return null;
		}

		$profile = json_decode( (string) wp_remote_retrieve_body( $profile_res ), true );
		if ( ! is_array( $profile ) || empty( $profile['email'] ) ) {
			return null;
		}

		return array(
			'email'            => sanitize_email( (string) $profile['email'] ),
			'name'             => sanitize_text_field( (string) ( $profile['name'] ?? $profile['email'] ) ),
			'avatar'           => esc_url_raw( (string) ( $profile['picture'] ?? '' ) ),
			'provider_user_id' => sanitize_text_field( (string) ( $profile['sub'] ?? '' ) ),
		);
	}
}
