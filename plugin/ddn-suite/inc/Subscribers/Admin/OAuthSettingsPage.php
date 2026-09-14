<?php
/**
 * Ajustes de inicio social: Client ID/Secret de Google y Facebook. El
 * secreto se guarda cifrado (ver OAuth\CredentialsStore) y nunca se
 * vuelve a mostrar en el formulario.
 *
 * @package DiarioDelNorte\Suite
 */

declare(strict_types=1);

namespace DiarioDelNorte\Suite\Subscribers\Admin;

use DiarioDelNorte\Suite\Subscribers\Install\CapabilityInstaller;
use DiarioDelNorte\Suite\Subscribers\OAuth\CredentialsStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OAuthSettingsPage {

	public const SLUG    = 'ddn-subscribers-oauth';
	private const ACTION = 'ddn_oauth_settings_save';

	public function __construct( private readonly CredentialsStore $credentials ) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	public function menu(): void {
		add_submenu_page(
			SubscribersPage::SLUG,
			__( 'Inicio social', 'ddn-suite' ),
			__( 'Inicio social', 'ddn-suite' ),
			CapabilityInstaller::CAP,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( CapabilityInstaller::CAP ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver esta página.', 'ddn-suite' ) );
		}

		$google   = $this->credentials->get( 'google' );
		$facebook = $this->credentials->get( 'facebook' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide si se muestra un aviso, no cambia estado.
		if ( isset( $_GET['ddn_saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Guardado.', 'ddn-suite' ) . '</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Inicio social (Google / Facebook)', 'ddn-suite' ); ?></h1>
			<p><?php esc_html_e( 'El Client Secret / App Secret se guarda cifrado. Déjalo en blanco para conservar el que ya tienes guardado.', 'ddn-suite' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>

				<h2>Google</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ddn_google_id">Client ID</label></th>
						<td><input type="text" id="ddn_google_id" name="google_client_id" class="regular-text" value="<?php echo esc_attr( $google['client_id'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="ddn_google_secret">Client Secret</label></th>
						<td><input type="password" id="ddn_google_secret" name="google_client_secret" class="regular-text" autocomplete="off" placeholder="<?php echo '' !== $google['client_secret'] ? esc_attr__( '(sin cambios)', 'ddn-suite' ) : ''; ?>"></td>
					</tr>
				</table>

				<h2>Facebook</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ddn_fb_id">App ID</label></th>
						<td><input type="text" id="ddn_fb_id" name="facebook_client_id" class="regular-text" value="<?php echo esc_attr( $facebook['client_id'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="ddn_fb_secret">App Secret</label></th>
						<td><input type="password" id="ddn_fb_secret" name="facebook_client_secret" class="regular-text" autocomplete="off" placeholder="<?php echo '' !== $facebook['client_secret'] ? esc_attr__( '(sin cambios)', 'ddn-suite' ) : ''; ?>"></td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'URL de redirección para registrar en cada proveedor', 'ddn-suite' ); ?></h2>
			<p><code><?php echo esc_html( add_query_arg( 'action', 'ddn_oauth_callback_google', admin_url( 'admin-post.php' ) ) ); ?></code></p>
			<p><code><?php echo esc_html( add_query_arg( 'action', 'ddn_oauth_callback_facebook', admin_url( 'admin-post.php' ) ) ); ?></code></p>
		</div>
		<?php
	}

	public function save(): void {
		if ( ! current_user_can( CapabilityInstaller::CAP ) ) {
			wp_die( esc_html__( 'No tienes permiso para hacer esto.', 'ddn-suite' ) );
		}
		check_admin_referer( self::ACTION );

		$this->credentials->save(
			'google',
			sanitize_text_field( wp_unslash( $_POST['google_client_id'] ?? '' ) ),
			(string) ( $_POST['google_client_secret'] ?? '' )
		);
		$this->credentials->save(
			'facebook',
			sanitize_text_field( wp_unslash( $_POST['facebook_client_id'] ?? '' ) ),
			(string) ( $_POST['facebook_client_secret'] ?? '' )
		);

		wp_safe_redirect( add_query_arg( 'ddn_saved', '1', admin_url( 'admin.php?page=' . self::SLUG ) ) );
		exit;
	}
}
