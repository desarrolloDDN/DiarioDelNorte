<?php
/**
 * Login propio de suscriptores: página del tema (page-ingresar.php),
 * NUNCA wp-login.php — se redirige cualquier visita a wp-login.php (y se
 * reescribe wp_login_url()) hacia aquí. Recuperar contraseña sigue
 * siendo el flujo nativo de WordPress (fuera de alcance de este módulo).
 *
 * Límite de intentos: 5 fallos en 15 minutos, contados por la IP real de
 * la conexión (nunca por usuario — así nadie bloquea la cuenta de otra
 * persona fallando su contraseña a propósito — ni por cabeceras como
 * X-Forwarded-For, que se pueden falsear). Se aplica en los hooks nativos
 * de WordPress (`authenticate`, `wp_login_failed`, `wp_login`), no solo
 * dentro de `handle()`: así cubre cualquier forma de intentar entrar
 * —esta página, un POST directo a wp-login.php, XML-RPC…—, no solo
 * nuestro propio formulario.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use DiarioDelNorte\Suite\Subscribers\Install\PageInstaller;
use DiarioDelNorte\Suite\Subscribers\OAuth\OAuthController;
use DiarioDelNorte\Suite\Subscribers\Support\Messages;
use DiarioDelNorte\Suite\Subscribers\Support\RateLimiter;
use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LoginController {

	private const ACTION      = 'ddn_subscriber_login';
	private const LOCKED_CODE = 'ddn_login_locked';

	public function __construct(
		private readonly RateLimiter $rate_limiter,
		private readonly OAuthController $oauth,
	) {}

	public function register(): void {
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );

		add_filter( 'login_url', array( $this, 'filter_login_url' ), 10, 1 );
		add_action( 'login_init', array( $this, 'redirect_wp_login' ) );

		// Universal: corre para cualquier intento de autenticación, sea por
		// donde sea que llegue.
		//
		// Prioridad 30 (no antes): las comprobaciones nativas de
		// usuario/contraseña de WordPress corren en 20 y, si reciben un
		// $user que YA es un WP_Error de una prioridad anterior, lo
		// PISAN con su propio resultado en vez de respetarlo — bloquear
		// en una prioridad más temprana quedaría sin efecto.
		add_filter( 'authenticate', array( $this, 'block_if_locked' ), 30 );
		add_action( 'wp_login_failed', array( $this, 'record_failure_on_any_login' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'reset_on_success' ), 10, 2 );
	}

	/**
	 * @param WP_User|WP_Error|null $user
	 * @return WP_User|WP_Error|null
	 */
	public function block_if_locked( $user ) {
		// Ya es un WP_Error de las comprobaciones nativas (usuario o
		// contraseña incorrectos): eso ya deniega el acceso igual: no hay
		// nada que "reforzar" pisándolo.
		if ( ! $user instanceof WP_User ) {
			return $user;
		}

		if ( $this->rate_limiter->is_locked( $this->rate_limit_key() ) ) {
			return new WP_Error( self::LOCKED_CODE, Messages::login( 'locked' ) );
		}

		return $user;
	}

	/**
	 * Firma exigida por el hook `wp_login_failed`; solo importa que se
	 * llame, sus argumentos no se usan (se cuenta por IP, no por usuario).
	 */
	public function record_failure_on_any_login( string $username, $error = null ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->rate_limiter->record_failure( $this->rate_limit_key() );
	}

	/** Firma exigida por el hook `wp_login`; sus argumentos no se usan. */
	public function reset_on_success( string $user_login, $user ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->rate_limiter->reset( $this->rate_limit_key() );
	}

	public static function action_url(): string {
		return admin_url( 'admin-post.php' );
	}

	public static function action_name(): string {
		return self::ACTION;
	}

	public function filter_login_url( string $login_url ): string {
		$own = PageInstaller::url( PageInstaller::SLUG_LOGIN );

		return '' !== $own ? $own : $login_url;
	}

	/**
	 * wp-login.php sigue existiendo (lo necesitan «olvidé mi contraseña»,
	 * «cerrar sesión», etc.); solo se redirige la pantalla de login en sí.
	 */
	public function redirect_wp_login(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide si redirige, no cambia estado.
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'login';

		if ( 'login' !== $action || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			return;
		}

		$own = PageInstaller::url( PageInstaller::SLUG_LOGIN );
		if ( '' === $own ) {
			return;
		}

		wp_safe_redirect( $own );
		exit;
	}

	/** Renderiza el formulario; lo llama theme/page-ingresar.php. */
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide qué mensaje mostrar, no cambia estado.
		$status = isset( $_GET['ddn_login'] ) ? sanitize_key( wp_unslash( $_GET['ddn_login'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
		$account_status = isset( $_GET['ddn_account'] ) ? sanitize_key( wp_unslash( $_GET['ddn_account'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- se reenvía tal cual al formulario, no se usa para nada más.
		$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';

		echo '<div class="ddn-auth-card">';
		echo '<h1 class="ddn-auth-card__title">' . esc_html__( 'Ingresar', 'ddn-suite' ) . '</h1>';
		echo '<p class="ddn-auth-card__subtitle">' . esc_html__( 'Entra con tu cuenta para ver tus preferencias y los artículos exclusivos para suscriptores.', 'ddn-suite' ) . '</p>';

		if ( 'deleted' === $account_status ) {
			printf( '<p class="ddn-form-notice ddn-form-notice--ok">%s</p>', esc_html__( 'Tu cuenta se eliminó correctamente.', 'ddn-suite' ) );
		} elseif ( '' !== $status ) {
			printf( '<p class="ddn-form-notice ddn-form-notice--error">%s</p>', esc_html( Messages::login( $status ) ) );
		}

		$this->oauth->render_buttons( $redirect_to );
		?>
		<div class="ddn-divider"><span><?php esc_html_e( 'O ingresa con tu correo', 'ddn-suite' ); ?></span></div>

		<form class="ddn-form" method="post" action="<?php echo esc_url( self::action_url() ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::action_name() ); ?>">
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
			<?php wp_nonce_field( self::ACTION, 'ddn_login_nonce' ); ?>

			<p class="ddn-form-hp" aria-hidden="true">
				<label for="ddn_login_website"><?php esc_html_e( 'Sitio web (dejar en blanco)', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_login_website" name="ddn_login_website" tabindex="-1" autocomplete="off">
			</p>

			<p class="ddn-field">
				<label for="ddn_login_user"><?php esc_html_e( 'Usuario o correo', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_login_user" name="ddn_login_user" required>
			</p>
			<p class="ddn-field">
				<label for="ddn_login_pwd"><?php esc_html_e( 'Contraseña', 'ddn-suite' ); ?></label>
				<input type="password" id="ddn_login_pwd" name="ddn_login_pwd" required>
			</p>
			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="ddn_remember" value="1"> <?php esc_html_e( 'Mantener sesión iniciada', 'ddn-suite' ); ?></label>
			</p>

			<p><button type="submit" class="btn ddn-form-submit"><?php esc_html_e( 'Ingresar', 'ddn-suite' ); ?></button></p>
		</form>

		<p class="ddn-auth-card__foot"><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( '¿Olvidaste tu contraseña?', 'ddn-suite' ); ?></a></p>
		<p class="ddn-auth-card__foot">
			<?php
			printf(
				/* translators: %s: enlace «Crea una». */
				esc_html__( '¿No tienes cuenta? %s', 'ddn-suite' ),
				'<a href="' . esc_url( PageInstaller::url( PageInstaller::SLUG_REGISTER ) ) . '"><strong>' . esc_html__( 'Crea una', 'ddn-suite' ) . '</strong></a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba.
			);
			?>
		</p>
		<?php
		echo '</div>';
	}

	public function handle(): void {
		$back = PageInstaller::url( PageInstaller::SLUG_LOGIN );
		if ( '' === $back ) {
			$back = home_url( '/' );
		}

		$nonce = isset( $_POST['ddn_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ddn_login_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->back_with( $back, 'invalid_request' );
		}

		if ( '' !== trim( (string) ( $_POST['ddn_login_website'] ?? '' ) ) ) {
			$this->back_with( $back, 'invalid_request' );
		}

		$creds = array(
			'user_login'    => sanitize_text_field( wp_unslash( $_POST['ddn_login_user'] ?? '' ) ),
			'user_password' => (string) ( $_POST['ddn_login_pwd'] ?? '' ),
			'remember'      => ! empty( $_POST['ddn_remember'] ),
		);

		// wp_signon() dispara el filtro `authenticate` (que aquí aplica el
		// bloqueo por IP, ver register()) y, si falla, la acción nativa
		// `wp_login_failed` (que aquí cuenta el fallo) — no hace falta
		// repetir esa cuenta a mano.
		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			// Mismo mensaje genérico tanto si el usuario no existe como si
			// la contraseña es incorrecta — salvo que la razón sea el
			// bloqueo por intentos, que sí se distingue.
			$status = self::LOCKED_CODE === $user->get_error_code() ? 'locked' : 'invalid';
			$this->back_with( $back, $status );
		}

		$requested = isset( $_POST['redirect_to'] ) ? (string) wp_unslash( $_POST['redirect_to'] ) : '';

		// Si no vino un destino explícito (típicamente sí viene: WordPress
		// lo agrega solo al mandar a un visitante sin sesión a esta página
		// desde una URL de wp-admin), el destino por defecto depende de
		// quién inició sesión: al personal de redacción (cualquiera con
		// permiso de publicar) lo manda a wp-admin, no a «Mi cuenta» — esa
		// es la página del suscriptor, no la suya.
		if ( $user instanceof WP_User && user_can( $user, 'edit_posts' ) ) {
			$default = admin_url();
		} else {
			$account_url = PageInstaller::url( PageInstaller::SLUG_ACCOUNT );
			$default     = '' !== $account_url ? $account_url : home_url( '/' );
		}
		// Nunca se redirige a una URL ajena al sitio, venga lo que venga en la petición.
		$target = wp_validate_redirect( $requested, $default );

		wp_safe_redirect( $target );
		exit;
	}

	private function back_with( string $back, string $status ): void {
		wp_safe_redirect( add_query_arg( 'ddn_login', $status, $back ) );
		exit;
	}

	private function rate_limit_key(): string {
		return 'login:' . $this->client_ip();
	}

	/**
	 * IP real de la conexión TCP. Nunca X-Forwarded-For ni otra cabecera
	 * enviada por el cliente: se pueden falsear con total libertad.
	 */
	private function client_ip(): string {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}
}
