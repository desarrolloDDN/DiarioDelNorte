<?php
/**
 * «Mi cuenta»: editar perfil y eliminar cuenta, dos formularios en la
 * misma página (page-mi-cuenta.php), cada uno con su propio token y
 * enganchado a `template_redirect`.
 *
 * Dos reglas de seguridad no negociables aquí:
 *
 * 1. Cada manejador comprueba PRIMERO si el POST trae su propio campo de
 *    nonce (Support\FormDispatch::targets()) antes de tocar cualquier
 *    otra cosa. Si no lo trae, es el envío del OTRO formulario: se
 *    devuelve sin hacer nada. Sin este candado, el envío de un
 *    formulario dispararía también la verificación de nonce del otro
 *    manejador, que fallaría y podría cortar la petición antes de que el
 *    manejador correcto llegue a ejecutarse.
 * 2. Todo actúa sobre `get_current_user_id()` — nunca sobre un ID que
 *    venga en el formulario. Así no hay forma de editar o borrar el
 *    perfil de otra persona cambiando un campo oculto.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers;

use DiarioDelNorte\Suite\Subscribers\Install\PageInstaller;
use DiarioDelNorte\Suite\Subscribers\Support\FormDispatch;
use DiarioDelNorte\Suite\Subscribers\Support\Messages;
use DiarioDelNorte\Suite\Subscribers\Support\Validator;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AccountController {

	private const PROFILE_NONCE_ACTION = 'ddn_subscriber_profile_update';
	private const DELETE_NONCE_ACTION  = 'ddn_subscriber_delete_account';

	public function __construct( private readonly ProfileRepository $profiles ) {}

	public function register(): void {
		add_action( 'template_redirect', array( $this, 'handle_profile_update' ) );
		add_action( 'template_redirect', array( $this, 'handle_delete_account' ) );
	}

	/** Renderiza los dos formularios; lo llama theme/page-mi-cuenta.php. */
	public function render(): void {
		if ( ! is_user_logged_in() ) {
			printf(
				'<p>%s <a href="%s">%s</a></p>',
				esc_html__( 'Debes iniciar sesión para ver tu cuenta.', 'ddn-suite' ),
				esc_url( PageInstaller::url( PageInstaller::SLUG_LOGIN ) ),
				esc_html__( 'Ingresar', 'ddn-suite' )
			);
			return;
		}

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		$profile = $this->profiles->get( $user_id );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide qué aviso mostrar, no cambia estado.
		$profile_status = isset( $_GET['ddn_profile'] ) ? sanitize_key( wp_unslash( $_GET['ddn_profile'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- idem.
		$delete_status = isset( $_GET['ddn_delete'] ) ? sanitize_key( wp_unslash( $_GET['ddn_delete'] ) ) : '';

		echo '<h2>' . esc_html__( 'Mi información', 'ddn-suite' ) . '</h2>';

		if ( 'updated' === $profile_status ) {
			printf( '<p class="ddn-form-notice ddn-form-notice--ok">%s</p>', esc_html__( 'Tus datos se actualizaron correctamente.', 'ddn-suite' ) );
		} elseif ( '' !== $profile_status ) {
			printf( '<p class="ddn-form-notice ddn-form-notice--error">%s</p>', esc_html( Messages::profile( $profile_status ) ) );
		}
		?>
		<form class="ddn-form" method="post" action="">
			<?php wp_nonce_field( self::PROFILE_NONCE_ACTION, 'ddn_profile_nonce' ); ?>

			<p class="ddn-field">
				<label for="ddn_full_name"><?php esc_html_e( 'Nombre completo', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_full_name" name="full_name" value="<?php echo esc_attr( $user instanceof WP_User ? $user->display_name : '' ); ?>" required>
			</p>
			<p class="ddn-field">
				<label for="ddn_email_ro"><?php esc_html_e( 'Correo electrónico', 'ddn-suite' ); ?></label>
				<input type="email" id="ddn_email_ro" value="<?php echo esc_attr( $user instanceof WP_User ? $user->user_email : '' ); ?>" readonly>
				<span class="description"><?php esc_html_e( 'El correo no se puede cambiar desde aquí.', 'ddn-suite' ); ?></span>
			</p>
			<p class="ddn-field">
				<label for="ddn_phone"><?php esc_html_e( 'Celular', 'ddn-suite' ); ?></label>
				<input type="tel" id="ddn_phone" name="phone" value="<?php echo esc_attr( (string) $profile['phone'] ); ?>" required>
			</p>

			<p class="ddn-field">
				<label for="ddn_department"><?php esc_html_e( 'Departamento', 'ddn-suite' ); ?></label>
				<select id="ddn_department" name="department">
					<option value=""><?php esc_html_e( 'Selecciona (opcional)', 'ddn-suite' ); ?></option>
					<?php foreach ( Options::departments() as $ddn_dep ) : ?>
						<option value="<?php echo esc_attr( $ddn_dep ); ?>" <?php selected( $profile['department'], $ddn_dep ); ?>><?php echo esc_html( $ddn_dep ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ddn-field">
				<label for="ddn_city"><?php esc_html_e( 'Ciudad', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_city" name="city" value="<?php echo esc_attr( (string) $profile['city'] ); ?>">
			</p>
			<p class="ddn-field">
				<label for="ddn_address"><?php esc_html_e( 'Dirección', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_address" name="address" value="<?php echo esc_attr( (string) $profile['address'] ); ?>">
			</p>
			<p class="ddn-field ddn-field--pair">
				<label for="ddn_doc_type"><?php esc_html_e( 'Tipo de documento', 'ddn-suite' ); ?></label>
				<select id="ddn_doc_type" name="doc_type">
					<option value=""><?php esc_html_e( 'Selecciona (opcional)', 'ddn-suite' ); ?></option>
					<?php foreach ( Options::document_types() as $ddn_code => $ddn_label ) : ?>
						<option value="<?php echo esc_attr( $ddn_code ); ?>" <?php selected( $profile['doc_type'], $ddn_code ); ?>><?php echo esc_html( $ddn_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="ddn-field ddn-field--pair">
				<label for="ddn_doc_number"><?php esc_html_e( 'Número de documento', 'ddn-suite' ); ?></label>
				<input type="text" id="ddn_doc_number" name="doc_number" value="<?php echo esc_attr( (string) $profile['doc_number'] ); ?>">
			</p>

			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="consent_email" value="1" <?php checked( $profile['consent_email'] ); ?>> <?php esc_html_e( 'Quiero recibir correos de Diario del Norte.', 'ddn-suite' ); ?></label>
			</p>
			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" name="consent_whatsapp" value="1" <?php checked( $profile['consent_whatsapp'] ); ?>> <?php esc_html_e( 'Quiero recibir WhatsApp de Diario del Norte.', 'ddn-suite' ); ?></label>
			</p>

			<h3><?php esc_html_e( 'Cambiar contraseña (opcional)', 'ddn-suite' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Deja los tres campos en blanco si no quieres cambiarla.', 'ddn-suite' ); ?></p>
			<p class="ddn-field">
				<label for="ddn_current_password"><?php esc_html_e( 'Contraseña actual', 'ddn-suite' ); ?></label>
				<input type="password" id="ddn_current_password" name="current_password">
			</p>
			<p class="ddn-field">
				<label for="ddn_new_password"><?php esc_html_e( 'Nueva contraseña', 'ddn-suite' ); ?></label>
				<input type="password" id="ddn_new_password" name="new_password" minlength="8">
			</p>
			<p class="ddn-field">
				<label for="ddn_confirm_password"><?php esc_html_e( 'Confirmar nueva contraseña', 'ddn-suite' ); ?></label>
				<input type="password" id="ddn_confirm_password" name="confirm_password" minlength="8">
			</p>

			<p><button type="submit" class="btn"><?php esc_html_e( 'Guardar cambios', 'ddn-suite' ); ?></button></p>
		</form>

		<h2><?php esc_html_e( 'Eliminar mi cuenta', 'ddn-suite' ); ?></h2>
		<?php if ( '' !== $delete_status ) : ?>
			<p class="ddn-form-notice ddn-form-notice--error"><?php echo esc_html( Messages::delete_account( $delete_status ) ); ?></p>
		<?php endif; ?>
		<p class="description"><?php esc_html_e( 'Borra tu cuenta y toda tu información de este sitio de forma permanente. No se puede deshacer.', 'ddn-suite' ); ?></p>
		<form
			class="ddn-form ddn-form--danger"
			method="post"
			action=""
			id="ddn-delete-account-form"
			data-confirm="<?php echo esc_attr__( '¿Seguro que quieres eliminar tu cuenta? Esta acción no se puede deshacer.', 'ddn-suite' ); ?>"
		>
			<?php wp_nonce_field( self::DELETE_NONCE_ACTION, 'ddn_delete_nonce' ); ?>
			<p class="ddn-field">
				<label for="ddn_delete_password"><?php esc_html_e( 'Escribe tu contraseña para confirmar', 'ddn-suite' ); ?></label>
				<input type="password" id="ddn_delete_password" name="ddn_delete_password" required>
			</p>
			<p class="ddn-field ddn-field--check">
				<label><input type="checkbox" id="ddn-delete-account-confirm" name="ddn_delete_confirm" value="1"> <?php esc_html_e( 'Entiendo que esta acción no se puede deshacer.', 'ddn-suite' ); ?></label>
			</p>
			<p><button type="submit" id="ddn-delete-account-btn" class="btn btn--ghost" disabled><?php esc_html_e( 'Eliminar mi cuenta', 'ddn-suite' ); ?></button></p>
		</form>
		<?php
	}

	public function handle_profile_update(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo mira si este POST es de ESTE formulario; el nonce se verifica más abajo.
		if ( ! FormDispatch::targets( $_POST, 'ddn_profile_nonce' ) ) {
			return; // El envío es del otro formulario de esta página: se deja intacto.
		}
		if ( ! is_page( PageInstaller::SLUG_ACCOUNT ) || ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$back    = $this->back_url();

		$nonce = sanitize_text_field( wp_unslash( $_POST['ddn_profile_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, self::PROFILE_NONCE_ACTION ) ) {
			$this->redirect_with( $back, 'ddn_profile', 'invalid_request' );
		}

		$data = array(
			'full_name'        => sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) ),
			'phone'            => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'department'       => sanitize_text_field( wp_unslash( $_POST['department'] ?? '' ) ),
			'city'             => sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) ),
			'address'          => sanitize_text_field( wp_unslash( $_POST['address'] ?? '' ) ),
			'doc_type'         => sanitize_text_field( wp_unslash( $_POST['doc_type'] ?? '' ) ),
			'doc_number'       => sanitize_text_field( wp_unslash( $_POST['doc_number'] ?? '' ) ),
			'consent_email'    => ! empty( $_POST['consent_email'] ),
			'consent_whatsapp' => ! empty( $_POST['consent_whatsapp'] ),
		);

		$errors = Validator::profile( $data, Options::departments(), Options::document_type_codes() );

		$current_pwd = (string) ( $_POST['current_password'] ?? '' );
		$new_pwd     = (string) ( $_POST['new_password'] ?? '' );
		$confirm_pwd = (string) ( $_POST['confirm_password'] ?? '' );
		$pwd_check   = Validator::password_change( $current_pwd, $new_pwd, $confirm_pwd );
		$errors      = array_merge( $errors, $pwd_check['errors'] );

		$user                 = get_userdata( $user_id );
		$new_password_to_save = null;

		if ( ! $pwd_check['skip'] && array() === $pwd_check['errors'] ) {
			if ( ! $user instanceof WP_User || ! self::verify_password( $current_pwd, $user->user_pass, 'wp_check_password' ) ) {
				$errors[] = 'current_password_invalid';
			} else {
				$new_password_to_save = $new_pwd;
			}
		}

		if ( array() !== $errors ) {
			$this->redirect_with( $back, 'ddn_profile', $errors[0] );
		}

		$this->profiles->save( $user_id, $data );

		// Un único wp_update_user() con la contraseña incluida cuando
		// aplica: así WordPress detecta que el propio usuario autenticado
		// se actualizó y refresca su cookie de sesión él solo. Partir esto
		// en dos llamadas (datos por un lado, wp_set_password() aparte)
		// cierra la sesión de quien la cambia a medio camino.
		wp_update_user( self::build_update_args( $user_id, $data['full_name'], $new_password_to_save ) );

		$this->redirect_with( $back, 'ddn_profile', 'updated' );
	}

	public function handle_delete_account(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo mira si este POST es de ESTE formulario; el nonce se verifica más abajo.
		if ( ! FormDispatch::targets( $_POST, 'ddn_delete_nonce' ) ) {
			return; // El envío es del otro formulario de esta página: se deja intacto.
		}
		if ( ! is_page( PageInstaller::SLUG_ACCOUNT ) || ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$back    = $this->back_url();

		$nonce = sanitize_text_field( wp_unslash( $_POST['ddn_delete_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, self::DELETE_NONCE_ACTION ) ) {
			$this->redirect_with( $back, 'ddn_delete', 'invalid_request' );
		}

		if ( empty( $_POST['ddn_delete_confirm'] ) ) {
			$this->redirect_with( $back, 'ddn_delete', 'confirm_required' );
		}

		$password = (string) ( $_POST['ddn_delete_password'] ?? '' );
		if ( '' === $password ) {
			$this->redirect_with( $back, 'ddn_delete', 'password_required' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User || ! self::verify_password( $password, $user->user_pass, 'wp_check_password' ) ) {
			$this->redirect_with( $back, 'ddn_delete', 'password_invalid' );
		}

		// wp_delete_user()/wpmu_delete_user() ya borran todo el usermeta
		// del usuario (perfil extendido incluido) como parte del borrado.
		if ( is_multisite() ) {
			require_once ABSPATH . 'wp-admin/includes/ms.php';
			wpmu_delete_user( $user_id );
		} else {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );
		}

		wp_destroy_current_session();
		wp_clear_auth_cookie();

		$login_url = PageInstaller::url( PageInstaller::SLUG_LOGIN );
		wp_safe_redirect( add_query_arg( 'ddn_account', 'deleted', '' !== $login_url ? $login_url : home_url( '/' ) ) );
		exit;
	}

	/**
	 * Argumentos para UNA sola llamada a wp_update_user(): la contraseña,
	 * si aplica, va en el MISMO array — nunca en una llamada aparte.
	 * Pública y pura para poder probarla sin WordPress.
	 *
	 * @return array<string,mixed>
	 */
	public static function build_update_args( int $user_id, string $full_name, ?string $new_password ): array {
		$args = array(
			'ID'           => $user_id,
			'display_name' => $full_name,
			'first_name'   => $full_name,
		);

		if ( null !== $new_password ) {
			$args['user_pass'] = $new_password;
		}

		return $args;
	}

	/**
	 * Envoltorio puro sobre el verificador inyectado (wp_check_password en
	 * producción; una función falsa en las pruebas).
	 *
	 * @param callable(string,string):bool $checker
	 */
	public static function verify_password( string $provided, string $hash, callable $checker ): bool {
		return (bool) $checker( $provided, $hash );
	}

	private function back_url(): string {
		$url = PageInstaller::url( PageInstaller::SLUG_ACCOUNT );

		return '' !== $url ? $url : home_url( '/' );
	}

	private function redirect_with( string $back, string $query_key, string $status ): void {
		wp_safe_redirect( add_query_arg( $query_key, $status, $back ) );
		exit;
	}
}
