<?php
/**
 * Orquesta el login social (Google/Facebook): «Authorization Code» con
 * `state` de un solo uso (evita CSRF en el callback). Si el correo del
 * proveedor ya tiene cuenta, la vincula (nunca crea un duplicado); si no,
 * NO crea la cuenta todavía — guarda el perfil recibido en un token
 * temporal de un solo uso y manda a la página de Registro a confirmar la
 * aceptación de Términos y Política antes de crear nada (ver
 * RegistrationController::render_social_confirm() /
 * handle_social_confirm()).
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\OAuth;

use DiarioDelNorte\Suite\Subscribers\Install\PageInstaller;
use DiarioDelNorte\Suite\Subscribers\ProfileRepository;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OAuthController {

	public const PENDING_TRANSIENT_PREFIX = 'ddn_oauth_pending_';
	private const STATE_TRANSIENT_PREFIX  = 'ddn_oauth_state_';
	private const STATE_TTL               = 600;

	/** @var array<string,ProviderInterface> */
	private array $providers;

	/** @param ProviderInterface[] $providers */
	public function __construct(
		array $providers,
		private readonly ProfileRepository $profiles,
		private readonly CredentialsStore $credentials,
	) {
		$this->providers = array();
		foreach ( $providers as $provider ) {
			$this->providers[ $provider->id() ] = $provider;
		}
	}

	public function register(): void {
		foreach ( array_keys( $this->providers ) as $id ) {
			add_action(
				'admin_post_nopriv_ddn_oauth_start_' . $id,
				function () use ( $id ): void {
					$this->start( $id );
				}
			);
			add_action(
				'admin_post_ddn_oauth_start_' . $id,
				function () use ( $id ): void {
					$this->start( $id );
				}
			);
			add_action(
				'admin_post_nopriv_ddn_oauth_callback_' . $id,
				function () use ( $id ): void {
					$this->callback( $id );
				}
			);
			add_action(
				'admin_post_ddn_oauth_callback_' . $id,
				function () use ( $id ): void {
					$this->callback( $id );
				}
			);
		}
	}

	/** @return ProviderInterface[] Solo los que tienen client id/secret configurados. */
	public function available_providers(): array {
		return array_values(
			array_filter(
				$this->providers,
				fn ( ProviderInterface $p ): bool => $this->credentials->configured( $p->id() )
			)
		);
	}

	/** Botones «Continuar con Google/Facebook»; nada si ninguno está configurado. */
	public function render_buttons( string $redirect_to = '' ): void {
		$providers = $this->available_providers();
		if ( array() === $providers ) {
			return;
		}

		echo '<div class="ddn-social-login">';
		foreach ( $providers as $provider ) {
			printf(
				'<a class="ddn-social-login__btn" href="%s">%s<span>%s</span></a>',
				esc_url( self::start_url( $provider->id(), $redirect_to ) ),
				$provider->icon(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo del proveedor, no viene de entrada de usuario.
				/* translators: %s: nombre del proveedor (Google, Facebook). */
				esc_html( sprintf( __( 'Continuar con %s', 'ddn-suite' ), $provider->label() ) )
			);
		}
		echo '</div>';
	}

	public static function start_url( string $provider_id, string $redirect_to = '' ): string {
		return add_query_arg(
			array(
				'action'      => 'ddn_oauth_start_' . $provider_id,
				'redirect_to' => rawurlencode( $redirect_to ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	private function callback_url( string $provider_id ): string {
		return add_query_arg( 'action', 'ddn_oauth_callback_' . $provider_id, admin_url( 'admin-post.php' ) );
	}

	private function start( string $provider_id ): void {
		$provider = $this->providers[ $provider_id ] ?? null;
		if ( null === $provider ) {
			wp_safe_redirect( $this->login_url( 'invalid_request' ) );
			exit;
		}

		$requested   = isset( $_GET['redirect_to'] ) ? (string) wp_unslash( $_GET['redirect_to'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo se usa tras validarla contra el propio sitio (wp_validate_redirect), tres líneas abajo.
		$redirect_to = wp_validate_redirect( $requested, '' );

		$state = wp_generate_password( 32, false, false );
		set_transient(
			self::STATE_TRANSIENT_PREFIX . $state,
			array(
				'provider'    => $provider_id,
				'redirect_to' => $redirect_to,
			),
			self::STATE_TTL
		);

		wp_redirect( $provider->authorize_url( $state, $this->callback_url( $provider_id ) ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- destino es el proveedor OAuth, no una URL de la petición.
		exit;
	}

	private function callback( string $provider_id ): void {
		$provider = $this->providers[ $provider_id ] ?? null;

		// El propio `state` de un solo uso (validado contra el transient
		// abajo) es la protección CSRF de este callback: no hace falta un
		// nonce de WordPress además.
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$stored = '' !== $state ? get_transient( self::STATE_TRANSIENT_PREFIX . $state ) : false;
		if ( '' !== $state ) {
			delete_transient( self::STATE_TRANSIENT_PREFIX . $state ); // de un solo uso: se borra se use o no.
		}

		if ( null === $provider || '' === $code || ! is_array( $stored ) || $stored['provider'] !== $provider_id ) {
			wp_safe_redirect( $this->login_url( 'invalid_request' ) );
			exit;
		}

		$profile = $provider->fetch_profile( $code, $this->callback_url( $provider_id ) );
		if ( null === $profile ) {
			wp_safe_redirect( $this->login_url( 'invalid' ) );
			exit;
		}

		$redirect_to = (string) $stored['redirect_to'];
		$existing    = get_user_by( 'email', $profile['email'] );

		if ( $existing instanceof WP_User ) {
			$this->profiles->link_oauth_id( (int) $existing->ID, $provider_id, $profile['provider_user_id'] );
			$this->log_in( $existing );
			wp_safe_redirect( $this->post_login_redirect( $redirect_to ) );
			exit;
		}

		// No hay cuenta con ese correo: falta aceptar Términos y Política
		// antes de crear nada. Perfil pendiente bajo un token de un solo
		// uso; RegistrationController lo recoge para el paso de
		// confirmación.
		$pending_token = wp_generate_password( 32, false, false );
		set_transient(
			self::PENDING_TRANSIENT_PREFIX . $pending_token,
			array(
				'provider'    => $provider_id,
				'profile'     => $profile,
				'redirect_to' => $redirect_to,
			),
			self::STATE_TTL
		);

		$register_url = PageInstaller::url( PageInstaller::SLUG_REGISTER );
		wp_safe_redirect( add_query_arg( 'ddn_social_token', $pending_token, '' !== $register_url ? $register_url : home_url( '/' ) ) );
		exit;
	}

	private function log_in( WP_User $user ): void {
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );
	}

	private function post_login_redirect( string $requested ): string {
		$account_url = PageInstaller::url( PageInstaller::SLUG_ACCOUNT );
		$default     = '' !== $account_url ? $account_url : home_url( '/' );

		return wp_validate_redirect( $requested, $default );
	}

	private function login_url( string $status ): string {
		$login = PageInstaller::url( PageInstaller::SLUG_LOGIN );

		return add_query_arg( 'ddn_login', $status, '' !== $login ? $login : home_url( '/' ) );
	}
}
