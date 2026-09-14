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
