<?php
/**
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FacebookProvider implements ProviderInterface {

	private const GRAPH_VERSION = 'v19.0';

	public function __construct( private readonly CredentialsStore $credentials ) {}

	public function id(): string {
		return 'facebook';
	}

	public function label(): string {
		return __( 'Facebook', 'ddn-suite' );
	}

	public function icon(): string {
		return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
			. '<path fill="#1877F2" d="M24 12.07C24 5.4 18.6 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.7 4.53-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/>'
			. '</svg>';
	}

	public function authorize_url( string $state, string $redirect_uri ): string {
		$creds = $this->credentials->get( $this->id() );

		return add_query_arg(
			array(
				'client_id'    => rawurlencode( $creds['client_id'] ),
				'redirect_uri' => rawurlencode( $redirect_uri ),
				'state'        => rawurlencode( $state ),
				'scope'        => rawurlencode( 'email,public_profile' ),
			),
			'https://www.facebook.com/' . self::GRAPH_VERSION . '/dialog/oauth'
		);
	}

	public function fetch_profile( string $code, string $redirect_uri ): ?array {
		$creds = $this->credentials->get( $this->id() );
		if ( '' === $creds['client_id'] || '' === $creds['client_secret'] ) {
			return null;
		}

		$token_url = add_query_arg(
			array(
				'client_id'     => $creds['client_id'],
				'redirect_uri'  => $redirect_uri,
				'client_secret' => $creds['client_secret'],
				'code'          => $code,
			),
			'https://graph.facebook.com/' . self::GRAPH_VERSION . '/oauth/access_token'
		);

		$token_res = wp_remote_get( $token_url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $token_res ) ) {
			return null;
		}

		$token_body   = json_decode( (string) wp_remote_retrieve_body( $token_res ), true );
		$access_token = is_array( $token_body ) ? (string) ( $token_body['access_token'] ?? '' ) : '';
		if ( '' === $access_token ) {
			return null;
		}

		$profile_url = add_query_arg(
			array(
				'fields'       => 'id,name,email,picture',
				'access_token' => $access_token,
			),
			'https://graph.facebook.com/' . self::GRAPH_VERSION . '/me'
		);

		$profile_res = wp_remote_get( $profile_url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $profile_res ) ) {
			return null;
		}

		$profile = json_decode( (string) wp_remote_retrieve_body( $profile_res ), true );
		if ( ! is_array( $profile ) || empty( $profile['email'] ) ) {
			return null;
		}

		$avatar = '';
		if ( isset( $profile['picture']['data']['url'] ) ) {
			$avatar = (string) $profile['picture']['data']['url'];
		}

		return array(
			'email'            => sanitize_email( (string) $profile['email'] ),
			'name'             => sanitize_text_field( (string) ( $profile['name'] ?? $profile['email'] ) ),
			'avatar'           => esc_url_raw( $avatar ),
			'provider_user_id' => sanitize_text_field( (string) ( $profile['id'] ?? '' ) ),
		);
	}
}
