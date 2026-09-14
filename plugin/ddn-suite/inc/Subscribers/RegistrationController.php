<?php
/**
 * Registro de suscriptores: directo (correo + contraseña) y el paso de
 * confirmación del registro social (Google/Facebook) — ver
 * OAuth\OAuthController, que entrega aquí el perfil recibido bajo un
 * token pendiente en cuanto no existe cuenta con ese correo. Formulario
 * público en la página «Registro» (page-registro.php del tema, creada
 * sola por Install\PageInstaller); procesa por admin-post.php.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use DiarioDelNorte\Suite\Subscribers\Install\PageInstaller;
use DiarioDelNorte\Suite\Subscribers\OAuth\OAuthController;
use DiarioDelNorte\Suite\Subscribers\Support\Messages;
use DiarioDelNorte\Suite\Subscribers\Support\UsernameGenerator;
use DiarioDelNorte\Suite\Subscribers\Support\Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RegistrationController {

	private const ACTION        = 'ddn_subscriber_register';
	private const SOCIAL_ACTION = 'ddn_subscriber_social_confirm';

	public function __construct(
		private readonly ProfileRepository $profiles,
		private readonly OAuthController $oauth,
	) {}

	public function register(): void {
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::SOCIAL_ACTION, array( $this, 'handle_social_confirm' ) );
		add_action( 'admin_post_' . self::SOCIAL_ACTION, array( $this, 'handle_social_confirm' ) );
	}

	public static function action_url(): string {
		return admin_url( 'admin-post.php' );
	}

	public static function action_name(): string {
		return self::ACTION;
	}

	/** Renderiza el formulario (o el paso de confirmación social); lo llama theme/page-registro.php. */
	public function render(): void {
		if ( is_user_logged_in() ) {
			printf(
				'<p>%s <a href="%s">%s</a></p>',
				esc_html__( 'Ya iniciaste sesión.', 'ddn-suite' ),
				esc_url( PageInstaller::url( PageInstaller::SLUG_ACCOUNT ) ),
				esc_html__( 'Ir a Mi cuenta', 'ddn-suite' )
			);
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide qué vista mostrar; el token en sí se valida contra el transient antes de usarlo.
		$token = isset( $_GET['ddn_social_token'] ) ? sanitize_text_field( wp_unslash( $_GET['ddn_social_token'] ) ) : '';
		if ( '' !== $token ) {
			$pending = get_transient( OAuthController::PENDING_TRANSIENT_PREFIX . $token );
			if ( is_array( $pending ) ) {
				$this->render_social_confirm( $token, $pending );
				return;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide qué mensaje mostrar, no cambia estado.
		$status = isset( $_GET['ddn_reg'] ) ? sanitize_key( wp_unslash( $_GET['ddn_reg'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
		$errors = isset( $_GET['ddn_errors'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['ddn_errors'] ) ) ) : array();

		if ( 'success' === $status ) {
			printf(
				'<p class="ddn-form-notice ddn-form-notice--ok">%s <a href="%s">%s</a></p>',
				esc_html__( 'Cuenta creada. Ya puedes iniciar sesión.', 'ddn-suite' ),
				esc_url( PageInstaller::url( PageInstaller::SLUG_LOGIN ) ),
				esc_html__( 'Ingresar', 'ddn-suite' )
			);
			return;
		}

		if ( array() !== $errors ) {
			echo '<div class="ddn-form-notice ddn-form-notice--error"><ul>';
			foreach ( $errors as $code ) {
				printf( '<li>%s</li>', esc_html( Messages::registration( $code ) ) );
			}
			echo '</ul></div>';
		}

		$this->oauth->render_buttons();

		$legal = LegalLinks::links();
		?>
		<form class="ddn-form" method="post" action="<?php echo esc_url( self::action_url() ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::action_name() ); ?>">
			<?php wp_nonce_field( self::ACTION, 'ddn_reg_nonce' ); ?>

			<p class="ddn-form-hp" aria-hidden="true">
				<label for="ddn_reg_website"><?php esc_html_e( 'Sitio web (dejar en blanco)', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_reg_website" name="ddn_reg_website" tabindex="-1" autocomplete="off">
			</p>

			<p class="ddn-field">
				<label for="ddn_full_name"><?php esc_html_e( 'Nombre completo', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_full_name" name="full_name" required>
			</p>
			<p class="ddn-field">
				<label for="ddn_phone"><?php esc_html_e( 'Celular', 'ddn-suite' ); ?></label>
				<input type="tel" id="ddn_phone" name="phone" required>
			</p>
			<p class="ddn-field">
				<label for="ddn_email"><?php esc_html_e( 'Correo electrónico', 'ddn-suite' ); ?></label>
				<input type="email" id="ddn_email" name="email" required>
			</p>
			<p class="ddn-field">
				<label for="ddn_password"><?php esc_html_e( 'Contraseña (mínimo 8 caracteres)', 'ddn-suite' ); ?></label>
				<input type="password" id="ddn_password" name="password" minlength="8" required>
			</p>

			<p class="ddn-field">
				<label for="ddn_department"><?php esc_html_e( 'Departamento', 'ddn-suite' ); ?></label>
				<select id="ddn_department" name="department">
					<option value=""><?php esc_html_e( 'Selecciona (opcional)', 'ddn-suite' ); ?></option>
					<?php foreach ( Options::departments() as $ddn_dep ) : ?>
						<option value="<?php echo esc_attr( $ddn_dep ); ?>"><?php echo esc_html( $ddn_dep ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ddn-field">
				<label for="ddn_city"><?php esc_html_e( 'Ciudad', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_city" name="city">
			</p>
			<p class="ddn-field">
				<label for="ddn_address"><?php esc_html_e( 'Dirección', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_address" name="address">
			</p>

			<p class="ddn-field ddn-field--pair">
				<label for="ddn_doc_type"><?php esc_html_e( 'Tipo de documento', 'ddn-suite' ); ?></label>
				<select id="ddn_doc_type" name="doc_type">
					<option value=""><?php esc_html_e( 'Selecciona (opcional)', 'ddn-suite' ); ?></option>
					<?php foreach ( Options::document_types() as $ddn_code => $ddn_label ) : ?>
						<option value="<?php echo esc_attr( $ddn_code ); ?>"><?php echo esc_html( $ddn_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ddn-field ddn-field--pair">
				<label for="ddn_doc_number"><?php esc_html_e( 'Número de documento', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_doc_number" name="doc_number">
			</p>
			<p class="description"><?php esc_html_e( 'Si llenas uno de los dos campos de documento, el otro también es obligatorio.', 'ddn-suite' ); ?></p>

			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="consent_email" value="1"> <?php esc_html_e( 'Quiero recibir correos de Diario del Norte.', 'ddn-suite' ); ?></label>
			</p>
			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="consent_whatsapp" value="1"> <?php esc_html_e( 'Quiero recibir WhatsApp de Diario del Norte.', 'ddn-suite' ); ?></label>
			</p>
			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="accept_terms" value="1" required>
					<?php
					printf(
						/* translators: %s: enlace a Términos y Condiciones. */
						esc_html__( 'Acepto los %s.', 'ddn-suite' ),
						'<a href="' . esc_url( $legal['terms'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Términos y Condiciones', 'ddn-suite' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba.
					);
					?>
				</label>
			</p>
			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="accept_privacy" value="1" required>
					<?php
					printf(
						/* translators: %s: enlace a la Política de tratamiento de datos. */
						esc_html__( 'Acepto la %s.', 'ddn-suite' ),
						'<a href="' . esc_url( $legal['privacy'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Política de tratamiento de datos', 'ddn-suite' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba.
					);
					?>
				</label>
			</p>

			<p><button type="submit" class="btn"><?php esc_html_e( 'Crear cuenta', 'ddn-suite' ); ?></button></p>
		</form>
		<?php
	}

	/**
	 * @param array{provider:string,profile:array{email:string,name:string,avatar:string,provider_user_id:string},redirect_to:string} $pending
	 */
	private function render_social_confirm( string $token, array $pending ): void {
		$legal   = LegalLinks::links();
		$profile = $pending['profile'];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide qué mensaje mostrar, no cambia estado.
		$errors = isset( $_GET['ddn_errors'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['ddn_errors'] ) ) ) : array();
		if ( array() !== $errors ) {
			echo '<div class="ddn-form-notice ddn-form-notice--error"><ul>';
			foreach ( $errors as $code ) {
				printf( '<li>%s</li>', esc_html( Messages::registration( $code ) ) );
			}
			echo '</ul></div>';
		}
		?>
		<p><?php esc_html_e( 'Ya casi terminas: confirma que aceptas lo siguiente para crear tu cuenta.', 'ddn-suite' ); ?></p>
		<p><strong><?php echo esc_html( $profile['name'] ); ?></strong> — <?php echo esc_html( $profile['email'] ); ?></p>
		<form class="ddn-form" method="post" action="<?php echo esc_url( self::action_url() ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::SOCIAL_ACTION ); ?>">
			<input type="hidden" name="ddn_social_token" value="<?php echo esc_attr( $token ); ?>">
			<?php wp_nonce_field( self::SOCIAL_ACTION, 'ddn_social_nonce' ); ?>

			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="accept_terms" value="1" required>
					<?php
					printf(
						/* translators: %s: enlace a Términos y Condiciones. */
						esc_html__( 'Acepto los %s.', 'ddn-suite' ),
						'<a href="' . esc_url( $legal['terms'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Términos y Condiciones', 'ddn-suite' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba.
					);
					?>
				</label>
			</p>
			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="accept_privacy" value="1" required>
					<?php
					printf(
						/* translators: %s: enlace a la Política de tratamiento de datos. */
						esc_html__( 'Acepto la %s.', 'ddn-suite' ),
						'<a href="' . esc_url( $legal['privacy'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Política de tratamiento de datos', 'ddn-suite' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba.
					);
					?>
				</label>
			</p>

			<p><button type="submit" class="btn"><?php esc_html_e( 'Crear mi cuenta', 'ddn-suite' ); ?></button></p>
		</form>
		<?php
	}

	public function handle(): void {
		$back = PageInstaller::url( PageInstaller::SLUG_REGISTER );
		if ( '' === $back ) {
			$back = home_url( '/' );
		}

		$nonce = isset( $_POST['ddn_reg_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ddn_reg_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->back_with_errors( $back, array( 'invalid_request' ) );
		}

		// Honeypot: un humano nunca llena este campo. Se responde como un
		// error genérico, sin crear nada.
		if ( '' !== trim( (string) ( $_POST['ddn_reg_website'] ?? '' ) ) ) {
			$this->back_with_errors( $back, array( 'invalid_request' ) );
		}

		$data = array(
			'full_name'        => sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) ),
			'phone'            => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'email'            => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'password'         => (string) ( $_POST['password'] ?? '' ),
			'department'       => sanitize_text_field( wp_unslash( $_POST['department'] ?? '' ) ),
			'city'             => sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) ),
			'address'          => sanitize_text_field( wp_unslash( $_POST['address'] ?? '' ) ),
			'doc_type'         => sanitize_text_field( wp_unslash( $_POST['doc_type'] ?? '' ) ),
			'doc_number'       => sanitize_text_field( wp_unslash( $_POST['doc_number'] ?? '' ) ),
			'consent_email'    => ! empty( $_POST['consent_email'] ),
			'consent_whatsapp' => ! empty( $_POST['consent_whatsapp'] ),
			'accept_terms'     => ! empty( $_POST['accept_terms'] ),
			'accept_privacy'   => ! empty( $_POST['accept_privacy'] ),
		);

		$errors = Validator::registration( $data, Options::departments(), Options::document_type_codes() );

		if ( array() === $errors && email_exists( $data['email'] ) ) {
			$errors[] = 'email_taken';
		}

		if ( array() !== $errors ) {
			$this->back_with_errors( $back, $errors );
		}

		$username = UsernameGenerator::generate(
			$data['email'],
			static fn ( string $candidate ): bool => false !== username_exists( $candidate )
		);

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $data['email'],
				'user_pass'    => $data['password'],
				'display_name' => $data['full_name'],
				'first_name'   => $data['full_name'],
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$this->back_with_errors( $back, array( 'server_error' ) );
			return;
		}

		$this->profiles->save( $user_id, $data );
		$this->profiles->record_acceptance( $user_id, 'direct' );

		wp_safe_redirect( add_query_arg( 'ddn_reg', 'success', $back ) );
		exit;
	}

	public function handle_social_confirm(): void {
		$token = isset( $_POST['ddn_social_token'] ) ? sanitize_text_field( wp_unslash( $_POST['ddn_social_token'] ) ) : '';
		$back  = PageInstaller::url( PageInstaller::SLUG_REGISTER );
		if ( '' === $back ) {
			$back = home_url( '/' );
		}
		$back_to_token = '' !== $token ? add_query_arg( 'ddn_social_token', $token, $back ) : $back;

		$nonce = isset( $_POST['ddn_social_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ddn_social_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::SOCIAL_ACTION ) ) {
			$this->back_with_errors( $back, array( 'invalid_request' ) );
		}

		$pending = '' !== $token ? get_transient( OAuthController::PENDING_TRANSIENT_PREFIX . $token ) : false;
		if ( ! is_array( $pending ) ) {
			// El token expiró o ya se usó: no hay perfil pendiente que confirmar.
			$this->back_with_errors( $back, array( 'invalid_request' ) );
			return;
		}

		$errors = array();
		if ( empty( $_POST['accept_terms'] ) ) {
			$errors[] = 'terms_required';
		}
		if ( empty( $_POST['accept_privacy'] ) ) {
			$errors[] = 'privacy_required';
		}

		if ( array() !== $errors ) {
			// El token sigue vivo a propósito: puede corregir las casillas
			// y reenviar sin repetir todo el paso con el proveedor.
			$this->back_with_errors( $back_to_token, $errors );
			return;
		}

		delete_transient( OAuthController::PENDING_TRANSIENT_PREFIX . $token );

		$profile     = $pending['profile'];
		$provider_id = (string) $pending['provider'];

		$username = UsernameGenerator::generate(
			$profile['email'],
			static fn ( string $candidate ): bool => false !== username_exists( $candidate )
		);

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $profile['email'],
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => $profile['name'],
				'first_name'   => $profile['name'],
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$this->back_with_errors( $back, array( 'server_error' ) );
			return;
		}

		$this->profiles->record_acceptance( $user_id, $provider_id );
		$this->profiles->link_oauth_id( $user_id, $provider_id, $profile['provider_user_id'] );

		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		$account_url = PageInstaller::url( PageInstaller::SLUG_ACCOUNT );
		$target      = wp_validate_redirect( (string) ( $pending['redirect_to'] ?? '' ), '' !== $account_url ? $account_url : home_url( '/' ) );

		wp_safe_redirect( $target );
		exit;
	}

	/** @param string[] $errors */
	private function back_with_errors( string $back, array $errors ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'ddn_reg'    => 'error',
					'ddn_errors' => implode( ',', $errors ),
				),
				$back
			)
		);
		exit;
	}
}
